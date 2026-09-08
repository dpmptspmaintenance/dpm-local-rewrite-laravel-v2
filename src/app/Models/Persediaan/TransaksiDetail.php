<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiDetail extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'transaksi_detail';

    public $timestamps = true;

    protected $fillable = [
        'id_header',
        'id_barang',
        'qty',
        'harga_satuan',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(TransaksiHeader::class, 'id_header');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class, 'id_barang');
    }
}
