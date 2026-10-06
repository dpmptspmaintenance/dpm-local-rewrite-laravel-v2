<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris DAFTAR URUT KEPANGKATAN (DUK) hasil impor PDF — tersimpan per
 * batch impor (lihat DukImpor), bukan snapshot tunggal.
 *
 * Masa kerja di sini NILAI RESMI dari dokumen (dihitung kepegawaian),
 * bukan turunan NIP seperti di modul penghargaan/rekap — karena DUK
 * memang membawa perhitungan masa kerja sendiri.
 */
class Duk extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'duk';

    protected $guarded = [];

    protected $casts = [
        'diimpor_pada' => 'datetime',
    ];

    public function impor(): BelongsTo
    {
        return $this->belongsTo(DukImpor::class, 'duk_impor_id');
    }

    /** "28 tahun 12 bulan" — format masa kerja seperti di dokumen DUK. */
    public function masaKerjaTeks(): string
    {
        $tahun = $this->masa_kerja_tahun ?? 0;
        $bulan = $this->masa_kerja_bulan ?? 0;

        return "{$tahun} tahun {$bulan} bulan";
    }
}
