<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpnameDetail extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'stok_opname_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_opname_header',
        'id_barang',
        'harga_satuan',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'alasan_selisih',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'float',
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function opnameHeader(): BelongsTo
    {
        return $this->belongsTo(StokOpnameHeader::class, 'id_opname_header');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class, 'id_barang');
    }
}
