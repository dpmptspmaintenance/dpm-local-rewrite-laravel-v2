<?php

namespace App\Models\Certificate;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateDataset extends Model
{
    protected $table = 'certificate_datasets';

    protected $fillable = [
        'template_id',
        'created_by',
        'original_name',
        'headers',
        'row_count',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'row_count' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(CertificateDatasetRow::class, 'dataset_id')->orderBy('row_index');
    }
}
