<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Kunci API untuk integrasi eksternal (Chrome extension Generator Surat
 * Cuti). Hanya hash SHA-256 yang disimpan; plaintext ditampilkan sekali saat
 * dibuat (lihat ApiKeys page).
 *
 * Lives on the `kepegawaian` connection karena hanya melayani modul itu.
 */
class ApiKey extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'api_keys';

    public const SCOPE_GENERATOR_CUTI = 'generator-cuti';

    public const SCOPE_REKAP_ABSEN = 'rekap-absen';

    public const SCOPE_DATA_DUKUNG_TPP = 'data-dukung-tpp';

    public const PREFIX = 'dpmk_';

    /** @var array<string, string> */
    public const SCOPES = [
        self::SCOPE_GENERATOR_CUTI => 'Generator Surat Cuti (baca pegawai, hitung sisa, simpan nomor)',
        self::SCOPE_REKAP_ABSEN => 'Rekap Absen Bulanan (unggah/upsert presensi bulanan per pegawai)',
        self::SCOPE_DATA_DUKUNG_TPP => 'Data Dukung TPP (unggah/upsert data pegawai tidak masuk kerja dari TPP)',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    /**
     * Buat kunci baru. Mengembalikan [model, plaintext] — plaintext HANYA
     * ada di sini, tidak bisa diambil lagi setelahnya.
     *
     * @param  list<string>  $scopes
     * @return array{0: self, 1: string}
     */
    public static function terbitkan(
        string $name,
        array $scopes,
        ?User $pembuat = null,
        ?\DateTimeInterface $expiresAt = null,
    ): array {
        $plaintext = self::PREFIX.Str::random(40);

        $model = self::create([
            'name' => trim($name),
            'key_prefix' => substr($plaintext, 0, 12),
            'key_hash' => hash('sha256', $plaintext),
            'scopes' => array_values(array_unique($scopes)),
            'user_id' => $pembuat?->getKey(),
            'user_nama' => $pembuat?->nama,
            'expires_at' => $expiresAt,
        ]);

        return [$model, $plaintext];
    }

    /** Cari kunci aktif dari plaintext header (null bila tidak valid). */
    public static function dariPlaintext(?string $plaintext): ?self
    {
        $plaintext = trim((string) $plaintext);

        if ($plaintext === '' || ! str_starts_with($plaintext, self::PREFIX)) {
            return null;
        }

        try {
            return self::query()
                ->where('key_hash', hash('sha256', $plaintext))
                ->first();
        } catch (\Throwable) {
            // Tabel belum dimigrasi / DB tidak terjangkau → anggap tak valid.
            report(new \RuntimeException('[api] lookup api_keys gagal'));

            return null;
        }
    }

    public function aktif(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function punyaScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    public function tandaiDipakai(?string $ip): void
    {
        // Tanpa updated_at supaya urutan "dibuat" tidak berubah.
        self::query()->whereKey($this->getKey())->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
            'updated_at' => $this->updated_at,
        ]);
    }

    public function statusLabel(): string
    {
        if ($this->revoked_at !== null) {
            return 'Dicabut';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'Kadaluarsa';
        }

        return 'Aktif';
    }
}
