<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikRekapKbliController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'tahun_pengambilan_data',
        'uraian_skala_usaha' => 'uraian_skala_usaha',
        'uraian_risiko_proyek' => 'uraian_risiko_proyek',
        'uraian_jenis_proyek' => 'uraian_jenis_proyek',
        'uraian_status_penanaman_modal' => 'uraian_status_penanaman_modal',
    ];

    private const SORTABLE = [
        'kbli' => 'kbli',
        'jml_tki' => 'raw:COALESCE(SUM(tki), 0)',
        'jml_investasi' => 'raw:COALESCE(SUM(jumlah_investasi3), 0)',
    ];

    public function index()
    {
        $rows = DB::table('2023_dp_proyek')
            ->select('tahun_pengambilan_data')
            ->selectRaw('COUNT(DISTINCT kbli) as total')
            ->whereNotNull('kbli')
            ->whereNotNull('tahun_pengambilan_data')
            ->groupBy('tahun_pengambilan_data')
            ->orderBy('tahun_pengambilan_data')
            ->get();

        return view('datakita.grafik-rekap-kbli.index', [
            'tahun' => $rows->pluck('tahun_pengambilan_data'),
            'dataInvestasi' => $rows->pluck('total'),
        ]);
    }

    public function bulanan(Request $request)
    {
        $tahun = $request->query('tahun');

        $rows = DB::table('2023_dp_proyek')
            ->selectRaw('CAST(bulan_pengambilan_data AS UNSIGNED) as bln')
            ->selectRaw('COUNT(DISTINCT kbli) as total')
            ->where('tahun_pengambilan_data', $tahun)
            ->whereNotNull('kbli')
            ->whereNotNull('bulan_pengambilan_data')
            ->groupBy('bln')
            ->orderBy('bln')
            ->get();

        $data = array_fill(0, 12, 0);
        foreach ($rows as $row) {
            $bulan = ((int) $row->bln) - 1;
            if ($bulan >= 0 && $bulan < 12) {
                $data[$bulan] = (int) $row->total;
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

        return view('datakita.grafik-rekap-kbli.table', [
            'tahun' => $tahun,
            'uraianSkalaUsahaOptions' => $options('uraian_skala_usaha'),
            'uraianRisikoProyekOptions' => $options('uraian_risiko_proyek'),
            'uraianJenisProyekOptions' => $options('uraian_jenis_proyek'),
            'uraianStatusPenanamanModalOptions' => $options('uraian_status_penanaman_modal'),
        ]);
    }

    private function filterQuery(Request $request)
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

        $tahun = $request->input('tahun');
        $orderName = $request->input('order.0.name', '');
        $orderDir = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // total record tanpa filter (distinct kbli per tahun terpilih)
        $recordsTotal = DB::table('2023_dp_proyek')
            ->where('tahun_pengambilan_data', $tahun)
            ->whereNotNull('kbli')
            ->distinct()
            ->count('kbli');

        $recordsFiltered = (clone $this->filterQuery($request))
            ->whereNotNull('kbli')
            ->distinct()
            ->count('kbli');

        $query = $this->filterQuery($request)
            ->select('kbli')
            ->selectRaw('COALESCE(SUM(tki), 0) as jml_tki')
            ->selectRaw('COALESCE(SUM(jumlah_investasi3), 0) as jml_investasi')
            ->groupBy('kbli');

        if ($orderName === 'jml_tki') {
            $query->orderByRaw('COALESCE(SUM(tki), 0) '.$orderDir);
        } elseif ($orderName === 'jml_investasi') {
            $query->orderByRaw('COALESCE(SUM(jumlah_investasi3), 0) '.$orderDir);
        } else {
            $query->orderBy('kbli', $orderDir);
        }

        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function export(Request $request)
    {
        $orderName = $request->input('columnName', '');
        $orderDir = strtolower($request->input('columnSortOrder', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = $this->filterQuery($request)
            ->select('kbli')
            ->selectRaw('COALESCE(SUM(tki), 0) as jml_tki')
            ->selectRaw('COALESCE(SUM(jumlah_investasi3), 0) as jml_investasi')
            ->groupBy('kbli');

        if ($orderName === 'jml_tki') {
            $query->orderByRaw('COALESCE(SUM(tki), 0) '.$orderDir);
        } elseif ($orderName === 'jml_investasi') {
            $query->orderByRaw('COALESCE(SUM(jumlah_investasi3), 0) '.$orderDir);
        } else {
            $query->orderBy('kbli', $orderDir);
        }

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.grafik-rekap-kbli.export', ['rows' => $rows])->render();
        }, 'Data_Rekap_KBLI.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
