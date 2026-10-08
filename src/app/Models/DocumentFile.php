<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu berkas fisik milik satu Document — TIDAK ada konsep "utama vs ekstra":
 * semua berkas dokumen adalah baris DocumentFile biasa di tabel ini. Model
 * list flat (keputusan user).
 *
 * Berkas fisik kini di disk lokal (`storage_path`); `google_file_id` masih
 * ada di DB sebagai kolom legacy (nullable) untuk data lama yang diabaikan.
 */
class DocumentFile extends Model
{
    protected $fillable = [
        'document_id',
        'storage_path',
        'google_file_id',
        'google_web_view_link',
        'original_filename',
        'file_extension',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * URL pratinjau/unduh dari route aplikasi (bukan lagi Google Drive).
     */
    public function previewUrl(): string
    {
        return route('arsip.berkas.document-file', ['documentFile' => $this->getKey()]);
    }

    public function openUrl(): string
    {
        return $this->previewUrl();
    }
}
