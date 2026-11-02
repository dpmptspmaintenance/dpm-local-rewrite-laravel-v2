<?php

namespace App\Models\Certificate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateDatasetRow extends Model
{
    protected $table = 'certificate_dataset_rows';

    protected $fillable = [
        'dataset_id',
        'row_index',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'row_index' => 'integer',
            'data' => 'array',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(CertificateDataset::class, 'dataset_id');
    }
}
