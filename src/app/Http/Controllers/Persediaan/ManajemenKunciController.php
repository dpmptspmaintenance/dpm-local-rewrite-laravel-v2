<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Models\Persediaan\PenguncianLaporan;
use Illuminate\Http\Request;

class ManajemenKunciController extends Controller
{
    use ChecksPersediaanAccess;

    public function index(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $tahun = (int) $request->input('tahun', date('Y'));
        $tahun = ($tahun >= 2000 && $tahun <= 2100) ? $tahun : (int) date('Y');

        $kunci = PenguncianLaporan::query()
            ->where('tahun', $tahun)
            ->pluck('is_locked', 'bulan')
            ->all();

        return view('persediaan.manajemen-kunci.index', [
            'tahun' => $tahun,
            'kunci' => $kunci,
        ]);
    }

    public function toggle(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'status_baru' => ['required', 'in:0,1'],
        ]);

        PenguncianLaporan::query()->updateOrCreate(
            [
                'tahun' => (int) $validated['tahun'],
                'bulan' => (int) $validated['bulan'],
            ],
            ['is_locked' => (int) $validated['status_baru'] === 1]
        );

        return back()->with('success', 'Status penguncian bulan '.$validated['bulan'].'-'.$validated['tahun'].' berhasil diperbarui!');
    }
}
