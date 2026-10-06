<?php

namespace App\Http\Controllers\Bangkit;

use App\Http\Controllers\Bangkit\Concerns\ChecksBangkitAccess;
use App\Http\Controllers\Controller;
use App\Models\Bangkit\Barang;
use App\Models\Bangkit\PermohonanPerbaikan;

class BangkitController extends Controller
{
    use ChecksBangkitAccess;

    public function index()
    {
        $this->abortIfNotBangkitUser();

        $barang = Barang::query()->where('is_aktif', 1);
        $permohonan = PermohonanPerbaikan::query()->where('is_aktif', 1);

        // "Barang milik saya" — pemegang = pegawai yang namanya match user.
        $pegawaiId = $this->pegawaiId();

        $stats = [
            'total_barang' => (clone $barang)->count(),
            'barang_saya' => $pegawaiId ? (clone $barang)->where('pemegang', $pegawaiId)->count() : 0,
            'permohonan_aktif' => (clone $permohonan)->count(),
            'permohonan_selesai' => (clone $permohonan)->whereNotNull('tanggal_selesai')->count(),
        ];

        return view('bangkit.index', compact('stats'));
    }

    /**
     * Cocokkan user login ke `sekre_pegawai_kekuatan` lewat nama (kolom
     * `users.nama` ≈ `sekre_pegawai_kekuatan.nama`). Null kalau tak cocok.
     */
    protected function pegawaiId(): ?int
    {
        $nama = trim((string) (auth()->user()->nama ?? ''));
        if ($nama === '') {
            return null;
        }

        return \App\Models\Bangkit\PegawaiKekuatan::query()
            ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($nama)])
            ->value('Id');
    }
}
