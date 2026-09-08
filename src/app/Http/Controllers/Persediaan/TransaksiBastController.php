<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Http\Controllers\Persediaan\Concerns\ComputesStock;
use App\Models\Persediaan\MasterBarang;
use App\Models\Persediaan\MasterSatuan;
use App\Models\Persediaan\TransaksiDetail;
use App\Models\Persediaan\TransaksiHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransaksiBastController extends Controller
{
    use ChecksPersediaanAccess, ComputesStock;

    public function index()
    {
        $this->abortIfNotAdminOrBpp();

        $barang = MasterBarang::query()
            ->join('master_satuan as s', 's.id', '=', 'master_barang.id_satuan')
            ->select('master_barang.id', 'master_barang.nama_barang', 's.nama_satuan', 'master_barang.harga_satuan')
            ->orderBy('master_barang.nama_barang')
            ->get();

        return view('persediaan.transaksi-bast.index', [
            'barang' => $barang,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotAdminOrBpp();

        $user = auth()->user();
        $isAdmin = $this->isAdmin($user);

        $validated = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'jenis_mutasi' => ['required', 'in:masuk,saldo_awal'],
            'pihak_terkait' => ['required', 'string', 'max:255'],
            'ttd_kiri' => ['nullable', 'string', 'max:255'],
            'ttd_tengah' => ['nullable', 'string', 'max:255'],
            'ttd_kanan' => ['required', 'string', 'max:255'],
            'action_type' => ['required', 'in:draft,submit'],
            'id_barang_array' => ['required', 'array', 'min:1'],
            'id_barang_array.*' => ['required', 'integer'],
            'qty_array' => ['required', 'array', 'size:'.count($request->input('id_barang_array', []))],
            'qty_array.*' => ['required', 'integer', 'min:1'],
            'harga_array' => ['required', 'array', 'size:'.count($request->input('id_barang_array', []))],
            'harga_array.*' => ['required', 'numeric', 'min:0'],
            'lampiran_berkas' => ['required', 'array'],
            'lampiran_berkas.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $tgl = $validated['tanggal_transaksi'];

        if ($this->bulanDikunci($tgl, $isAdmin)) {
            return back()->with('error', 'Gagal: Pelaporan untuk periode bulan tersebut telah dikunci!')->withInput();
        }

        // Persist lampiran under persediaan-file/bast on the public disk.
        $savedFiles = [];
        foreach ($request->file('lampiran_berkas') as $file) {
            $name = time().'_BAST_'.uniqid().'.'.$file->guessExtension();
            $file->storeAs('persediaan-file/bast/'.$name, '', 'public');
            $savedFiles[] = $name;
        }

        $jenis = $validated['jenis_mutasi'];
        $statusTrx = $validated['action_type'] === 'draft'
            ? 'draft'
            : ($isAdmin ? 'disetujui' : 'menunggu');

        $kodeTrx = 'IN-'.date('YmdHis');

        try {
            $header = DB::connection('persediaan')->transaction(function () use ($validated, $jenis, $statusTrx, $savedFiles, $kodeTrx, $user) {
                $h = TransaksiHeader::create([
                    'kode_transaksi' => $kodeTrx,
                    'jenis_mutasi' => $jenis,
                    'tanggal_transaksi' => $validated['tanggal_transaksi'],
                    'pihak_terkait' => $validated['pihak_terkait'],
                    'ttd_kiri' => $validated['ttd_kiri'] ?? null,
                    'ttd_tengah' => $validated['ttd_tengah'] ?? null,
                    'ttd_kanan' => $validated['ttd_kanan'],
                    'ttd_bmd' => null,
                    'status' => $statusTrx,
                    'lampiran' => $savedFiles,
                    'created_by' => $user->id,
                ]);

                foreach ($validated['id_barang_array'] as $i => $idBarang) {
                    TransaksiDetail::create([
                        'id_header' => $h->id,
                        'id_barang' => (int) $idBarang,
                        'qty' => (int) $validated['qty_array'][$i],
                        'harga_satuan' => (float) $validated['harga_array'][$i],
                    ]);
                }

                return $h;
            });
        } catch (\Throwable $e) {
            // Best-effort cleanup of already-uploaded files.
            foreach ($savedFiles as $f) {
                Storage::disk('public')->delete('persediaan-file/bast/'.$f);
            }

            return back()->withInput()->with('error', 'Gagal memproses transaksi: '.$e->getMessage());
        }

        if ($statusTrx === 'draft') {
            return back()->with('success', '<i class="bi bi-bookmark-check-fill me-2"></i>Dokumen berhasil disimpan sebagai <b>Draft</b>. Anda dapat memeriksanya di halaman Riwayat.');
        }

        $printLink = route('persediaan.cetak.bast', $header->id);
        $msg = '<b>Berhasil Disimpan!</b> Dokumen BAST terdaftar dengan nomor: <b>'.e($header->kode_transaksi).'</b>. '
            .'<a href="'.$printLink.'" target="_blank" class="btn btn-sm btn-success ms-3 rounded-pill px-3"><i class="bi bi-printer me-1"></i> Cetak Dokumen</a>';

        return back()->with('success', $msg);
    }
}
