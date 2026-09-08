<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikRekapJumlahInvestasiController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'tahun_pengambilan_data',
        'uraian_skala_usaha' => 'uraian_skala_usaha',
        'uraian_risiko_proyek' => 'uraian_risiko_proyek',
        'uraian_jenis_proyek' => 'uraian_jenis_proyek',
        'uraian_status_penanaman_modal' => 'uraian_status_penanaman_modal',
    ];

    private const SORTABLE = [
        'uraian_skala_usaha' => 'uraian_skala_usaha',
        'uraian_risiko_proyek' => 'uraian_risiko_proyek',
        'uraian_jenis_proyek' => 'uraian_jenis_proyek',
        'uraian_status_penanaman_modal' => 'uraian_status_penanaman_modal',
        'kelurahan_usaha' => 'kelurahan_usaha',
        'kecamatan_usaha' => 'kecamatan_usaha',
        'bulan_pengambilan_data' => 'raw:CAST(bulan_pengambilan_data AS UNSIGNED)',
        'kbli' => 'kbli',
        'jml_investasi' => 'jumlah_investasi3',
    ];

    private const SELECT_COLUMNS = [
        'uraian_skala_usaha', 'uraian_risiko_proyek', 'uraian_jenis_proyek',
        'uraian_status_penanaman_modal', 'kelurahan_usaha', 'kecamatan_usaha',
        'tahun_pengambilan_data', 'kbli',
    ];

    private function baseSelect()
    {
        return DB::table('2023_dp_proyek')
            ->select(self::SELECT_COLUMNS)
            ->selectRaw('CAST(bulan_pengambilan_data AS UNSIGNED) as bulan_pengambilan_data')
            ->selectRaw('jumlah_investasi3 as jml_investasi');
    }

    public function index()
    {
        $rows = DB::table('2023_dp_proyek')
            ->select('tahun_pengambilan_data')
            ->selectRaw('COALESCE(SUM(jumlah_investasi3), 0) as total')
            ->whereNotNull('jumlah_investasi3')
            ->whereNotNull('tahun_pengambilan_data')
            ->groupBy('tahun_pengambilan_data')
            ->orderBy('tahun_pengambilan_data')
            ->get();

        return view('datakita.grafik-rekap-jumlah-investasi.index', [
            'tahun' => $rows->pluck('tahun_pengambilan_data'),
            'dataInvestasi' => $rows->pluck('total'),
        ]);
    }

    public function bulanan(Request $request)
    {
        $tahun = $request->query('tahun');

        $rows = DB::table('2023_dp_proyek')
            ->selectRaw('CAST(bulan_pengambilan_data AS UNSIGNED) as bln')
            ->selectRaw('COALESCE(SUM(jumlah_investasi3), 0) as total')
            ->where('tahun_pengambilan_data', $tahun)
            ->whereNotNull('jumlah_investasi3')
            ->whereNotNull('bulan_pengambilan_data')
            ->groupBy('bln')
            ->orderBy('bln')
            ->get();

        $data = array_fill(0, 12, 0);
        foreach ($rows as $row) {
            $bulan = ((int) $row->bln) - 1;
            if ($bulan >= 0 && $bulan < 12) {
                $data[$bulan] = (float) $row->total;
            }
        }

        return response()->json(['data' => $data]);
    }

    public function table(Request $request)
    {
        $tahun = $request->query('tahun');

        $options = function (string $column) use ($tahun) {
            return DB::table('2023_dp_proyek')
                ->where('tahun_pengambilan_data', $tahun)
                ->select($column)
                ->distinct()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->orderBy($column)
                ->pluck($column);
        };

        return view('datakita.grafik-rekap-jumlah-investasi.table', [
            'tahun' => $tahun,
            'uraianSkalaUsahaOptions' => $options('uraian_skala_usaha'),
            'uraianRisikoProyekOptions' => $options('uraian_risiko_proyek'),
            'uraianJenisProyekOptions' => $options('uraian_jenis_proyek'),
            'uraianStatusPenanamanModalOptions' => $options('uraian_status_penanaman_modal'),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('2023_dp_proyek');

        foreach (self::FILTER_MAP as $param => $column) {
            $value = $request->input($param);
            if ($value === null || $value === '') {
                continue;
            }
            $query->where($column, $value);
        }

        $search = trim((string) $request->input('search.value', $request->input('searchValue', '')));
        if ($search !== '') {
            $query->where('kbli', 'like', "%{$search}%");
        }

        return $query;
    }

    public function ajaxData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $orderName = $request->input('order.0.name', '');
        $orderDir = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $orderColumn = self::SORTABLE[$orderName] ?? 'raw:CAST(bulan_pengambilan_data AS UNSIGNED)';

        $recordsTotal = DB::table('2023_dp_proyek')
            ->whereNotNull('tahun_pengambilan_data')
            ->count();
        $recordsFiltered = $this->baseQuery($request)->count();

        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->selectRaw('CAST(bulan_pengambilan_data AS UNSIGNED) as bulan_pengambilan_data')
            ->selectRaw('jumlah_investasi3 as jml_investasi')
            ->orderByRaw(preg_replace('/^raw:/', '', $orderColumn).' '.$orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function export(Request $request)
    {
        $orderName = $request->input('columnName', '');
        $orderDir = strtolower($request->input('columnSortOrder', 'asc')) === 'desc' ? 'desc' : 'asc';
        $orderColumn = self::SORTABLE[$orderName] ?? 'raw:CAST(bulan_pengambilan_data AS UNSIGNED)';

        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->selectRaw('CAST(bulan_pengambilan_data AS UNSIGNED) as bulan_pengambilan_data')
            ->selectRaw('jumlah_investasi3 as jml_investasi')
            ->orderByRaw(preg_replace('/^raw:/', '', $orderColumn).' '.$orderDir)
            ->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.grafik-rekap-jumlah-investasi.export', ['rows' => $rows])->render();
        }, 'Data_Rekap_Jumlah_Investasi.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
