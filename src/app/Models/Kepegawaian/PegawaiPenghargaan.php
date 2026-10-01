<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PegawaiPenghargaan extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_penghargaan';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * 'impor' = ditulis oleh PegawaiImportService dari JSON SISDM (kena
     * full-snapshot replace tiap impor pegawai itu). 'manual' = ditambah
     * lewat halaman Daftar Penghargaan, tidak pernah disentuh impor —
     * dipakai saat staf malas mengisi SIMPATIK/SISDM tapi SK penghargaannya
     * nyata ada.
     */
    public const SUMBER_IMPOR = 'impor';

    public const SUMBER_MANUAL = 'manual';

    /**
     * Label pilihan tingkat SLKS untuk form tambah manual — key = value yang
     * disimpan ke kolom jenis_penghargaan (sama persis format teks dari
     * scrape SISDM, mis. "SATYALANCANA KARYA SATYA 20 TAHUN"), supaya baris
     * manual dan baris impor bisa di-parse tingkatnya dengan regex yang sama
     * (tierFromText()) tanpa perlakuan khusus.
     */
    public const TIER_LABELS = [
        'SATYALANCANA KARYA SATYA 10 TAHUN' => 'Satyalancana Karya Satya 10 Tahun',
        'SATYALANCANA KARYA SATYA 20 TAHUN' => 'Satyalancana Karya Satya 20 Tahun',
        'SATYALANCANA KARYA SATYA 30 TAHUN' => 'Satyalancana Karya Satya 30 Tahun',
    ];

    /** Default nama_penghargaan per tingkat — samakan gaya dgn data scrape ("Karya Satya 20 tahun"). */
    public const NAMA_DEFAULT = [
        'SATYALANCANA KARYA SATYA 10 TAHUN' => 'Karya Satya 10 tahun',
        'SATYALANCANA KARYA SATYA 20 TAHUN' => 'Karya Satya 20 tahun',
        'SATYALANCANA KARYA SATYA 30 TAHUN' => 'Karya Satya 30 tahun',
    ];

    /**
     * Tingkat SLKS (10/20/30) yang tersirat dari sebuah teks, dibaca via
     * regex "N TAHUN" atau singkatan "N TH" (mis. data SISDM yang menulis
     * "SATYA LANCANA KARYA SATYA 20 TH") — cocok untuk teks hasil scrape
     * maupun teks canonical dari TIER_LABELS di atas. Null bila teks tak
     * mengandung pola itu atau angkanya bukan 10/20/30.
     */
    public static function tierFromText(?string $text): ?int
    {
        if (blank($text) || ! preg_match('/(\d+)\s*(TAHUN|TH\b)/i', $text, $m)) {
            return null;
        }

        $n = (int) $m[1];

        return in_array($n, [10, 20, 30], true) ? $n : null;
    }

    /**
     * Tingkat SLKS baris ini — dicoba dari jenis_penghargaan dulu, fallback
     * ke nama_penghargaan bila jenis_penghargaan tak mengandung info tingkat
     * (SISDM kadang mengkategorikan SLKS asli sebagai generic "TANDA
     * PENGHARGAAN LAINNYA", padahal nama_penghargaan-nya jelas menyebut
     * tingkatnya — baris itu tak boleh hilang dari rekap checklist).
     */
    public function tier(): ?int
    {
        return self::tierFromText($this->jenis_penghargaan) ?? self::tierFromText($this->nama_penghargaan);
    }

    protected function casts(): array
    {
        return [
            'tanggal_sk_penghargaan' => 'date',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
