<?php

namespace App\Models\Certificate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateField extends Model
{
    protected $table = 'certificate_fields';

    protected $fillable = [
        'template_id',
        'key',
        'label',
        'x_mm',
        'y_mm',
        'w_mm',
        'h_mm',
        'font_family',
        'font_size_pt',
        'color',
        'bold',
        'italic',
        'align',
        'valign',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'x_mm' => 'float',
            'y_mm' => 'float',
            'w_mm' => 'float',
            'h_mm' => 'float',
            'font_size_pt' => 'float',
            'bold' => 'boolean',
            'italic' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }
}
