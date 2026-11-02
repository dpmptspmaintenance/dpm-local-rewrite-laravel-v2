<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data Satpam (non-ASN, di luar SISDM/pegawai_profil) beserta arsip
 * berkasnya (KTA, KTP, Ijazah, dll). Berkas fisik di disk lokal subfolder
 * "Kepegawaian/satpam" — lihat App\Services\Kepegawaian\SatpamArsipService.
 */
class Satpam extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'satpam';

    protected $guarded = ['id'];

    public function arsip(): HasMany
    {
        return $this->hasMany(SatpamArsip::class, 'satpam_id')->orderByDesc('created_at');
    }

    /**
     * Berkas khusus satu jenis (KTA/KK/KTP/Ijazah) — selalu maksimal satu
     * baris per jenis per satpam (upload baru menggantikan yang lama).
     */
    public function berkasJenis(string $jenis): ?SatpamArsip
    {
        return $this->arsip()->where('kategori', $jenis)->first();
    }
}
