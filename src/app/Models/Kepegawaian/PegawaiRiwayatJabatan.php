<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat jabatan (banyak baris per pegawai) dari impor JSON SISDM — lihat
 * PegawaiImportService. Full-snapshot replace per NIP tiap impor.
 */
class PegawaiRiwayatJabatan extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_riwayat_jabatan';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_sk_jabatan' => 'date',
            'tmt_sk_jabatan' => 'date',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
