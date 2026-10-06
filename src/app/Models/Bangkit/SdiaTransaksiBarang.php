<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Transaksi barang masuk/keluar (legacy `sdia_transaksi_barang`). */
class SdiaTransaksiBarang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_transaksi_barang';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    public const MASUK = 'Barang Masuk';
    public const KELUAR = 'Barang Keluar';

    protected $fillable = [
        'tgl_transaksi',
        'klas_transaksi',
        'id_sdia_data_dpa',
        'id_sdia_kegiatan',
        'jumlah_transaksi',
        'harga_satuan',
        'id_katkit_bidang',
        'modified_by',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tgl_transaksi' => 'date',
            'jumlah_transaksi' => 'float',
            'harga_satuan' => 'float',
            'is_aktif' => 'boolean',
        ];
    }

    public function dpa(): BelongsTo
    {
        return $this->belongsTo(SdiaDataDpa::class, 'id_sdia_data_dpa', 'Id');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(SdiaKegiatan::class, 'id_sdia_kegiatan', 'Id');
    }
}
