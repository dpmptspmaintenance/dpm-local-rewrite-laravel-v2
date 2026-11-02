<?php

namespace App\Models\Certificate;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateGeneration extends Model
{
    protected $table = 'certificate_generations';

    protected $fillable = [
        'template_id',
        'dataset_id',
        'created_by',
        'mode',
        'file_name',
        'row_count',
    ];

    protected function casts(): array
    {
        return [
            'row_count' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(CertificateDataset::class, 'dataset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
