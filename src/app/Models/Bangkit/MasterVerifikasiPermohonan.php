<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Master opsi verifikasi permohonan (legacy `sekre_bangkit_master_verifikasi_permohonan_perbaikan`). */
class MasterVerifikasiPermohonan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_master_verifikasi_permohonan_perbaikan';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['verifikasi'];
}
