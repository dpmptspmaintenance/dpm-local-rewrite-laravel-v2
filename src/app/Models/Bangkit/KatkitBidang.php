<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;

/** Bidang OPD (legacy `katkit_bidang`) — dipakai scoping SDIA per bidang. */
class KatkitBidang extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'katkit_bidang';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['nama_bidang'];
}
