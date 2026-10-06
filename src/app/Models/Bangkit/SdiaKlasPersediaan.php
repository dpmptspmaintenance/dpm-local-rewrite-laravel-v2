<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Klasifikasi persediaan (legacy `sdia_klas_persediaan`). */
class SdiaKlasPersediaan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_klas_persediaan';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['rek_klas', 'nama_klas', 'is_aktif'];

    protected function casts(): array
    {
        return ['is_aktif' => 'boolean'];
    }
}
