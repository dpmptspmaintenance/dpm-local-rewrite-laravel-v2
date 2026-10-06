<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Master jenis barang (legacy `sekre_bangkit_master_jenis_barang`). */
class MasterJenisBarang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_master_jenis_barang';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['jenis_barang'];
}
