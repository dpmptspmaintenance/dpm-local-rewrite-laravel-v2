<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerizinanController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'raw:YEAR(p.tanggal_proyek)',
        'bulan' => 'raw:MONTH(p.tanggal_proyek)',
        'uraian_status_penanaman_modal' => 'p.uraian_status_penanaman_modal',
        'uraian_jenis_perusahaan' => 'p.uraian_jenis_perusahaan',
        'uraian_skala_usaha' => 'p.uraian_skala_usaha',
        'kecamatan_usaha' => 'p.kecamatan_usaha',
        'sektor_pembina' => 'p.sektor_pembina',
        'uraian_status_respon' => 'i.uraian_status_respon',
    ];

    private const SORTABLE = [
        'id_proyek' => 'p.id_proyek',
        'nib' => 'p.nib',
        'nama_perusahaan' => 'p.nama_perusahaan',
        'nama_proyek' => 'p.nama_proyek',
        'alamat_kantor' => 'k.alamat_perusahaan',
        'kecamatan_usaha' => 'p.kecamatan_usaha',
        'kbli' => 'p.kbli',
        'judul_kbli' => 'p.judul_kbli',
        'uraian_skala_usaha' => 'p.uraian_skala_usaha',
        'jumlah_investasi3' => 'p.jumlah_investasi3',
        'id_permohonan_izin' => 'i.id_permohonan_izin',
        'uraian_jenis_perizinan' => 'i.uraian_jenis_perizinan',
        'uraian_status_respon' => 'i.uraian_status_respon',
    ];

    public function index()
    {
        return view('datakita.perizinan.index', [
            'statusPenanamanModal' => DB::table('dasi_master_status_penanaman_modal')->get(),
            'jenisPerusahaan' => DB::table('dasi_master_jenis_perusahaan')->where('is_aktif', 1)->get(),
            'skalaUsaha' => DB::table('dasi_master_skala_usaha')->get(),
            'kecamatan' => DB::table('dasi_master_kecamatan')->get(),
            'pembina' => DB::table('dasi_data_pembina')->get(),
            'statusRespon' => DB::table('2023_list_izin')
                ->select('uraian_status_respon')
                ->distinct()
                ->whereNotNull('uraian_status_respon')
                ->where('uraian_status_respon', '!=', '')
                ->orderBy('uraian_status_respon')
                ->pluck('uraian_status_respon'),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('2023_dp_proyek as p')
            ->leftJoin('2023_dp_nib_kantor as k', 'p.nib', '=', 'k.nib')
            ->leftJoin('2023_list_izin as i', 'p.id_proyek', '=', 'i.id_proyek');

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
                $q->where('p.nama_perusahaan', 'like', $like)
                    ->orWhere('p.nama_proyek', 'like', $like)
                    ->orWhere('p.nib', 'like', $like)
                    ->orWhere('p.kbli', 'like', $like)
                    ->orWhere('i.uraian_jenis_perizinan', 'like', $like)
                    ->orWhere('i.id_permohonan_izin', 'like', $like);
            });
        }

        return $query;
    }

    private const SELECT_COLUMNS = [
        'p.id_proyek', 'p.nib', 'p.nama_perusahaan', 'p.nama_proyek', 'p.tanggal_terbit_oss',
        'p.alamat_usaha', 'p.kecamatan_usaha', 'p.kelurahan_usaha', 'p.kbli', 'p.judul_kbli',
        'p.uraian_risiko_proyek', 'p.uraian_jenis_perusahaan', 'p.uraian_skala_usaha',
        'p.sektor_pembina', 'p.luas_tanah', 'p.satuan_tanah', 'p.jumlah_investasi3',
        'k.alamat_perusahaan as alamat_kantor', 'k.email as email_kantor',
        'i.id_permohonan_izin', 'i.uraian_jenis_perizinan', 'i.nama_dokumen',
        'i.uraian_status_respon', 'i.day_of_tanggal_izin',
    ];

    public function ajaxData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $orderName = $request->input('order.0.name', '');
        $orderDir = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = self::SORTABLE[$orderName] ?? 'p.tanggal_proyek';

        $recordsTotal = DB::table('2023_dp_proyek')->count();
        $recordsFiltered = $this->baseQuery($request)->count(DB::raw('DISTINCT p.id'));

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
        $orderColumn = self::SORTABLE[$request->input('columnName', '')] ?? 'p.tanggal_proyek';
        $orderDir = strtolower($request->input('columnSortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->orderBy($orderColumn, $orderDir)
            ->get();

        $filename = 'Data_Proyek_Izin_Kantor.xls';

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.perizinan.export', ['rows' => $rows])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
