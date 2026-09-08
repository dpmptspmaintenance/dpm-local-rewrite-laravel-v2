<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StokOpnameHeader extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'stok_opname_header';

    public $timestamps = false;

    protected $fillable = [
        'kode_opname',
        'tanggal_opname',
        'nama_kegiatan',
        'ttd_kiri',
        'ttd_tengah',
        'ttd_kanan',
        'ttd_sekretaris',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_opname' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(StokOpnameDetail::class, 'id_opname_header');
    }
}
