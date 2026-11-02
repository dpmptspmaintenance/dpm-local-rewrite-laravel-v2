<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu berkas arsip milik satu Satpam — file fisiknya di disk lokal
 * (subfolder "Kepegawaian/satpam" di dalam root disk arsip), baris ini
 * cuma metadata. Lihat App\Services\Kepegawaian\SatpamArsipService.
 */
class SatpamArsip extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'satpam_arsip';

    protected $guarded = ['id'];

    public function satpam(): BelongsTo
    {
        return $this->belongsTo(Satpam::class, 'satpam_id');
    }

    /**
     * URL pratinjau/unduh dari route aplikasi.
     */
    public function previewUrl(): string
    {
        return route('arsip.berkas.satpam-arsip', ['satpamArsip' => $this->getKey()]);
    }

    public function openUrl(): string
    {
        return $this->previewUrl();
    }
}
