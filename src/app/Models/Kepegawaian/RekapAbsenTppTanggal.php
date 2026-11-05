<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu tanggal yang dicakup baris data dukung TPP. Dipisah dari induknya
 * supaya pengecekan "tanggal X sudah konfirmasi?" cukup lewat SQL EXISTS.
 */
class RekapAbsenTppTanggal extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'rekap_absen_tpp_tanggal';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
