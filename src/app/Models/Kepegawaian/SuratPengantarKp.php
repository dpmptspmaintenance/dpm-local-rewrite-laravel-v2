<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Riwayat Surat Pengantar Kenaikan Pangkat — satu baris per surat, isi tujuan/
 * paragraf + daftar pegawai (hasMany) + snapshot dasar hukum & tembusan.
 */
class SuratPengantarKp extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_pengantar_kp';

    protected $guarded = [];

    public function pegawai(): HasMany
    {
        return $this->hasMany(SuratPengantarKpPegawai::class, 'surat_pengantar_kp_id')->orderBy('urutan');
    }
}
