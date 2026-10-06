<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Berkas lampiran DRH (a–e) — lihat migration
 * 2026_10_05_000184_create_drh_satya_lancana_dokumen_table.php untuk arti
 * tiap `jenis`. Digabung jadi 1 PDF oleh DrhSatyaLancanaDocumentService.
 */
class DrhSatyaLancanaDokumen extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'drh_satya_lancana_dokumen';

    protected $guarded = [];

    public const JENIS_LABELS = [
        'a' => 'DRH ditandatangani & diketahui atasan langsung',
        'b' => 'SK CPNS',
        'c' => 'SK Pangkat Terakhir',
        'd' => 'SK Jabatan Terakhir',
        'e' => 'Piagam/Petikan Keppres SLKS tingkat sebelumnya (opsional)',
    ];

    public function drh(): BelongsTo
    {
        return $this->belongsTo(DrhSatyaLancana::class, 'drh_satya_lancana_id');
    }
}
