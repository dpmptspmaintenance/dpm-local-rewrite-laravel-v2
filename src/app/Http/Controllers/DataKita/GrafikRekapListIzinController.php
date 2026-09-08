<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikRekapListIzinController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'raw:YEAR(day_of_tanggal_terbit_oss)',
        'uraian_jenis_perizinan' => 'uraian_jenis_perizinan',
        'uraian_status_respon' => 'uraian_status_respon',
        'uraian_status_penanaman_modal' => 'uraian_status_penanaman_modal',
    ];

    private const SORTABLE = [
        'nama_perusahaan' => 'nama_perusahaan',
        'nib' => 'nib',
        'uraian_status_respon' => 'uraian_status_respon',
        'uraian_jenis_perizinan' => 'uraian_jenis_perizinan',
        'uraian_status_penanaman_modal' => 'uraian_status_penanaman_modal',
        'kbli' => 'kbli',
        'bulan' => 'raw:MONTH(day_of_tanggal_terbit_oss)',
    ];

    private const SELECT_COLUMNS = [
        'nama_perusahaan', 'nib', 'uraian_jenis_perizinan', 'uraian_status_respon',
        'uraian_status_penanaman_modal', 'kbli',
    ];

    public function index()
    {
        $rows = DB::table('2023_list_izin')
            ->selectRaw('YEAR(day_of_tanggal_terbit_oss) as tahun')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('day_of_tanggal_terbit_oss')
            ->groupBy('tahun')
            ->orderBy('tahun')
            ->get();

        return view('datakita.grafik-rekap-list-izin.index', [
            'tahun' => $rows->pluck('tahun'),
            'dataInvestasi' => $rows->pluck('total'),
        ]);
    }

    public function bulanan(Request $request)
    {
        $tahun = $request->query('tahun');

        $rows = DB::table('2023_list_izin')
            ->selectRaw('MONTH(day_of_tanggal_terbit_oss) as bulan')
            ->selectRaw('COUNT(*) as total')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$tahun])
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $data = array_fill(0, 12, 0);
        foreach ($rows as $row) {
            $bulan = ((int) $row->bulan) - 1;
            if ($bulan >= 0 && $bulan < 12) {
                $data[$bulan] = (int) $row->total;
            }
        }

        return response()->json(['data' => $data]);
    }

    public function table(Request $request)
    {
        $tahun = $request->query('tahun');

        $jenisPerizinanOptions = DB::table('2023_list_izin')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$tahun])
            ->select('uraian_jenis_perizinan')
            ->distinct()
            ->whereNotNull('uraian_jenis_perizinan')
            ->where('uraian_jenis_perizinan', '!=', '')
            ->orderBy('uraian_jenis_perizinan')
            ->pluck('uraian_jenis_perizinan');

        $statusResponOptions = DB::table('2023_list_izin')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$tahun])
            ->select('uraian_status_respon')
            ->distinct()
            ->whereNotNull('uraian_status_respon')
            ->where('uraian_status_respon', '!=', '')
            ->orderBy('uraian_status_respon')
            ->pluck('uraian_status_respon');

        $statusPenanamanModalOptions = DB::table('2023_list_izin')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$tahun])
            ->select('uraian_status_penanaman_modal')
            ->distinct()
            ->whereNotNull('uraian_status_penanaman_modal')
            ->where('uraian_status_penanaman_modal', '!=', '')
            ->orderBy('uraian_status_penanaman_modal')
            ->pluck('uraian_status_penanaman_modal');

        return view('datakita.grafik-rekap-list-izin.table', [
            'tahun' => $tahun,
            'jenisPerizinanOptions' => $jenisPerizinanOptions,
            'statusResponOptions' => $statusResponOptions,
            'statusPenanamanModalOptions' => $statusPenanamanModalOptions,
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('2023_list_izin');

        foreach (self::FILTER_MAP as $param => $column) {
            $value = $request->input($param);
            if ($value === null || $value === '') {
                continue;
            }
            if (str_starts_with($column, 'raw:')) {
                $query->whereRaw(substr($column, 4).' = ?', [$value]);
            } else {
                $query->where($column, $value);
            }
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
        $orderColumn = self::SORTABLE[$orderName] ?? 'day_of_tanggal_terbit_oss';

        $recordsTotal = DB::table('2023_list_izin')->count();
        $recordsFiltered = $this->baseQuery($request)->count();

        $query = $this->baseQuery($request)
            ->select(array_merge(self::SELECT_COLUMNS, [
                DB::raw('MONTH(day_of_tanggal_terbit_oss) as bulan'),
            ]));

        if (str_starts_with($orderColumn, 'raw:')) {
            $query->orderByRaw(substr($orderColumn, 4).' '.$orderDir);
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function export(Request $request)
    {
        $orderColumn = self::SORTABLE[$request->input('columnName', '')] ?? 'day_of_tanggal_terbit_oss';
        $orderDir = strtolower($request->input('columnSortOrder', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = $this->baseQuery($request)
            ->select(array_merge(self::SELECT_COLUMNS, [
                DB::raw('MONTH(day_of_tanggal_terbit_oss) as bulan'),
                DB::raw('YEAR(day_of_tanggal_terbit_oss) as tahun'),
            ]));

        if (str_starts_with($orderColumn, 'raw:')) {
            $query->orderByRaw(substr($orderColumn, 4).' '.$orderDir);
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.grafik-rekap-list-izin.export', ['rows' => $rows])->render();
        }, 'Data_Rekap_List_Izin.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
