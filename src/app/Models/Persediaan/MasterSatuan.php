<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterSatuan extends Model
{
    protected $connection = 'persediaan';

    protected $table = 'master_satuan';

    protected $fillable = [
        'nama_satuan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function barang(): HasMany
    {
        return $this->hasMany(MasterBarang::class, 'id_satuan');
    }
}
