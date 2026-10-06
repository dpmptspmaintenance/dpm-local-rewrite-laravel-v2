<?php

namespace App\Models\Bangkit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kegiatan SDIA (legacy `sdia_kegiatan`). */
class SdiaKegiatan extends Model
{
    protected $connection = 'bangkit';

    protected $table = 'sdia_kegiatan';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = ['id_katkit_bidang', 'tahun', 'nama_kegiatan', 'is_aktif', 'modified_by'];

    protected function casts(): array
    {
        return ['is_aktif' => 'boolean'];
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(KatkitBidang::class, 'id_katkit_bidang', 'Id');
    }
}
