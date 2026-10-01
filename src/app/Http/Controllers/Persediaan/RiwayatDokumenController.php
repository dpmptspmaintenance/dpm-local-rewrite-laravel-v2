<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Http\Controllers\Persediaan\Concerns\ComputesStock;
use App\Models\Persediaan\MasterBarang;
use App\Models\Persediaan\TransaksiDetail;
use App\Models\Persediaan\TransaksiHeader;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RiwayatDokumenController extends Controller
{
    use ChecksPersediaanAccess, ComputesStock;

    /** Halaman riwayat BAST Masuk & Saldo Awal. */
    public function indexMasuk(Request $request)
    {
        [$isAdmin, $filterBpp, $extras] = $this->commonData($request);

        $docs = TransaksiHeader::query()
            ->whereIn('jenis_mutasi', ['masuk', 'saldo_awal'])
            ->with(['details.barang.rekening', 'details.barang.satuan'])
            ->tap(fn ($q) => $this->scopeByUser($q, $isAdmin, auth()->id(), auth()->user()->bidang, $filterBpp))
            ->get();

        return view('persediaan.riwayat-dokumen.masuk', array_merge($extras, [
            'masukDocs' => $this->buildDocs($docs),
        ]));
    }

    /** Halaman riwayat Bon Pengeluaran. */
    public function indexKeluar(Request $request)
    {
        [$isAdmin, $filterBpp, $extras] = $this->commonData($request);

        $docs = TransaksiHeader::query()
            ->where('jenis_mutasi', 'keluar')
            ->with(['details.barang.rekening', 'details.barang.satuan'])
            ->tap(fn ($q) => $this->scopeByUser($q, $isAdmin, auth()->id(), auth()->user()->bidang, $filterBpp))
            ->get();

        return view('persediaan.riwayat-dokumen.keluar', array_merge($extras, [
            'keluarDocs' => $this->buildDocs($docs),
        ]));
    }

    /** Data bersama kedua halaman riwayat: auth guard, filter BPP, opsi modal edit. */
    private function commonData(Request $request): array
    {
        $this->abortIfNotAdminOrBpp();

        $user = auth()->user();
        $isAdmin = $this->isAdmin($user);
        $filterBpp = $isAdmin ? (int) $request->input('filter_bpp', 0) : 0;

        $users = User::query()->where('is_aktif', 1)->orderBy('nama')->get(['id', 'nama', 'bidang', 'is_bpp', 'is_admin_persediaan']);

        return [$isAdmin, $filterBpp, [
            'mapUsers' => $users->pluck('nama', 'id')->all(),
            'arrBpp' => $users->filter(fn ($u) => (int) $u->is_bpp === 1 || (int) $u->is_admin_persediaan === 1)->values(),
            'filterBpp' => $filterBpp,
            'barangOptions' => MasterBarang::query()->orderBy('nama_barang')->get(['id', 'nama_barang', 'harga_satuan']),
            'stokOptions' => $this->stockBatches(),
            'isAdmin' => $isAdmin,
        ]];
    }

    /** 1. Ajukan draft ke admin. */
    public function ajukanKeAdmin(Request $request)
    {
        $this->abortIfNotAdminOrBpp();

        $validated = $request->validate(['id_header' => ['required', 'integer']]);
        $header = TransaksiHeader::findOrFail($validated['id_header']);

        if ($this->bulanDikunci($header->tanggal_transaksi->format('Y-m-d'), $this->isAdmin(auth()->user()))) {
            return back()->with('error', 'Gagal mengajukan: Periode laporan bulan tersebut sudah dikunci!');
        }

        $updated = TransaksiHeader::query()
            ->where('id', $header->id)
            ->where('status', 'draft')
            ->update(['status' => 'menunggu']);

        if ($updated > 0) {
            return back()->with('success', 'Berhasil! Dokumen BAST telah diajukan ke Admin Persediaan untuk diverifikasi.');
        }

        return back();
    }

    /** 2. Hapus dokumen (draft only, or any status for admin). */
    public function hapusDokumen(Request $request)
    {
        $this->abortIfNotAdminOrBpp();

        $validated = $request->validate(['id_header' => ['required', 'integer']]);
        $header = TransaksiHeader::findOrFail($validated['id_header']);

        if ($this->bulanDikunci($header->tanggal_transaksi->format('Y-m-d'), $this->isAdmin(auth()->user()))) {
            return back()->with('error', 'Gagal menghapus: Periode laporan bulan tersebut sudah dikunci!');
        }

        if ($header->status !== 'draft' && ! $this->isAdmin(auth()->user())) {
            return back()->with('error', 'Akses ditolak. Anda tidak berhak menghapus dokumen ini.');
        }

        $dir = $this->uploadDir($header->jenis_mutasi);
        DB::connection('persediaan')->transaction(function () use ($header, $dir) {
            foreach ((array) $header->lampiran as $file) {
                Storage::disk('public')->delete("persediaan-file/{$dir}/{$file}");
            }
            TransaksiDetail::query()->where('id_header', $header->id)->delete();
            $header->delete();
        });

        return back()->with('success', 'Berhasil! Dokumen draft berhasil dihapus secara permanen dari sistem.');
    }

    /** 3. Approval (admin). */
    public function prosesApproval(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate([
            'id_header' => ['required', 'integer'],
            'status_app' => ['required', 'in:disetujui,ditolak'], // whitelist (legacy trusted client value)
        ]);

        $statusApp = $validated['status_app'];

        TransaksiHeader::query()->where('id', $validated['id_header'])->update([
            'status' => $statusApp,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $label = $statusApp === 'disetujui' ? 'DISETUJUI' : 'DITOLAK';

        return back()->with('success', "Status dokumen berhasil diperbarui menjadi: <span class='text-primary'>{$label}</span>");
    }

    /** 4. Edit dokumen + sync details + lampiran (both BAST & Bon flows). */
    public function editDokumen(Request $request)
    {
        $this->abortIfNotAdminOrBpp();

        $user = auth()->user();
        $isAdmin = $this->isAdmin($user);

        $idHeader = (int) $request->input('id_header'); // casted — legacy had SQLi here
        $header = TransaksiHeader::findOrFail($idHeader);

        $validated = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:100'],
            'pihak_terkait' => ['required', 'string', 'max:255'],
            'alasan_pengambilan' => ['nullable', 'string'],
            'ttd_kiri' => ['required', 'string', 'max:255'],
            'ttd_tengah' => ['nullable', 'string', 'max:255'],
            'ttd_kanan' => ['required', 'string', 'max:255'],
            'ttd_sekretaris' => ['nullable', 'string', 'max:255'],
            'id_detail' => ['nullable', 'array'],
            'id_detail.*' => ['integer'],
            'qty_detail' => ['nullable', 'array'],
            'harga_detail' => ['nullable', 'array'],
            'new_id_barang' => ['nullable', 'array'],
            'new_id_barang.*' => ['nullable', 'integer'],
            'new_qty' => ['nullable', 'array'],
            'new_harga' => ['nullable', 'array'],
            'edit_lampiran_berkas' => ['nullable', 'array'],
            'edit_lampiran_berkas.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'existing_files' => ['nullable', 'array'],
            'existing_files.*' => ['string'],
        ]);

        $dir = $this->uploadDir($header->jenis_mutasi);

        if ($this->bulanDikunci($validated['tanggal_transaksi'], $isAdmin)) {
            return back()->with('error', 'Gagal Edit: Pelaporan periode bulan tersebut telah dikunci.');
        }

        // --- lampiran reconciliation (kept old files + new uploads) ---
        $currentFiles = (array) $header->lampiran;
        $kept = array_values(array_intersect($validated['existing_files'] ?? [], $currentFiles));

        $newUploads = [];
        if ($request->hasFile('edit_lampiran_berkas')) {
            foreach ($request->file('edit_lampiran_berkas') as $file) {
                $name = time().'_UPD_'.uniqid().'.'.$file->guessExtension();
                $file->storeAs("persediaan-file/{$dir}/{$name}", '', 'public');
                $newUploads[] = $name;
            }
        }

        $finalFiles = array_merge($kept, $newUploads);
        if (empty($finalFiles)) {
            foreach ($newUploads as $f) {
                Storage::disk('public')->delete("persediaan-file/{$dir}/{$f}");
            }

            return back()->with('error', 'Gagal Simpan Perubahan: Dokumen wajib menyertakan minimal 1 buah berkas lampiran bukti fisik!');
        }

        // delete physical files that were removed in the UI
        foreach ($currentFiles as $old) {
            if (! in_array($old, $kept, true)) {
                Storage::disk('public')->delete("persediaan-file/{$dir}/{$old}");
            }
        }

        try {
            DB::connection('persediaan')->transaction(function () use ($header, $validated, $finalFiles, $request) {
                $header->update([
                    'tanggal_transaksi' => $validated['tanggal_transaksi'],
                    'pihak_terkait' => $validated['pihak_terkait'],
                    'alasan_pengambilan' => ($validated['alasan_pengambilan'] ?? null) ?: null,
                    'no_bukti' => ($validated['no_bukti'] ?? null) ?: null,
                    'ttd_kiri' => $validated['ttd_kiri'],
                    'ttd_tengah' => ($validated['ttd_tengah'] ?? null) ?: null,
                    'ttd_kanan' => $validated['ttd_kanan'],
                    'ttd_sekretaris' => ($validated['ttd_sekretaris'] ?? null) ?: 'Anton Siswartono, S.Sos, M.M',
                    'lampiran' => $finalFiles,
                    'status' => $header->status === 'ditolak' ? 'draft' : $header->status,
                ]);

                // --- detail sync ---
                $postedIds = array_map('intval', $validated['id_detail'] ?? []);
                $existingIds = TransaksiDetail::query()->where('id_header', $header->id)->pluck('id')->all();

                $toDelete = array_diff($existingIds, $postedIds);
                if (! empty($toDelete)) {
                    TransaksiDetail::query()->whereIn('id', $toDelete)->delete();
                }

                foreach (($validated['id_detail'] ?? []) as $i => $idDtl) {
                    $qty = (int) ($validated['qty_detail'][$i] ?? 0);
                    if ($qty > 0) {
                        TransaksiDetail::query()->where('id', (int) $idDtl)->update([
                            'qty' => $qty,
                            'harga_satuan' => $this->parseHarga($validated['harga_detail'][$i] ?? 0),
                        ]);
                    }
                }

                foreach (($validated['new_id_barang'] ?? []) as $i => $idBarang) {
                    if ($idBarang === '' || $idBarang === null) {
                        continue;
                    }
                    $qty = (int) ($validated['new_qty'][$i] ?? 0);
                    if ($qty > 0) {
                        TransaksiDetail::create([
                            'id_header' => $header->id,
                            'id_barang' => (int) $idBarang,
                            'qty' => $qty,
                            'harga_satuan' => $this->parseHarga($validated['new_harga'][$i] ?? 0),
                        ]);
                    }
                }

                $sisa = TransaksiDetail::query()->where('id_header', $header->id)->count();
                if ($sisa === 0) {
                    $header->delete();
                }

                return $sisa;
            });
        } catch (\Throwable $e) {
            foreach ($newUploads as $f) {
                Storage::disk('public')->delete("persediaan-file/{$dir}/{$f}");
            }

            return back()->with('error', 'Gagal melakukan pembaruan: '.$e->getMessage());
        }

        $remaining = TransaksiDetail::query()->where('id_header', $header->id)->exists();
        if ($remaining) {
            return back()->with('success', 'Dokumen BAST & Rincian Uraian Barang berhasil diperbarui!');
        }

        return back()->with('success', 'Dokumen dihapus otomatis karena seluruh rincian item barang di dalamnya kosong.');
    }

    // --- helpers ---

    private function scopeByUser($query, bool $isAdmin, $userId, $userBidang, int $filterBpp): void
    {
        if (! $isAdmin) {
            $query->where(function ($q) use ($userId, $userBidang) {
                $q->where('created_by', $userId)
                    ->orWhere('pihak_terkait', $userBidang);
            });
        } elseif ($filterBpp > 0) {
            $query->where('created_by', $filterBpp);
        }

        $query->orderByDesc('tanggal_transaksi')->orderByDesc('id');
    }

    /** Build render-friendly per-doc array (row + details + lampiran URLs + totals). */
    private function buildDocs($headers): array
    {
        return $headers->map(function (TransaksiHeader $h) {
            $dir = $this->uploadDir($h->jenis_mutasi);
            $details = $h->details->sortBy('id')->values();

            return [
                'row' => $h,
                'total_item' => $details->count(),
                'total_nilai' => $details->sum(fn ($d) => $d->qty * $d->harga_satuan),
                'details' => $details,
                'files' => collect((array) $h->lampiran)->map(fn ($name) => [
                    'name' => $name,
                    'ext' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                    'url' => Storage::disk('public')->url("persediaan-file/{$dir}/{$name}"),
                ]),
            ];
        })->all();
    }

    private function uploadDir(string $jenisMutasi): string
    {
        return in_array($jenisMutasi, ['masuk', 'saldo_awal'], true) ? 'bast' : 'bon';
    }

    /**
     * Parse angka harga dari form. Fix bug 100x: parser lama cuma
     * str_replace('.', '', $x) — input "25.000,00" (format ribuan Indonesia +
     * 2 digit koma) jadi 2500000 (100x lipat) karena koma desimal diabaikan.
     * Aturan: ada ',' → '.' = ribuan & ',' = desimal; tak ada ',' → '.' jadi
     * ribuan bila pola ribuan valid (≥1 dot, tiap grup tepat 3 digit), sisanya
     * dianggap desimal titik biasa.
     */
    private function parseHarga(mixed $value): float
    {
        $v = trim((string) $value);

        if ($v === '' || ! preg_match('/^\d[\d\.,]*$/', $v)) {
            return 0.0;
        }

        if (str_contains($v, ',')) {
            return (float) str_replace(',', '.', str_replace('.', '', $v));
        }

        if (substr_count($v, '.') >= 1 && preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            return (float) str_replace('.', '', $v);
        }

        return (float) $v;
    }
}
