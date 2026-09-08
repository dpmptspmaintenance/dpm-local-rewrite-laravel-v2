<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProyekController extends Controller
{
    private const FILTER_MAP = [
        'tahun' => 'raw:YEAR(p.tanggal_proyek)',
        'bulan' => 'raw:MONTH(p.tanggal_proyek)',
        'uraian_status_penanaman_modal' => 'p.uraian_status_penanaman_modal',
        'uraian_jenis_perusahaan' => 'p.uraian_jenis_perusahaan',
        'uraian_risiko_proyek' => 'p.uraian_risiko_proyek',
        'uraian_skala_usaha' => 'p.uraian_skala_usaha',
        'kecamatan_usaha' => 'p.kecamatan_usaha',
        'sektor_pembina' => 'p.sektor_pembina',
    ];

    private const SORTABLE = [
        'nama_perusahaan' => 'p.nama_perusahaan',
        'nama_proyek' => 'p.nama_proyek',
        'tanggal_terbit_oss' => 'p.tanggal_terbit_oss',
        'nib' => 'p.nib',
        'alamat_usaha' => 'p.alamat_usaha',
        'kecamatan_usaha' => 'p.kecamatan_usaha',
        'kelurahan_usaha' => 'p.kelurahan_usaha',
        'kbli' => 'p.kbli',
        'judul_kbli' => 'p.judul_kbli',
        'uraian_risiko_proyek' => 'p.uraian_risiko_proyek',
        'uraian_jenis_perusahaan' => 'p.uraian_jenis_perusahaan',
        'uraian_skala_usaha' => 'p.uraian_skala_usaha',
        'sektor_pembina' => 'p.sektor_pembina',
        'jumlah_investasi3' => 'p.jumlah_investasi3',
    ];

    private const SELECT_COLUMNS = [
        'p.nama_perusahaan', 'p.nama_proyek', 'p.tanggal_terbit_oss', 'p.nib',
        'p.alamat_usaha', 'p.kecamatan_usaha', 'p.kelurahan_usaha', 'p.kbli',
        'p.judul_kbli', 'p.uraian_risiko_proyek', 'p.uraian_jenis_perusahaan',
        'p.uraian_skala_usaha', 'p.sektor_pembina', 'p.luas_tanah', 'p.satuan_tanah',
        'p.jumlah_investasi3',
    ];

    public function index()
    {
        return view('datakita.proyek.index', [
            'statusPenanamanModal' => DB::table('dasi_master_status_penanaman_modal')->get(),
            'jenisPerusahaan' => DB::table('dasi_master_jenis_perusahaan')->where('is_aktif', 1)->get(),
            'skalaUsaha' => DB::table('dasi_master_skala_usaha')->get(),
            'kecamatan' => DB::table('dasi_master_kecamatan')->get(),
            'pembina' => DB::table('dasi_data_pembina')->get(),
            'sektor' => DB::table('dasi_data_sektor')->where('is_aktif', 1)->get(),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('2023_dp_proyek as p');

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

        $sektor = $request->input('sektor');
        if ($sektor !== null && $sektor !== '') {
            $query->whereIn('p.kbli', DB::table('dasi_data_proyek')
                ->select('kbli')
                ->where('id_data_sektor', $sektor));
        }

        $search = trim((string) $request->input('search.value', $request->input('searchValue', '')));
        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('p.nama_perusahaan', 'like', $like)
                    ->orWhere('p.nama_proyek', 'like', $like)
                    ->orWhere('p.nib', 'like', $like)
                    ->orWhere('p.kbli', 'like', $like);
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
        $orderColumn = self::SORTABLE[$orderName] ?? 'p.tanggal_proyek';

        $recordsTotal = DB::table('2023_dp_proyek')->count();
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
        $orderColumn = self::SORTABLE[$request->input('columnName', '')] ?? 'p.tanggal_proyek';
        $orderDir = strtolower($request->input('columnSortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';

        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->orderBy($orderColumn, $orderDir)
            ->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.proyek.export', ['rows' => $rows])->render();
        }, 'Data Proyek.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
