<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PegawaiAnak extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_anak';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_lahir_anak' => 'date',
    ];

    /**
     * Pendidikan yang dianggap "kuliah" untuk perpanjangan hak tunjangan
     * sampai usia 25 (batas normal 21).
     */
    public const PENDIDIKAN_KULIAH = ['Diploma III', 'Diploma IV', 'S-1', 'S-2', 'S-3'];

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }

    public function usiaTahun(): ?int
    {
        return $this->tanggal_lahir_anak?->age;
    }

    public function isKuliah(): bool
    {
        return in_array($this->tingkat_pendidikan_anak, self::PENDIDIKAN_KULIAH, true);
    }

    /**
     * Batas usia hak tunjangan anak: 21 tahun normal, diperpanjang sampai 25
     * bila anak masih menempuh pendidikan tinggi (kuliah).
     */
    public function batasUsiaTunjangan(): int
    {
        return $this->isKuliah() ? 25 : 21;
    }

    /**
     * Status hak tunjangan anak (KP4):
     * 'aktif'   — masih jauh dari batas (> 1 tahun menuju batas)
     * 'warning' — ≤ 1 tahun menuju batas usia
     * 'habis'   — sudah melewati batas usia
     */
    public function statusTunjangan(): string
    {
        $usia = $this->usiaTahun();

        if ($usia === null) {
            return 'unknown';
        }

        $batas = $this->batasUsiaTunjangan();

        if ($usia >= $batas) {
            return 'habis';
        }

        // Kurang dari 1 tahun menuju batas: ulang tahun berikutnya sudah
        // mencapai/melewati batas.
        if ($usia >= $batas - 1) {
            return 'warning';
        }

        return 'aktif';
    }
}
