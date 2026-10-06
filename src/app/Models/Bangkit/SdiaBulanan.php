<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Saldo bulanan barang persediaan (legacy `sdia_bulanan`). */
class SdiaBulanan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_bulanan';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'id_katkit_bidang',
        'tahun',
        'bulan',
        'id_transaksi_barang_masuk',
        'id_transaksi_barang_keluar',
        'saldo_awal',
        'saldo_masuk',
        'harga_satuan',
        'saldo_keluar',
        'saldo_akhir',
        'is_aktif',
        'created_at',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'saldo_awal' => 'float',
            'saldo_masuk' => 'float',
            'harga_satuan' => 'float',
            'saldo_keluar' => 'float',
            'saldo_akhir' => 'float',
            'is_aktif' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
