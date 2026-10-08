<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu berkas arsip pegawai (SKP, SK Kenaikan Pangkat, SK Jabatan, Foto,
 * Ijazah, dll) — file fisiknya di disk lokal (subfolder "Kepegawaian" di
 * dalam root disk arsip), baris ini cuma metadata. Lihat
 * App\Services\Kepegawaian\PegawaiArsipService.
 */
class PegawaiArsip extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_arsip';

    protected $guarded = ['id'];

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }

    /**
     * URL pratinjau/unduh dari route aplikasi (bukan lagi Google Drive).
     */
    public function previewUrl(): string
    {
        return route('arsip.berkas.pegawai-arsip', ['pegawaiArsip' => $this->getKey()]);
    }

    public function openUrl(): string
    {
        return $this->previewUrl();
    }
}
