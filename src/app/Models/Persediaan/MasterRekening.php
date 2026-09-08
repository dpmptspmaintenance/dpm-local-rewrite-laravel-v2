<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRekening extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'master_rekening';

    protected $fillable = [
        'kode_rekening',
        'nama_rekening',
        'parent_kode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function barang(): HasMany
    {
        return $this->hasMany(MasterBarang::class, 'kode_rekening', 'kode_rekening');
    }
}
