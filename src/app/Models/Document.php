<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Metadata arsip. Berkas fisik disimpan di Google Drive — satu dokumen bisa
 * punya 0..N berkas, semuanya setara (model list flat, tanpa konsep "utama").
 * Lihat App\Services\ArsipDigital\GoogleDriveService.
 */
class Document extends Model
{
    public const STATUS_PENDING = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ARCHIVED = 'archived';

    // Sumber dokumen — berkas fisik diunggah ke Drive vs referensi tautan
    // saja (Google Drive / web), tanpa berkas fisik apa pun di sistem.
    public const SOURCE_FILE = 'file';

    public const SOURCE_URL = 'url';

    /** @var array<string, string> */
    public const SOURCES = [
        self::SOURCE_FILE => 'Berkas',
        self::SOURCE_URL => 'Tautan',
    ];

    /** @var array<string, string> */
    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu Verifikasi',
        self::STATUS_PUBLISHED => 'Diterbitkan',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_ARCHIVED => 'Diarsipkan',
    ];

    protected $fillable = [
        'title',
        'source_type',
        'source_url',
        'drive_folder_id',
        'drive_folder_name',
        'status',
        'rejection_reason',
        'category_id',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tag');
    }

    /**
     * Semua berkas fisik milik dokumen ini (model list flat — tidak ada
     * konsep "utama" lagi, semua baris setara).
     *
     * @return HasMany<DocumentFile>
     */
    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    /**
     * Logika fallback judul — AGENTS.md § 2.C. Dipanggil saat pengunggah
     * mengosongkan judul: bersihkan nama file asli (buang ekstensi, ganti
     * separator jadi spasi, rapikan spasi ganda) lalu beri prefiks "[DRAF]".
     */
    public static function fallbackTitle(string $originalFilename): string
    {
        $withoutExtension = pathinfo($originalFilename, PATHINFO_FILENAME);

        $cleaned = preg_replace('/[_\-]+/', ' ', $withoutExtension) ?? $withoutExtension;
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);

        if ($cleaned === '') {
            $cleaned = $withoutExtension;
        }

        return '[DRAF] - '.$cleaned;
    }

    /**
     * Nama folder Drive khusus dokumen ini, dibuat saat diorganisasi (Approve
     * / merge) di dalam folder induk (ARSIP_DRIVE_FOLDER_ID). Format:
     * "YYYY-MM-DD - {judul}" — tanggal dari created_at (tanggal unggah).
     */
    public function folderName(): string
    {
        $title = (string) $this->title;
        $title = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '', $title);
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);

        if (mb_strlen($title) > 80) {
            $title = rtrim(mb_substr($title, 0, 80));
        }

        if ($title === '') {
            $title = 'Tanpa Judul';
        }

        $date = $this->created_at?->format('Y-m-d') ?? now()->format('Y-m-d');

        return "{$date} - {$title}";
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUrl(): bool
    {
        return $this->source_type === self::SOURCE_URL;
    }

    /**
     * URL yang dibuka saat user menekan tombol buka:
     * - tipe url  : tautan aslinya langsung.
     * - tipe file : folder Drive dokumen kalau sudah ada, kalau belum ke
     *               berkas pertama, kalau tidak ada berkas sama sekali '#'.
     */
    public function openUrl(): string
    {
        if ($this->isUrl()) {
            return (string) $this->source_url;
        }

        if ($this->drive_folder_id) {
            return "https://drive.google.com/drive/folders/{$this->drive_folder_id}";
        }

        return $this->files->first()?->openUrl() ?? '#';
    }
}
