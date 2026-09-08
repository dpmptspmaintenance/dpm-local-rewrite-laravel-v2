<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Http\Controllers\Persediaan\Concerns\ComputesStock;
use App\Models\Persediaan\StokOpnameDetail;
use App\Models\Persediaan\StokOpnameHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokOpnameController extends Controller
{
    use ChecksPersediaanAccess, ComputesStock;

    public function index()
    {
        $this->abortIfNotPersediaanAdmin();

        $snapshot = $this->stockBatches();

        return view('persediaan.stok-opname.index', [
            'snapshot' => $snapshot,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate([
            'tanggal_opname' => ['required', 'date'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'ttd_kiri' => ['nullable', 'string', 'max:255'],
            'ttd_tengah' => ['nullable', 'string', 'max:255'],
            'ttd_kanan' => ['required', 'string', 'max:255'],
            'ttd_sekretaris' => ['nullable', 'string', 'max:255'],
            'status_simpan' => ['required', 'in:draft,final'],
            'id_barang' => ['required', 'array', 'min:1'],
            'id_barang.*' => ['integer'],
            'harga_satuan' => ['required', 'array', 'size:'.count($request->input('id_barang', []))],
            'harga_satuan.*' => ['required', 'numeric', 'min:0'],
            'stok_sistem' => ['required', 'array', 'size:'.count($request->input('id_barang', []))],
            'stok_sistem.*' => ['required', 'integer', 'min:0'],
            'stok_fisik' => ['required', 'array', 'size:'.count($request->input('id_barang', []))],
            'stok_fisik.*' => ['required', 'integer', 'min:0'],
            'alasan_selisih' => ['nullable', 'array'],
        ]);

        $statusSimpan = $validated['status_simpan'];
        $kodeOpname = 'SOP-'.date('YmdHis');
        $userId = (int) auth()->id();

        try {
            $header = DB::connection('persediaan')->transaction(function () use ($validated, $statusSimpan, $kodeOpname, $userId) {
                $h = StokOpnameHeader::create([
                    'kode_opname' => $kodeOpname,
                    'tanggal_opname' => $validated['tanggal_opname'],
                    'nama_kegiatan' => $validated['nama_kegiatan'],
                    'ttd_kiri' => $validated['ttd_kiri'] ?? null,
                    'ttd_tengah' => $validated['ttd_tengah'] ?? null,
                    'ttd_kanan' => $validated['ttd_kanan'],
                    'ttd_sekretaris' => $validated['ttd_sekretaris'] ?? null,
                    'status' => $statusSimpan,
                    'created_by' => $userId,
                ]);

                $selisihRows = [];
                foreach ($validated['id_barang'] as $i => $idBarang) {
                    $stkSis = (int) $validated['stok_sistem'][$i];
                    $stkFis = (int) $validated['stok_fisik'][$i];
                    $selisih = $stkFis - $stkSis;

                    StokOpnameDetail::create([
                        'id_opname_header' => $h->id,
                        'id_barang' => (int) $idBarang,
                        'harga_satuan' => (float) $validated['harga_satuan'][$i],
                        'stok_sistem' => $stkSis,
                        'stok_fisik' => $stkFis,
                        'selisih' => $selisih,
                        'alasan_selisih' => trim($validated['alasan_selisih'][$i] ?? '') ?: null,
                    ]);

                    if ($statusSimpan === 'final' && $selisih !== 0) {
                        $selisihRows[] = (object) [
                            'id_barang' => (int) $idBarang,
                            'qty' => $selisih,
                            'harga' => (float) $validated['harga_satuan'][$i],
                        ];
                    }
                }

                if ($statusSimpan === 'final' && $selisihRows !== []) {
                    $this->applyOpnameAdjustments(
                        $selisihRows,
                        $validated['tanggal_opname'],
                        [
                            'ttd_kiri' => $validated['ttd_kiri'] ?? null,
                            'ttd_tengah' => $validated['ttd_tengah'] ?? null,
                            'ttd_kanan' => $validated['ttd_kanan'],
                        ],
                        $userId
                    );
                }

                return $h;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memproses opname: '.$e->getMessage());
        }

        $label = $statusSimpan === 'final' ? 'Diterbitkan (Final) & Stok Terpenyesuaian' : 'Tersimpan sebagai Draft';
        $printLink = route('persediaan.cetak.baso', $header->id);
        $msg = 'Berhasil! Stok Opname <b>'.e($header->kode_opname).'</b> '.$label.'. '
            .'<a href="'.$printLink.'" target="_blank" class="btn btn-sm btn-dark ms-3 rounded-3"><i class="bi bi-printer"></i> Cetak BASO</a>';

        return back()->with('success', $msg);
    }
}
