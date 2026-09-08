<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapJenisIzinController extends Controller
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
        $rows = $filters['tahun'] !== '' ? $this->query($request)->get() : collect();

        return view('datakita.rekap-jenis-izin.index', [
            'groups' => $rows->groupBy('kd_resiko')->map(fn ($g) => $g->groupBy('uraian_jenis_perizinan')),
            'hasRows' => $rows->isNotEmpty(),
            'filters' => $filters,
            'bulanNama' => self::BULAN_NAMA,
            'resikoNama' => self::RESIKO_NAMA,
            'availableYears' => DB::table('2023_list_izin')->whereNotNull('tanggal_proyek')->selectRaw('DISTINCT YEAR(tanggal_proyek) as tahun_list')->orderByDesc('tahun_list')->pluck('tahun_list'),
            'jenisPerizinanOptions' => DB::table('2023_list_izin')->select('uraian_jenis_perizinan')->distinct()->whereNotNull('uraian_jenis_perizinan')->orderBy('uraian_jenis_perizinan')->pluck('uraian_jenis_perizinan'),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $filters['tahun'] !== '' ? $this->query($request)->get() : collect();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.rekap-jenis-izin.export', [
                'groups' => $rows->groupBy('kd_resiko')->map(fn ($g) => $g->groupBy('uraian_jenis_perizinan')),
                'hasRows' => $rows->isNotEmpty(),
                'bulanNama' => self::BULAN_NAMA,
                'resikoNama' => self::RESIKO_NAMA,
            ])->render();
        }, 'Rekap List Jenis Izin Dan Nama Dokumen.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function filters(Request $request): array
    {
        return [
            'tahun' => (string) $request->query('tahun', ''),
            'bulan' => (string) $request->query('bulan', ''),
            'kd_resiko' => (string) $request->query('kd_resiko', ''),
            'uraian_jenis_perizinan' => (string) $request->query('uraian_jenis_perizinan', ''),
        ];
    }

    private function query(Request $request)
    {
        $filters = $this->filters($request);

        $query = DB::table('2023_list_izin')
            ->whereRaw('YEAR(tanggal_proyek) = ?', [$filters['tahun']]);

        if ($filters['bulan'] !== '') {
            $query->whereRaw('MONTH(tanggal_proyek) = ?', [$filters['bulan']]);
        }
        if ($filters['kd_resiko'] !== '') {
            $query->where('kd_resiko', $filters['kd_resiko']);
        }
        if ($filters['uraian_jenis_perizinan'] !== '') {
            $query->where('uraian_jenis_perizinan', $filters['uraian_jenis_perizinan']);
        }

        return $query
            ->select([
                DB::raw('YEAR(tanggal_proyek) as tahun'),
                DB::raw('MONTH(tanggal_proyek) as bulan'),
                'kd_resiko',
                'uraian_jenis_perizinan',
                'uraian_status_respon',
            ])
            ->selectRaw('COUNT(id) as jumlah_izin')
            ->groupBy(DB::raw('YEAR(tanggal_proyek)'), DB::raw('MONTH(tanggal_proyek)'), 'kd_resiko', 'uraian_jenis_perizinan', 'uraian_status_respon')
            ->orderByDesc('tahun')
            ->orderBy('bulan')
            ->orderBy('kd_resiko')
            ->orderBy('uraian_jenis_perizinan');
    }
}
