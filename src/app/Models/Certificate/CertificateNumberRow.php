<?php

namespace App\Models\Certificate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris daftar nomor sertifikat (satu penerima). `nomor_surat` adalah
 * snapshot teks naskah dinas, mis. "800.2/1/2026".
 */
class CertificateNumberRow extends Model
{
    protected $table = 'certificate_number_rows';

    protected $fillable = [
        'activity_id',
        'urutan',
        'nomor',
        'kode',
        'tahun',
        'nomor_surat',
        'nama',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'nomor' => 'integer',
            'tahun' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(CertificateNumberActivity::class, 'activity_id');
    }
}
