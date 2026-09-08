<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterBarang extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'master_barang';

    protected $fillable = [
        'kode_rekening',
        'nama_barang',
        'id_satuan',
        'harga_satuan',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(MasterSatuan::class, 'id_satuan');
    }

    /**
     * Join on kode_rekening (not id) — legacy schema uses kode_rekening as
     * the logical key between the two tables; no real FK exists.
     */
    public function rekening(): BelongsTo
    {
        return $this->belongsTo(MasterRekening::class, 'kode_rekening', 'kode_rekening');
    }
}
