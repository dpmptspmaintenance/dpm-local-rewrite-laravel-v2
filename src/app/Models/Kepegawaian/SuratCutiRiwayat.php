<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris riwayat Generator Surat Cuti (satu rincian cuti yang pernah
 * dinomori). Dikelompokkan per batch. MURNI arsip — tidak dibaca oleh
 * perhitungan cuti mana pun (rekap/kuota/total mengabaikan tabel ini).
 */
class SuratCutiRiwayat extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'riwayat_generator_cuti';

    /** Batch dibuat lewat halaman Generator Surat Cuti. */
    public const SUMBER_MANUAL = 'manual';

    /** Batch dikirim Chrome extension lewat API. */
    public const SUMBER_EXTENSION = 'extension';

    protected $guarded = ['id'];

    protected $casts = [
        'hari' => 'integer',
        'is_duplikat' => 'boolean',
        'sisa' => 'array',
    ];
}
