<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Master bahan barang (legacy `sekre_bangkit_master_bahan_barang`). */
class MasterBahanBarang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_master_bahan_barang';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['bahan', 'bahan_bakar'];
}
