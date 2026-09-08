<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sikenut extends Model
{
    protected $table = 'sikenut';

    protected $fillable = [
        'nama_kegiatan',
        'surat_tugas',
        'tanggal_surat',
        'tanggal_acara',
        'tipe_anggaran',
        'anggaran_bulan',
        'tahun',
        'disposisi',
        'bidang',
        'anggaran_bidang',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_acara' => 'date',
            'tahun' => 'integer',
        ];
    }

    public function disposisiUser()
    {
        return $this->belongsTo(User::class, 'disposisi');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
