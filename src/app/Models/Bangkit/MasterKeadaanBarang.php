<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Master keadaan barang (legacy `sekre_bangkit_master_keadaan_barang`). */
class MasterKeadaanBarang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_master_keadaan_barang';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['keadaan_barang'];
}
