<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapKbliKecamatanController extends Controller
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

    private const SKALA_USAHA = ['Usaha Mikro', 'Usaha Kecil', 'Usaha Menengah', 'Usaha Besar'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $hasFilter = collect($filters)->contains(fn ($v) => $v !== '');

        return view('datakita.rekap-kbli-kecamatan.index', [
            'rows' => $hasFilter ? $this->query($request)->get() : collect(),
            'hasFilter' => $hasFilter,
            'filters' => $filters,
            'bulanNama' => self::BULAN_NAMA,
            'skalaUsahaOptions' => self::SKALA_USAHA,
            'kecamatanOptions' => DB::table('dasi_master_kecamatan')->orderBy('Kecamatan')->pluck('Kecamatan'),
            'kelurahanOptions' => DB::table('2023_dp_proyek')->select('kelurahan_usaha')->distinct()->whereNotNull('kelurahan_usaha')->orderBy('kelurahan_usaha')->pluck('kelurahan_usaha'),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->query($request)->get();
        $filters = $this->filters($request);

        return response()->streamDownload(function () use ($rows, $filters) {
            echo view('datakita.rekap-kbli-kecamatan.export', [
                'rows' => $rows,
                'filters' => $filters,
                'bulanNama' => self::BULAN_NAMA,
            ])->render();
        }, 'Data KBLI Perkecamatan.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function filters(Request $request): array
    {
        return [
            'tahun' => (string) $request->query('tahun', ''),
            'bulan' => (string) $request->query('bulan', ''),
            'kecamatan' => (string) $request->query('kecamatan', ''),
            'kelurahan' => (string) $request->query('kelurahan', ''),
            'judul_kbli' => (string) $request->query('judul_kbli', ''),
            'skala_usaha' => (string) $request->query('skala_usaha', ''),
        ];
    }

    private function query(Request $request)
    {
        $filters = $this->filters($request);

        $query = DB::table('2023_dp_proyek');

        if ($filters['tahun'] !== '') {
            $query->where('tahun_pengambilan_data', $filters['tahun']);
        }
        if ($filters['bulan'] !== '') {
            $query->where('bulan_pengambilan_data', $filters['bulan']);
        }
        if ($filters['kecamatan'] !== '') {
            $query->where('kecamatan_usaha', $filters['kecamatan']);
        }
        if ($filters['kelurahan'] !== '') {
            $query->where('kelurahan_usaha', $filters['kelurahan']);
        }
        if ($filters['judul_kbli'] !== '') {
            $query->where('judul_kbli', 'like', '%'.$filters['judul_kbli'].'%');
        }
        if ($filters['skala_usaha'] !== '') {
            $query->where('uraian_skala_usaha', 'like', '%'.$filters['skala_usaha'].'%');
        }

        return $query
            ->select([
                'tahun_pengambilan_data as tahun',
                'bulan_pengambilan_data as bulan',
                'kecamatan_usaha as kecamatan',
                'kelurahan_usaha as kelurahan',
                'kbli',
                'judul_kbli',
            ])
            ->selectRaw('COUNT(kbli) as jumlah_kbli')
            ->selectRaw('SUM(jumlah_investasi3) as total_investasi')
            ->groupBy('tahun_pengambilan_data', 'bulan_pengambilan_data', 'kbli', 'judul_kbli', 'kecamatan_usaha', 'kelurahan_usaha')
            ->orderBy('tahun_pengambilan_data')
            ->orderByRaw('CAST(bulan_pengambilan_data AS UNSIGNED)')
            ->orderBy('jumlah_kbli');
    }
}
