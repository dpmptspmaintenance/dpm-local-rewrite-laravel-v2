<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapNibController extends Controller
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
        $bulan = (string) $request->query('bulan', '');
        $hasFilter = $tahun !== '' || $bulan !== '';

        $rows = $hasFilter ? $this->rows($request) : collect();

        return view('datakita.rekap-nib.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'hasFilter' => $hasFilter,
            'bulanNama' => self::BULAN_NAMA,
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->rows($request);
        $bulan = (string) $request->query('bulan', '');

        return response()->streamDownload(function () use ($rows, $bulan) {
            echo view('datakita.rekap-nib.export', [
                'rows' => $rows,
                'bulan' => $bulan,
                'bulanNama' => self::BULAN_NAMA,
            ])->render();
        }, 'Data Rekap Jumlah NIB Baru.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function rows(Request $request)
    {
        $tahun = (string) $request->query('tahun', '');
        $bulan = (string) $request->query('bulan', '');

        $investasiPerNib = DB::table('2023_dp_proyek')
            ->select('nib', 'tahun_pengambilan_data', 'bulan_pengambilan_data')
            ->selectRaw('SUM(jumlah_investasi3) as jumlah_investasi')
            ->groupBy('nib', 'tahun_pengambilan_data', 'bulan_pengambilan_data');

        $query = DB::table('2023_dp_nib_kantor as k')
            ->leftJoinSub($investasiPerNib, 'p', function ($join) {
                $join->on('k.nib', '=', 'p.nib')
                    ->on('k.tahun_pengambilan_data', '=', 'p.tahun_pengambilan_data')
                    ->on('k.bulan_pengambilan_data', '=', 'p.bulan_pengambilan_data');
            })
            ->select('k.tahun_pengambilan_data as tahun', 'k.bulan_pengambilan_data as bulan')
            ->selectRaw('COUNT(DISTINCT k.nib) as total_nib')
            ->selectRaw('COALESCE(SUM(p.jumlah_investasi), 0) as jumlah_investasi');

        if ($tahun !== '') {
            $query->where('k.tahun_pengambilan_data', $tahun);
        }
        if ($bulan !== '') {
            $query->where('k.bulan_pengambilan_data', $bulan);
        }

        $rows = $query
            ->groupBy('k.tahun_pengambilan_data', 'k.bulan_pengambilan_data')
            ->orderBy('k.tahun_pengambilan_data')
            ->orderByRaw('CAST(k.bulan_pengambilan_data AS UNSIGNED)')
            ->get();

        if ($bulan === '') {
            $previous = null;
            foreach ($rows as $row) {
                if ($previous) {
                    $row->nib_change = $previous->total_nib > 0
                        ? round((($row->total_nib - $previous->total_nib) / $previous->total_nib) * 100, 2)
                        : 0;
                    $row->investasi_change = $previous->jumlah_investasi > 0
                        ? round((($row->jumlah_investasi - $previous->jumlah_investasi) / $previous->jumlah_investasi) * 100, 2)
                        : 0;
                } else {
                    $row->nib_change = 0;
                    $row->investasi_change = 0;
                }
                $previous = $row;
            }
        }

        return $rows;
    }
}
