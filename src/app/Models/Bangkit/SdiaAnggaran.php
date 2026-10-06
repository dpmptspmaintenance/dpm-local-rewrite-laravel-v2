<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anggaran master per kegiatan+klasifikasi per bidang (legacy `sdia_anggaran`). */
class SdiaAnggaran extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_anggaran';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'id_katkit_bidang',
        'id_sdia_kegiatan',
        'rek_klas',
        'anggaran',
        'is_aktif',
        'created_at',
        'modified_at',
        'modified_by',
    ];

    protected function casts(): array
    {
        return [
            'anggaran' => 'float',
            'is_aktif' => 'boolean',
            'created_at' => 'datetime',
            'modified_at' => 'datetime',
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
