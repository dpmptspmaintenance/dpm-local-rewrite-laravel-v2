<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'hari_libur';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
