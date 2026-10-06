<?php

namespace App\Http\Controllers\Bangkit;

use App\Http\Controllers\Bangkit\Concerns\ChecksBangkitAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Daftar user modul Bangkit memakai tabel `users` aplikasi (dpmptsp_new),
 * BUKAN tabel warisan `sekre_bangkit_user`. Role 1..6 dipakai apa adanya
 * (sama arti dengan legacy). scoping bidang SDIA memakai kolom string
 * `users.bidang`.
 */
class BangkitUserController extends Controller
{
    use ChecksBangkitAccess;

    public const ROLES = [
        1 => 'Superadmin',
        2 => 'Kadin',
        3 => 'Sekdin',
        4 => 'Kasubag',
        5 => 'Bendahara',
        6 => 'User',
    ];

    public function daftar(Request $request)
    {
        $this->abortIfNotBangkitAdmin();

        $status = $request->query('status'); // 'aktif' | 'nonaktif' | null
        $bidang = $request->query('bidang'); // nama bidang persis

        // Hanya role internal (1..6). Role 7/8 (Dinas Luar) dikecualikan.
        $users = User::whereIn('role', array_keys(self::ROLES))
            ->when($status === 'aktif', fn ($q) => $q->where('is_aktif', 1))
            ->when($status === 'nonaktif', fn ($q) => $q->where('is_aktif', 0))
            ->when($bidang === '__kosong__', fn ($q) => $q->where(fn ($w) => $w->whereNull('bidang')->orWhere('bidang', '')))
            ->when($bidang && $bidang !== '__kosong__', fn ($q) => $q->where('bidang', $bidang))
            ->orderBy('nama')
            ->get();

        return view('bangkit.user.daftar', [
            'users' => $users,
            'roles' => self::ROLES,
            'status' => $status,
            'bidang' => $bidang,
            'bidangOptions' => $this->bidangOptions(),
        ]);
    }

    public function toggle(int $id)
    {
        $this->abortIfNotBangkitAdmin();

        $user = User::whereIn('role', array_keys(self::ROLES))->findOrFail($id);
        $user->update(['is_aktif' => $user->is_aktif ? 0 : 1]);

        return redirect()->route('bangkit.user.daftar')->with('success', 'Status user diubah.');
    }

    public function ubah(int $id)
    {
        $this->abortIfNotBangkitAdmin();

        return view('bangkit.user.form', [
            'user' => User::whereIn('role', array_keys(self::ROLES))->findOrFail($id),
            'roles' => self::ROLES,
            'bidangs' => $this->bidangOptions(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->abortIfNotBangkitAdmin();
        User::whereIn('role', array_keys(self::ROLES))->findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string|max:100',
            'bidang' => 'nullable|string|max:100',
            'role' => 'required|integer|in:1,2,3,4,5,6',
            'is_admin_bangkit' => 'nullable|boolean',
        ]);

        User::whereKey($id)->update([
            'nama' => $data['nama'],
            'bidang' => $data['bidang'] ?? null,
            'role' => $data['role'],
            'is_admin_bangkit' => $request->boolean('is_admin_bangkit') ? 1 : 0,
        ]);

        return redirect()->route('bangkit.user.daftar')->with('success', 'User diubah.');
    }

    /**
     * Opsi bidang: gabungan nama yang sudah dipakai user + master katkit_bidang,
     * supaya admin bisa memilih atau ketik bidang baru yang akan otomatis
     * terpetakan saat seeder/master diisi.
     */
    private function bidangOptions(): array
    {
        $dipakai = User::whereNotNull('bidang')->where('bidang', '!=', '')
            ->distinct()->orderBy('bidang')->pluck('bidang')->all();

        $master = \App\Models\Bangkit\KatkitBidang::orderBy('nama_bidang')->pluck('nama_bidang')->all();

        return collect($dipakai)->merge($master)->unique()->sort()->values()->all();
    }
}
