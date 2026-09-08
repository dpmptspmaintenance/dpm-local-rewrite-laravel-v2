<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RapatKitaNotulen extends Model
{
    protected $table = 'rapat_kita_notulen';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_kegiatan',
        'tanggal',
        'jam_mulai',
        'nama_kegiatan',
        'ketua',
        'sekretaris',
        'anggota',
        'susunan',
        'pembahasan',
        'hasil',
        'nama_pimpinan',
        'jabatan_pimpinan',
        'nama_notulis',
        'jabatan_notulis',
        'created_by',
        'foto_1',
        'foto_2',
        'foto_3',
        'foto_surat',
        'user_id_pembuat_notulen',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function kegiatan()
    {
        return $this->belongsTo(RapatKitaSchedule::class, 'id_kegiatan');
    }
}
