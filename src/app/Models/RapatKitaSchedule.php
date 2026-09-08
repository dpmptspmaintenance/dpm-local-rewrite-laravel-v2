<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RapatKitaSchedule extends Model
{
    protected $table = 'rapat_kita_schedule_list';

    protected $fillable = [
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'lokasi',
        'pelaksana',
        'dihadiri',
        'dispo',
        'id_rapat_sebelumnya',
        'bidang_pembuat_jadwal',
        'created_by',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'is_aktif' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', 1);
    }

    public function notulen()
    {
        return $this->hasOne(RapatKitaNotulen::class, 'id_kegiatan');
    }
}
