<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

/**
 * Kuota cuti tahunan per pegawai per tahun. Tanpa baris untuk (nip, tahun)
 * berarti jatah default Cuti::KUOTA_TAHUNAN (12) — baris yang ada adalah
 * override (0 untuk PPPK/CPNS yang masuk tahun itu, atau nilai lain).
 */
class CutiKuotaTahunan extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'cuti_kuota_tahunan';

    public $timestamps = true;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'kuota_hari' => 'integer',
        ];
    }
}
