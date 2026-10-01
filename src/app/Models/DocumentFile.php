<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu berkas fisik milik satu Document — TIDAK ada konsep "utama vs ekstra":
 * semua berkas dokumen adalah baris DocumentFile biasa di tabel ini. Model
 * list flat (keputusan user).
 */
class DocumentFile extends Model
{
    protected $fillable = [
        'document_id',
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

    public function previewUrl(): string
    {
        return "https://drive.google.com/file/d/{$this->google_file_id}/preview";
    }

    public function openUrl(): string
    {
        return $this->google_web_view_link ?: $this->previewUrl();
    }
}
