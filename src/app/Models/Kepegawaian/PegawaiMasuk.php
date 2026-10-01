<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

class PegawaiMasuk extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_masuk';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
        ];
    }
}
