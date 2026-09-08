<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikKbliController extends Controller
{
    public function index()
    {
        return view('datakita.grafik-kbli.index');
    }

    public function tahunan(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));

        $rows = DB::table('2023_dp_proyek')
            ->select('tahun_pengambilan_data as tahun', 'kbli', 'judul_kbli')
            ->selectRaw('SUM(tki) as tki')
            ->selectRaw('SUM(jumlah_investasi3) as jumlah_investasi3')
            ->where(function ($q) use ($cari) {
                $like = "%{$cari}%";
                $q->where('kbli', 'like', $like)
                    ->orWhere('judul_kbli', 'like', $like);
            })
            ->groupBy('kbli', 'tahun_pengambilan_data', 'judul_kbli')
            ->orderBy('tahun_pengambilan_data')
            ->orderBy('kbli')
            ->get();

        $dataPerTahun = $rows->groupBy('tahun');

        return view('datakita.grafik-kbli.tahunan', [
            'dataPerTahun' => $dataPerTahun,
        ]);
    }
}
