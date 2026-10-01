<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OssRbaController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'raw:YEAR(l.tanggal_permohonan)',
        'bulan' => 'raw:MONTH(l.tanggal_permohonan)',
        'uraian_status_penanaman_modal' => 'l.uraian_status_penanaman_modal',
        'resiko_proyek' => 'r.Resiko',
        'uraian_jenis_perizinan' => 'l.uraian_jenis_perizinan',
        'nama_dokumen' => 'l.nama_dokumen',
        'kl_sektor' => 'l.kl_sektor',
        'uraian_status_respon' => 'l.uraian_status_respon',
        'sektor' => 's.Id',
    ];

    private const SORTABLE = [
        'nama_perusahaan' => 'l.nama_perusahaan',
        'day_of_tanggal_terbit_oss' => 'l.day_of_tanggal_terbit_oss',
    ];

    private const SELECT_COLUMNS = [
        'l.nama_perusahaan', 'l.nib', 'l.day_of_tanggal_terbit_oss', 'l.kd_resiko',
        'l.kbli', 'l.uraian_jenis_perizinan', 'l.nama_dokumen', 'l.uraian_status_respon',
        'l.kl_sektor', 'r.Resiko as resiko', 'f.nama_proyek as judul_kbli',
        'dp.kelurahan_usaha as kelurahan', 'dp.kecamatan_usaha as kecamatan',
    ];

    public function index()
    {
        return view('datakita.oss-rba.index', [
            'statusPenanamanModal' => DB::table('dasi_master_status_penanaman_modal')->get(),
            'resiko' => DB::table('dasi_master_resiko')->get(),
            'jenisIzin' => DB::table('dasi_master_izin')->where('is_aktif', 1)->get(),
            'dokumenIzin' => DB::table('dasi_master_dokumen_izin')->where('is_aktif', 1)->get(),
            'pembina' => DB::table('dasi_data_pembina')->get(),
            'statusRespon' => DB::table('dasi_master_status_respon')->get(),
            'sektor' => DB::table('dasi_data_sektor')->get(),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('2023_list_izin as l')
            ->join('dasi_master_resiko as r', 'l.kd_resiko', '=', 'r.kd_resiko')
            ->join('dasi_data_proyek as f', 'l.kbli', '=', 'f.kbli')
            ->leftJoin('dasi_data_sektor as s', 'f.id_data_sektor', '=', 's.Id')
            ->leftJoin('2023_dp_proyek as dp', 'l.id_proyek', '=', 'dp.id_proyek');

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
                $q->where('l.nama_perusahaan', 'like', $like)
                    ->orWhere('l.kbli', 'like', $like)
                    ->orWhere('l.nib', 'like', $like)
                    ->orWhere('f.nama_proyek', 'like', $like);
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
        $orderColumn = self::SORTABLE[$orderName] ?? 'l.tanggal_proyek';

        $recordsTotal = DB::table('2023_list_izin')->count();
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

    private const EXPORT_REFERENCE = [
        'r.Resiko' => 'resiko',
        'f.nama_proyek' => 'judul_kbli',
        'dp.kelurahan_usaha' => 'kelurahan',
        'dp.kecamatan_usaha' => 'kecamatan',
    ];

    public function export(Request $request)
    {
        $orderColumn = self::SORTABLE[$request->input('columnName', '')] ?? 'l.tanggal_proyek';
        $orderDir = strtolower($request->input('columnSortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        $columns = array_values(array_diff(Schema::getColumnListing('2023_list_izin'), ['id']));

        $selects = [];
        foreach ($columns as $column) {
            $selects[] = DB::raw("`l`.`{$column}` as `l__{$column}`");
        }

        $reference = [];
        foreach (self::EXPORT_REFERENCE as $expression => $alias) {
            $selects[] = DB::raw("{$expression} as `ref__{$alias}`");
            $reference[] = $alias;
        }

        $rows = $this->baseQuery($request)
            ->select($selects)
            ->orderBy($orderColumn, $orderDir)
            ->get();

        $groups = ['l' => $columns, 'ref' => $reference];

        return response()->streamDownload(function () use ($rows, $groups) {
            echo view('datakita.oss-rba.export', ['rows' => $rows, 'groups' => $groups])->render();
        }, 'Data Perizinan OSS-RBA.xls', [
            'Content-Type' => 'application/vnd-ms-excel',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
