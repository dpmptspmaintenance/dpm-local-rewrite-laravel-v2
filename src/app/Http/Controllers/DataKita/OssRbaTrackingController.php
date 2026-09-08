<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OssRbaTrackingController extends Controller
{
    public function index(Request $request)
    {
        $idPermohonanIzin = trim((string) $request->query('id_permohonan_izin', ''));

        $results = collect();
        if ($idPermohonanIzin !== '') {
            $results = DB::table('2023_list_izin')
                ->where('id_permohonan_izin', $idPermohonanIzin)
                ->get();
        }

        return view('datakita.oss-rba-tracking.index', [
            'results' => $results,
            'idPermohonanIzin' => $idPermohonanIzin,
            'searched' => $request->query->count() > 0,
        ]);
    }
}
