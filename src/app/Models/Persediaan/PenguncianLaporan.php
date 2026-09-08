<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;

class PenguncianLaporan extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'penguncian_laporan';

    public $timestamps = true;

    protected $fillable = [
        'tahun',
        'bulan',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
