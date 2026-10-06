<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Riwayat pergantian pemegang barang (legacy `sekre_bangkit_history_data_barang`). */
class HistoryBarang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_history_data_barang';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'pemegang_lama',
        'pemegang_baru',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
