<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Master lokasi barang (legacy `sekre_bangkit_master_lokasi`). */
class MasterLokasi extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sekre_bangkit_master_lokasi';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['lokasi', 'keterangan'];
}
