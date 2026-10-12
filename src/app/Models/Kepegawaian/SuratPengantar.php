<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Riwayat Surat Pengantar yang pernah digenerate — satu baris per surat, berisi
 * perihal/tujuan/paragraf + daftar pegawai (hasMany SuratPengantarPegawai) +
 * snapshot dasar hukum & tembusan saat dibuat.
 */
class SuratPengantar extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_pengantar';

    protected $guarded = [];

    public function pegawai(): HasMany
    {
        return $this->hasMany(SuratPengantarPegawai::class, 'surat_pengantar_id')->orderBy('urutan');
    }
}
