<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Rekap10KbliTeratasController extends Controller
{
    private const BULAN_NAMA = [
        '1' => 'Januari',
        '2' => 'Februari',
        '3' => 'Maret',
        '4' => 'April',
        '5' => 'Mei',
        '6' => 'Juni',
        '7' => 'Juli',
        '8' => 'Agustus',
        '9' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];

    public function index(Request $request)
    {
        $tahun = (string) $request->query('tahun', '');
        $skalaUsaha = (string) $request->query('skala_usaha', '');

        $rows = collect();
        $hasFilter = $tahun !== '' || $skalaUsaha !== '';

        if ($hasFilter) {
            $rows = $this->query($request)->get();
        }

        return view('datakita.rekap-10-kbli-teratas.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'skalaUsaha' => $skalaUsaha,
            'hasFilter' => $hasFilter,
            'bulanNama' => self::BULAN_NAMA,
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.rekap-10-kbli-teratas.export', [
                'rows' => $rows,
                'bulanNama' => self::BULAN_NAMA,
            ])->render();
        }, 'Data 10 KBLI Teratas.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function query(Request $request)
    {
        $query = DB::table('2023_dp_proyek')
            ->select('tahun_pengambilan_data as tahun', 'kbli', 'judul_kbli')
            ->selectRaw('COUNT(kbli) as jumlah_kbli')
            ->selectRaw('SUM(jumlah_investasi3) as total_investasi');

        $tahun = (string) $request->query('tahun', '');
        if ($tahun !== '') {
            $query->where('tahun_pengambilan_data', $tahun);
        }

        $skalaUsaha = (string) $request->query('skala_usaha', '');
        if ($skalaUsaha === 'UMK') {
            $query->where(function ($q) {
                $q->where('uraian_skala_usaha', 'Usaha Mikro')
                    ->orWhere('uraian_skala_usaha', 'Usaha Kecil');
            });
        } elseif ($skalaUsaha === 'Non UMK') {
            $query->where('uraian_skala_usaha', 'Usaha Besar');
        }

        return $query
            ->groupBy('tahun_pengambilan_data', 'kbli', 'judul_kbli')
            ->orderByDesc('jumlah_kbli')
            ->limit(10);
    }
}
