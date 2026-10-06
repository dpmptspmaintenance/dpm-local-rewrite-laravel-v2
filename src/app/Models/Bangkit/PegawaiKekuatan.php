<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Daftar pegawai (legacy `sekre_pegawai_kekuatan`) — dipakai lookup nama pemegang barang. */
class PegawaiKekuatan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_pegawai_kekuatan';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['nama'];
}
