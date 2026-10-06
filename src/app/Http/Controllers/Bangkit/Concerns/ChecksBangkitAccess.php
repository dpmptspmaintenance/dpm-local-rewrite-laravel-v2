<?php

namespace App\Http\Controllers\Bangkit\Concerns;

use App\Models\Bangkit\KatkitBidang;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Gating modul Bangkit (Barang Kita + SDIA), meniru ChecksPersediaanAccess.
 *
 * Legacy CodeIgniter `bangkit` punya 6 role sendiri (1 superadmin, 2 kadin,
 * 3 sekdin, 4 kasubag, 5 bendahara, 6 user) dan tiap role punya controller +
 * view terpisah namun ~95% identik. Di app ini role `users.role` kebetulan
 * bernilai sama 1..6, jadi dipakai apa adanya:
 *
 *   - isBangkitAdmin : role 1 ATAU flag is_admin_bangkit  → admin penuh
 *     (superadmin: kelola barang, verifikasi tahap akhir, selesaikan, kelola
 *     user bangkit).
 *   - role 5 (bendahara)  : verifikasi tahap 1 permohonan.
 *   - role 3 (sekdin)     : verifikasi tahap 3.
 *   - role 4 (kasubag)    : verifikasi tahap 2 + laporan/kartu.
 *   - role 2 (kadin)      : read-only pencarian.
 *   - role 6 (user)       : input SDIA + permohonan barang miliknya.
 */
trait ChecksBangkitAccess
{
    protected function isBangkitAdmin(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (int) ($user->role ?? 0) === 1 || (int) ($user->is_admin_bangkit ?? 0) === 1;
    }

    protected function roleBangkit(?User $user = null): int
    {
        $user ??= auth()->user();

        return (int) ($user->role ?? 0);
    }

    protected function abortIfNotBangkitAdmin(): void
    {
        abort_if($this->isBangkitAdmin() === false, 403, 'Akses Ditolak! Hanya Admin Bangkit / Superadmin.');
    }

    /**
     * Role yang boleh mengakses modul Bangkit sama sekali (2..6 + admin).
     * Meniru redirect legacy: role di luar 1..6 dianggap tak berhak.
     */
    protected function abortIfNotBangkitUser(): void
    {
        abort_if($this->roleBangkit() < 1 || $this->roleBangkit() > 6, 403, 'Akses Ditolak! Modul Bangkit hanya untuk pengguna internal.');
    }

    /**
     * Resolve id_katkit_bidang milik user yang login untuk scoping data SDIA.
     * Legacy menyimpan `bidang` sebagai int (key `katkit_bidang.Id`); di app
     * ini `users.bidang` berupa string bebas ("Bidang 1", "Sekretariat", ...),
     * jadi dicocokkan case-insensitively ke `katkit_bidang.nama_bidang`.
     * Kembalikan null kalau tak ketemu (pemanggil admin → tampil semua).
     */
    protected function bidangId(): ?int
    {
        $nama = trim((string) (auth()->user()->bidang ?? ''));
        if ($nama === '') {
            return null;
        }

        $map = Cache::rememberForever('bangkit.bidang_map', function () {
            return KatkitBidang::query()
                ->pluck('Id', 'nama_bidang')
                ->mapWithKeys(fn ($id, $nama) => [mb_strtolower(trim($nama)) => $id])
                ->all();
        });

        return $map[mb_strtolower($nama)] ?? null;
    }

    /**
     * Scope SDIA: admin lihat semua; user bidang lihat bidangnya; kalau
     * bidangnya tak termap ke katkit_bidang, kembalikan -1 supaya kosong
     * (bukan bocor semua data). Pakai ->whereIn biar aman di query builder.
     */
    protected function scopeBidangId(): ?int
    {
        if ($this->isBangkitAdmin()) {
            return null;
        }

        return $this->bidangId() ?? -1;
    }
}
