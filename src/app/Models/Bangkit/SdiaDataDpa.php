<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Data DPA (rencana barang) (legacy `sdia_data_dpa`). */
class SdiaDataDpa extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_data_dpa';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'id_katkit_bidang',
        'tahun',
        'id_sdia_kegiatan',
        'rek_klas',
        'nama_barang',
        'jumlah_barang',
        'satuan',
        'is_aktif',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_barang' => 'float',
            'is_aktif' => 'boolean',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(SdiaKegiatan::class, 'id_sdia_kegiatan', 'Id');
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(SdiaKlasPersediaan::class, 'rek_klas', 'Id');
    }
}
