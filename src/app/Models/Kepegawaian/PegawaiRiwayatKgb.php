<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat KGB (Kenaikan Gaji Berkala) — banyak baris per pegawai dari impor
 * JSON SISDM (riwayat_kgb.riwayat_kgb_detail). Lihat PegawaiImportService.
 * Full-snapshot replace per NIP tiap impor.
 */
class PegawaiRiwayatKgb extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_riwayat_kgb';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_sk_kgb' => 'date',
            'tmt_kgb' => 'date',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
