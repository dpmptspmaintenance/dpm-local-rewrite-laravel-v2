<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RealisasiInvestasiController extends Controller
{
    private const SORTABLE = ['no_proyek', 'nama_perusahaan', 'no_izin', 'status', 'negara', 'tahun', 'triwulan'];

    private const SELECT_COLUMNS = ['no_proyek', 'nama_perusahaan', 'no_izin', 'status', 'negara', 'tahun', 'triwulan'];

    public function index(Request $request)
    {
        $allTahun = DB::table('laporan_realisasi_investasi')
            ->select('tahun')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $currentTahun = $request->query('tahun', $allTahun->first() ?? date('Y'));

        return view('datakita.realisasi-investasi.index', [
            'allTahun' => $allTahun,
            'currentTahun' => $currentTahun,
            'statusList' => DB::table('laporan_realisasi_investasi')->where('tahun', $currentTahun)->groupBy('status')->pluck('status'),
            'negaraList' => DB::table('laporan_realisasi_investasi')->where('tahun', $currentTahun)->groupBy('negara')->pluck('negara'),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('laporan_realisasi_investasi');

        foreach (['tahun', 'status', 'negara'] as $param) {
            $value = $request->input($param);
            if ($value !== null && $value !== '') {
                $query->where($param, $value);
            }
        }

        $search = trim((string) $request->input('search.value', $request->input('search', '')));
        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('nama_perusahaan', 'like', $like)
                    ->orWhere('no_izin', 'like', $like)
                    ->orWhere('no_proyek', 'like', $like)
                    ->orWhere('negara', 'like', $like);
            });
        }

        return $query;
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $orderName = $request->input('order.0.name', 'nama_perusahaan');
        $orderColumn = in_array($orderName, self::SORTABLE, true) ? $orderName : 'nama_perusahaan';
        $orderDir = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $tahun = $request->input('tahun', date('Y'));
        $recordsTotal = DB::table('laporan_realisasi_investasi')->where('tahun', $tahun)->count();
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
        $rows = $this->baseQuery($request)
            ->select(self::SELECT_COLUMNS)
            ->orderBy('nama_perusahaan')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.realisasi-investasi.export', ['rows' => $rows])->render();
        }, 'Data_Realisasi_Investasi.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
