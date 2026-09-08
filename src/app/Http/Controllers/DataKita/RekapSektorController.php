<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapSektorController extends Controller
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
        $filters = $this->filters($request);
        $hasFilter = collect($filters)->contains(fn ($v) => $v !== '');

        return view('datakita.rekap-sektor.index', [
            'rows' => $hasFilter ? $this->query($request)->get() : collect(),
            'hasFilter' => $hasFilter,
            'filters' => $filters,
            'bulanNama' => self::BULAN_NAMA,
            'sektorPembina' => DB::table('2023_dp_proyek')->select('sektor_pembina')->distinct()->whereNotNull('sektor_pembina')->orderBy('sektor_pembina')->pluck('sektor_pembina'),
            'kecamatan' => DB::table('dasi_master_kecamatan')->orderBy('Kecamatan')->pluck('Kecamatan'),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->get();
        $filters = $this->filters($request);

        return response()->streamDownload(function () use ($rows, $filters) {
            echo view('datakita.rekap-sektor.export', [
                'rows' => $rows,
                'filters' => $filters,
                'bulanNama' => self::BULAN_NAMA,
            ])->render();
        }, 'Rekap Data Proyek Persektor.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function filters(Request $request): array
    {
        return [
            'tahun' => (string) $request->query('tahun', ''),
            'bulan' => (string) $request->query('bulan', ''),
            'sektor_pembina' => (string) $request->query('sektor_pembina', ''),
            'kecamatan' => (string) $request->query('kecamatan', ''),
        ];
    }

    private function baseQuery(Request $request)
    {
        $filters = $this->filters($request);

        $query = DB::table('2023_dp_proyek');

        if ($filters['tahun'] !== '') {
            $query->where('tahun_pengambilan_data', $filters['tahun']);
        }
        if ($filters['bulan'] !== '') {
            $query->where('bulan_pengambilan_data', $filters['bulan']);
        }
        if ($filters['sektor_pembina'] !== '') {
            $query->where('sektor_pembina', $filters['sektor_pembina']);
        }
        if ($filters['kecamatan'] !== '') {
            $query->where('kecamatan_usaha', $filters['kecamatan']);
        }

        return $query;
    }

    private function query(Request $request)
    {
        $withKecamatan = (string) $request->query('kecamatan', '') !== '';

        $groupCols = ['tahun_pengambilan_data', 'bulan_pengambilan_data', 'sektor_pembina', 'kbli', 'judul_kbli'];
        if ($withKecamatan) {
            $groupCols[] = 'kecamatan_usaha';
        }

        $sub = $this->baseQuery($request)
            ->select($groupCols)
            ->selectRaw('COUNT(*) as total_per_kbli')
            ->groupBy($groupCols);

        $outer = DB::query()->fromSub($sub, 'sub')
            ->select([
                'tahun_pengambilan_data as tahun',
                'bulan_pengambilan_data as bulan',
                'sektor_pembina',
            ])
            ->selectRaw('SUM(total_per_kbli) as jumlah_sektor_pembina')
            ->selectRaw("GROUP_CONCAT(DISTINCT CONCAT(kbli, ' - ', judul_kbli, ' (', total_per_kbli, ')') SEPARATOR '<br>') as kbli_detail");

        if ($withKecamatan) {
            $outer->selectRaw("GROUP_CONCAT(DISTINCT kecamatan_usaha SEPARATOR ', ') as kecamatan");
        }

        return $outer
            ->groupBy('tahun_pengambilan_data', 'bulan_pengambilan_data', 'sektor_pembina')
            ->orderBy('tahun_pengambilan_data')
            ->orderByRaw('CAST(bulan_pengambilan_data AS UNSIGNED)');
    }
}
