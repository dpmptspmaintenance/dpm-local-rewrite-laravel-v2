<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerusahaanController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'raw:YEAR(day_of_tanggal_terbit_oss)',
        'bulan' => 'raw:MONTH(day_of_tanggal_terbit_oss)',
        'status_penanaman_modal' => 'status_penanaman_modal',
        'uraian_jenis_perusahaan' => 'uraian_jenis_perusahaan',
    ];

    private const SORTABLE = [
        'nama_perusahaan' => 'nama_perusahaan',
        'day_of_tanggal_terbit_oss' => 'day_of_tanggal_terbit_oss',
    ];

    private const SELECT_COLUMNS = [
        'nama_perusahaan', 'nib', 'kelurahan', 'kecamatan', 'email',
        'alamat_perusahaan', 'day_of_tanggal_terbit_oss',
        'status_penanaman_modal', 'uraian_jenis_perusahaan',
    ];

    public function index()
    {
        return view('datakita.perusahaan.index', [
            'statusPenanamanModal' => DB::table('dasi_master_status_penanaman_modal')->get(),
            'jenisPerusahaan' => DB::table('dasi_master_jenis_perusahaan')->where('is_aktif', 1)->get(),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('2023_dp_nib_kantor');

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
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('nama_perusahaan', 'like', $like)
                    ->orWhere('nib', 'like', $like)
                    ->orWhere('alamat_perusahaan', 'like', $like);
            });
        }

        return $query;
    }

    public function ajaxData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $orderName = $request->input('order.0.name', '');
        $orderDir = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = self::SORTABLE[$orderName] ?? 'day_of_tanggal_terbit_oss';

        $recordsTotal = DB::table('2023_dp_nib_kantor')->count();
        $recordsFiltered = $this->baseQuery($request)->count();

        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->orderBy($orderColumn, $orderDir)
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
        $orderColumn = self::SORTABLE[$request->input('columnName', '')] ?? 'day_of_tanggal_terbit_oss';
        $orderDir = strtolower($request->input('columnSortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->orderBy($orderColumn, $orderDir)
            ->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.perusahaan.export', ['rows' => $rows])->render();
        }, 'Data Perusahaan.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
