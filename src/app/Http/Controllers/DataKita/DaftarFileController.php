<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DaftarFileController extends Controller
{
    private const ALLOWED_KLASIFIKASI = ['oss', 'simbg', 'nswi', 'mpp'];

    public function index(Request $request)
    {
        $klasifikasi = (string) $request->query('klasifikasi', 'oss');

        $files = DB::table('datakita_upload_file')
            ->where('is_aktif', 1)
            ->where('klasifikasi', $klasifikasi)
            ->orderByDesc('id')
            ->get();

        return view('datakita.daftar-file.index', compact('klasifikasi', 'files'));
    }

    public function upload(Request $request)
    {
        $klasifikasi = (string) $request->query('klasifikasi', '');
        if (! in_array($klasifikasi, self::ALLOWED_KLASIFIKASI, true)) {
            return redirect()->route('datakita.daftar-file.index', ['klasifikasi' => 'oss']);
        }

        $request->validate([
            'berkas' => 'required|file',
        ]);

        $file = $request->file('berkas');
        $namaFile = $file->getClientOriginalName();

        // Simpan ke storage/public datakita-file/<klasifikasi>
        $storedPath = $file->storeAs(
            'datakita-file/'.$klasifikasi,
            $namaFile,
            'public'
        );

        DB::table('datakita_upload_file')->insert([
            'nama_file' => $namaFile,
            'lokasi_file' => '/storage/'.$storedPath,
            'klasifikasi' => $klasifikasi,
            'created_by' => (string) (Auth::id() ?? 'guest'),
            'is_aktif' => 1,
        ]);

        return redirect()->route('datakita.daftar-file.index', ['klasifikasi' => $klasifikasi])
            ->with('success', 'Berhasil!');
    }
}
