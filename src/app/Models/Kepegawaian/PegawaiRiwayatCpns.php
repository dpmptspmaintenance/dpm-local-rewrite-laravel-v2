<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat CPNS (satu baris per pegawai) dari impor JSON SISDM — lihat
 * PegawaiImportService. Full-snapshot replace per NIP tiap impor.
 */
class PegawaiRiwayatCpns extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_riwayat_cpns';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_sk' => 'date',
            'tmt_sk' => 'date',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
