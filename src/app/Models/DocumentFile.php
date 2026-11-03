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
        'link_title',
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

    /** Baris ini tautan (bukan berkas fisik). */
    public function isLink(): bool
    {
        return filled($this->google_web_view_link) && blank($this->storage_path);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * URL pratinjau/unduh dari route aplikasi (bukan lagi Google Drive).
     * Baris tautan langsung mengembalikan URL aslinya.
     */
    public function previewUrl(): string
    {
        if ($this->isLink()) {
            return (string) $this->google_web_view_link;
        }

        return route('arsip.berkas.document-file', ['documentFile' => $this->getKey()]);
    }

    public function openUrl(): string
    {
        return $this->previewUrl();
    }

    /**
     * Apakah berkas ini gambar (JPG/PNG/dll)? Dipakai halaman detail dokumen
     * untuk memilih antara kotak pratinjau gambar atau kotak nama + tipe.
     */
    public function isImage(): bool
    {
        $extension = strtolower((string) $this->file_extension);

        if ($extension === '') {
            $extension = strtolower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION));
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            return true;
        }

        return str_starts_with(strtolower((string) $this->mime_type), 'image/');
    }
}
