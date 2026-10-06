<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat pangkat (banyak baris per pegawai) dari impor JSON SISDM — lihat
 * PegawaiImportService. Full-snapshot replace per NIP tiap impor.
 */
class PegawaiRiwayatPangkat extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_riwayat_pangkat';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_sk_pangkat' => 'date',
            'tmt_sk_pangkat' => 'date',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
