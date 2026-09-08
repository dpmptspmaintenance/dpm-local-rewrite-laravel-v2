<?php

namespace App\Http\Controllers\Persediaan\Concerns;

use App\Models\User;

/**
 * Mirrors the role derivation in legacy persediaan/header.php:
 *   is_admin          = role == 1 || is_admin_persediaan == 1
 *   is_pegawai_biasa  = !is_admin && is_bpp == 0
 * All module controllers gate on these instead of the raw columns.
 */
trait ChecksPersediaanAccess
{
    public function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $user->role === 1 || (int) $user->is_admin_persediaan === 1;
    }

    public function isBpp(?User $user): bool
    {
        return (bool) ($user->is_bpp ?? false);
    }

    public function isPegawaiBiasa(?User $user): bool
    {
        return ! $this->isAdmin($user) && ! $this->isBpp($user);
    }

    public function abortIfNotPersediaanAdmin(): void
    {
        abort_if(! $this->isAdmin(auth()->user()), 403, 'Akses Ditolak! Hanya Admin Persediaan yang dapat mengakses halaman ini.');
    }

    /** Admin or BPP may use the BAST/riwayat-dokumen flow. */
    public function abortIfNotAdminOrBpp(): void
    {
        abort_if($this->isPegawaiBiasa(auth()->user()), 403, 'Akses Ditolak! Anda tidak memiliki hak akses ke halaman ini.');
    }
}
