<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Http\Controllers\Persediaan\Concerns\ComputesStock;
use App\Models\Persediaan\TransaksiDetail;
use App\Models\Persediaan\TransaksiHeader;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransaksiBonController extends Controller
{
    use ChecksPersediaanAccess, ComputesStock;

    public function index()
    {
        $this->abortIfNotPersediaanAdmin();

        $pegawai = User::query()
            ->where('is_aktif', 1)
            ->orderBy('nama')
            ->get(['nama', 'bidang']);

        $stokBatch = $this->stockBatches();

        return view('persediaan.transaksi-bon.index', [
            'pegawai' => $pegawai,
            'stokBatch' => $stokBatch,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $idBarang = $request->input('id_barang_keluar', []);
        $qty = $request->input('qty_keluar', []);

        $validated = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'ttd_kanan' => ['required', 'string', 'max:255'],
            'pihak_terkait' => ['required', 'string', 'max:255'],
            'alasan_pengambilan' => ['required', 'string'],
            'ttd_sekretaris' => ['nullable', 'string', 'max:255'],
            'ttd_kiri' => ['nullable', 'string', 'max:255'],
            'ttd_tengah' => ['nullable', 'string', 'max:255'],
            'id_barang_keluar' => ['required', 'array'],
            'id_barang_keluar.*' => ['integer'],
            'qty_keluar' => ['required', 'array', 'size:'.count($idBarang)],
            'qty_keluar.*' => ['required', 'integer', 'min:0'],
            'harga_keluar' => ['required', 'array', 'size:'.count($idBarang)],
            'harga_keluar.*' => ['required', 'numeric', 'min:0'],
            'lampiran_berkas' => ['required', 'array'],
            'lampiran_berkas.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        // Legacy filtered out rows with qty <= 0 server-side; only those are written.
        $items = [];
        foreach ($validated['id_barang_keluar'] as $i => $idBarangItem) {
            $qtyItem = (int) $validated['qty_keluar'][$i];
            if ($qtyItem > 0) {
                $items[] = [
                    'id_barang' => (int) $idBarangItem,
                    'qty' => $qtyItem,
                    'harga' => (float) $validated['harga_keluar'][$i],
                ];
            }
        }

        if (empty($items)) {
            return back()->with('error', 'Gagal! Kuantitas alokasi barang belum diisi satupun.')->withInput();
        }

        // Admin-only feature → no month-lock restriction (admins always bypass the lock).
        $savedFiles = [];
        foreach ($request->file('lampiran_berkas') as $file) {
            $name = time().'_OUT_'.uniqid().'.'.$file->guessExtension();
            $file->storeAs('persediaan-file/bon/'.$name, '', 'public');
            $savedFiles[] = $name;
        }

        $kodeTrx = 'OUT-'.date('YmdHis');

        try {
            $header = DB::connection('persediaan')->transaction(function () use ($validated, $items, $savedFiles, $kodeTrx) {
                $h = TransaksiHeader::create([
                    'kode_transaksi' => $kodeTrx,
                    'jenis_mutasi' => 'keluar',
                    'tanggal_transaksi' => $validated['tanggal_transaksi'],
                    'pihak_terkait' => $validated['pihak_terkait'],
                    'alasan_pengambilan' => $validated['alasan_pengambilan'],
                    'ttd_kiri' => $validated['ttd_kiri'] ?? null,
                    'ttd_tengah' => $validated['ttd_tengah'] ?? null,
                    'ttd_kanan' => $validated['ttd_kanan'],
                    'ttd_sekretaris' => $validated['ttd_sekretaris'] ?? null,
                    'status' => 'disetujui',
                    'lampiran' => $savedFiles,
                    'created_by' => auth()->id(),
                ]);

                foreach ($items as $item) {
                    TransaksiDetail::create([
                        'id_header' => $h->id,
                        'id_barang' => $item['id_barang'],
                        'qty' => $item['qty'],
                        'harga_satuan' => $item['harga'],
                    ]);
                }

                return $h;
            });
        } catch (\Throwable $e) {
            foreach ($savedFiles as $f) {
                Storage::disk('public')->delete('persediaan-file/bon/'.$f);
            }

            return back()->withInput()->with('error', 'Gagal eksekusi: '.$e->getMessage());
        }

        $printLink = route('persediaan.cetak.bon', $header->id);
        $msg = '<b>Berhasil!</b> Bon permintaan barang keluar tersimpan dengan nomor <b>'.e($header->kode_transaksi).'</b>. '
            .'<a href="'.$printLink.'" target="_blank" class="btn btn-sm btn-danger ms-3"><i class="bi bi-printer"></i> Cetak Bon Permintaan</a>';

        return back()->with('success', $msg);
    }
}
