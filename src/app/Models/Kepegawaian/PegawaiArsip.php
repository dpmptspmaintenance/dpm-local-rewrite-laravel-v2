<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu berkas arsip pegawai (SKP, SK Kenaikan Pangkat, SK Jabatan, Foto,
 * Ijazah, dll) — file fisiknya di Google Drive (subfolder "Kepegawaian" di
 * dalam folder induk Arsip Digital), baris ini cuma metadata. Lihat
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

    public function previewUrl(): string
    {
        return "https://drive.google.com/file/d/{$this->google_file_id}/preview";
    }

    public function openUrl(): string
    {
        return $this->google_web_view_link ?: $this->previewUrl();
    }
}
