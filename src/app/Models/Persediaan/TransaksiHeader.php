<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiHeader extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'transaksi_header';

    protected $fillable = [
        'kode_transaksi',
        'no_bukti',
        'jenis_mutasi',
        'status',
        'lampiran',
        'tanggal_transaksi',
        'pihak_terkait',
        'alasan_pengambilan',
        'keterangan',
        'ttd_kiri',
        'ttd_tengah',
        'ttd_kanan',
        'ttd_sekretaris',
        'ttd_bmd',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'lampiran' => 'array',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class, 'id_header');
    }
}
