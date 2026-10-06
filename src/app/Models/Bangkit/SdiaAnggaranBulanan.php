<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Anggaran bulanan terpakai/sisa (legacy `sdia_anggaran_bulanan`). */
class SdiaAnggaranBulanan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_anggaran_bulanan';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'id_katkit_bidang',
        'id_sdia_kegiatan',
        'rek_klas',
        'id_anggaran',
        'tahun',
        'bulan',
        'anggaran_awal',
        'anggaran_terpakai',
        'sisa_anggaran',
        'is_aktif',
        'created_at',
        'modified_at',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'anggaran_awal' => 'float',
            'anggaran_terpakai' => 'float',
            'sisa_anggaran' => 'float',
            'is_aktif' => 'boolean',
            'created_at' => 'datetime',
            'modified_at' => 'datetime',
        ];
    }
}
