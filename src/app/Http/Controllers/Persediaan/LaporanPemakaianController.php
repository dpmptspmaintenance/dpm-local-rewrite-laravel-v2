<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPemakaianController extends Controller
{
    use ChecksPersediaanAccess;

    private const LIST_BIDANG = ['Bidang 1', 'Bidang 2', 'monev', 'Bidang 3', 'Sekretariat', 'PM'];

    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $this->isAdmin($user);

        $bulan = (int) $request->input('bulan', date('n'));
        $bulan = ($bulan >= 1 && $bulan <= 12) ? $bulan : (int) date('n');
        $tahun = (int) $request->input('tahun', date('Y'));
        $tahun = ($tahun >= 2000 && $tahun <= 2100) ? $tahun : (int) date('Y');

        $filterBidang = trim((string) $request->input('bidang', ''));

        if ($isAdmin && $filterBidang !== '') {
            $bidang = $filterBidang;
            $labelBidang = $filterBidang;
        } elseif ($isAdmin) {
            $bidang = null;
            $labelBidang = 'Semua Bidang (Global)';
        } else {
            $bidang = $user->bidang;
            $labelBidang = (string) $user->bidang;
        }

        $tglAwal = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        $rows = DB::connection('persediaan')
            ->table('master_barang as b')
            ->join('master_satuan as s', 'b.id_satuan', '=', 's.id')
            ->leftJoin('transaksi_detail as d', 'b.id', '=', 'd.id_barang')
            ->leftJoin('transaksi_header as h', function ($join) use ($bidang, $tglAwal, $tglAkhir) {
                $join->on('d.id_header', '=', 'h.id')
                    ->where('h.status', 'disetujui')
                    ->where('h.jenis_mutasi', 'keluar');
                if ($bidang !== null) {
                    // For a bidang report, bon-keluar to this bidang counts as goods RECEIVED by the bidang.
                    $join->where('h.pihak_terkait', $bidang);
                }
            })
            ->selectRaw("
                b.id, b.nama_barang, s.nama_satuan, b.harga_satuan,
                COALESCE(SUM(CASE WHEN h.tanggal_transaksi < ? THEN d.qty ELSE 0 END), 0) AS saldo_awal,
                COALESCE(SUM(CASE WHEN h.tanggal_transaksi BETWEEN ? AND ? THEN d.qty ELSE 0 END), 0) AS masuk,
                0 AS keluar
            ", [$tglAwal, $tglAwal, $tglAkhir])
            ->groupBy('b.id', 'b.nama_barang', 's.nama_satuan', 'b.harga_satuan')
            ->havingRaw('(saldo_awal != 0 OR masuk != 0 OR keluar != 0)')
            ->orderBy('b.nama_barang')
            ->get()
            ->map(fn ($r) => (object) [
                'id' => (int) $r->id,
                'nama_barang' => $r->nama_barang,
                'nama_satuan' => $r->nama_satuan,
                'saldo_awal' => (int) $r->saldo_awal,
                'masuk' => (int) $r->masuk,
                'keluar' => (int) $r->keluar,
                'saldo_akhir' => (int) $r->saldo_awal + (int) $r->masuk - (int) $r->keluar,
            ]);

        return view('persediaan.laporan-pemakaian.index', [
            'rows' => $rows,
            'listBidang' => self::LIST_BIDANG,
            'bulanNama' => self::BULAN_NAMA,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'filterBidang' => $filterBidang,
            'labelBidang' => $labelBidang,
            'isAdmin' => $isAdmin,
        ]);
    }
}
