<?php

namespace App\Http\Controllers\Bangkit;

use App\Http\Controllers\Bangkit\Concerns\ChecksBangkitAccess;
use App\Http\Controllers\Controller;
use App\Models\Bangkit\MasterJenisBarang;
use App\Models\Bangkit\PermohonanPerbaikan;

class RekapController extends Controller
{
    use ChecksBangkitAccess;

    public function rekapPerbulan(int $bulan = null)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403, 'Akses Ditolak!');
        $bulan ??= (int) date('n');

        $list = PermohonanPerbaikan::with('barang')
            ->where('is_aktif', 1)
            ->where('verifikasi_b_barang', 1)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('created_at')
            ->get();

        return view('bangkit.rekap.perbulan', ['list' => $list, 'bulan' => $bulan]);
    }

    public function printRekapPerbulan(int $bulan)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403, 'Akses Ditolak!');

        $list = PermohonanPerbaikan::with('barang')
            ->where('is_aktif', 1)
            ->where('verifikasi_b_barang', 1)
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('created_at')
            ->get();

        return view('bangkit.rekap.print-perbulan', ['list' => $list, 'bulan' => $bulan]);
    }

    public function aksiFilterBulan(\Illuminate\Http\Request $request)
    {
        $this->abortIfNotBangkitUser();
        $bulan = (int) $request->input('bulan', date('n'));

        return redirect()->route('bangkit.rekap.perbulan', $bulan);
    }

    public function rekapPermohonan(int $bulan, int $jenisBarang)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403, 'Akses Ditolak!');

        $list = PermohonanPerbaikan::with('barang')
            ->where('sekre_bangkit_data_permohonan_perbaikan.is_aktif', 1)
            ->whereHas('barang', fn ($q) => $q->where('Jenis', $jenisBarang))
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('created_at')
            ->get();

        return view('bangkit.rekap.permohonan', [
            'list' => $list,
            'bulan' => $bulan,
            'jenis_barang' => MasterJenisBarang::orderBy('jenis_barang')->get(),
        ]);
    }

    public function printRekapPermohonan(int $bulan, int $jenisBarang)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403, 'Akses Ditolak!');

        $list = PermohonanPerbaikan::with('barang')
            ->where('is_aktif', 1)
            ->whereHas('barang', fn ($q) => $q->where('Jenis', $jenisBarang))
            ->whereMonth('created_at', $bulan)
            ->orderByDesc('created_at')
            ->get();

        return view('bangkit.rekap.print-permohonan', [
            'list' => $list,
            'bulan' => $bulan,
            'jenis_barang' => MasterJenisBarang::orderBy('jenis_barang')->get(),
        ]);
    }

    public function aksiRekapPermohonan(\Illuminate\Http\Request $request)
    {
        $this->abortIfNotBangkitUser();

        return redirect()->route('bangkit.rekap.permohonan', [
            'bulan' => (int) $request->input('filter_bulan', date('n')),
            'jenisBarang' => (int) $request->input('jenis_barang', 1),
        ]);
    }
}
