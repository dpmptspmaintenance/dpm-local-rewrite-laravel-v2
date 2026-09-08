<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapIzinKbliController extends Controller
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

    private const RESIKO_NAMA = [
        'R' => 'Rendah',
        'MR' => 'Menengah Rendah',
        'MT' => 'Menengah Tinggi',
        'T' => 'Tinggi',
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $hasFilter = collect($filters)->contains(fn ($v) => $v !== '');

        return view('datakita.rekap-izin-kbli.index', [
            'rows' => $hasFilter ? $this->query($request)->get() : collect(),
            'hasFilter' => $hasFilter,
            'filters' => $filters,
            'bulanNama' => self::BULAN_NAMA,
            'resikoNama' => self::RESIKO_NAMA,
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->get();
        $filters = $this->filters($request);

        return response()->streamDownload(function () use ($rows, $filters) {
            echo view('datakita.rekap-izin-kbli.export', [
                'rows' => $rows,
                'filters' => $filters,
                'bulanNama' => self::BULAN_NAMA,
                'resikoNama' => self::RESIKO_NAMA,
            ])->render();
        }, 'Rekap List Izin Per KBLI.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function filters(Request $request): array
    {
        return [
            'tahun' => (string) $request->query('tahun', ''),
            'bulan' => (string) $request->query('bulan', ''),
            'kbli' => (string) $request->query('kbli', ''),
            'kd_resiko' => (string) $request->query('kd_resiko', ''),
        ];
    }

    private function query(Request $request)
    {
        $filters = $this->filters($request);

        $query = DB::table('2023_list_izin as l')
            ->leftJoin('data_master_kbli as m', 'l.kbli', '=', 'm.kbli');

        if ($filters['tahun'] !== '') {
            $query->whereRaw('YEAR(l.tanggal_proyek) = ?', [$filters['tahun']]);
        }
        if ($filters['bulan'] !== '') {
            $query->whereRaw('MONTH(l.tanggal_proyek) = ?', [$filters['bulan']]);
        }
        if ($filters['kbli'] !== '') {
            $query->where('l.kbli', $filters['kbli']);
        }
        if ($filters['kd_resiko'] !== '') {
            $query->where('l.kd_resiko', $filters['kd_resiko']);
        }

        return $query
            ->select([
                DB::raw('YEAR(l.tanggal_proyek) as tahun'),
                DB::raw('MONTH(l.tanggal_proyek) as bulan'),
                'l.kbli',
                'l.kd_resiko',
                'm.judul_kbli',
            ])
            ->selectRaw('COUNT(l.kbli) as jumlah_kbli')
            ->groupBy(DB::raw('YEAR(l.tanggal_proyek)'), DB::raw('MONTH(l.tanggal_proyek)'), 'l.kbli', 'l.kd_resiko', 'm.judul_kbli')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->orderBy('jumlah_kbli');
    }
}
