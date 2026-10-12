<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris pegawai pada satu Surat Pengantar Kenaikan Pangkat — snapshot identitas
 * saat surat dibuat (bukan live-join ke pegawai_profil).
 */
class SuratPengantarKpPegawai extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_pengantar_kp_pegawai';

    protected $guarded = [];

    protected $casts = [
        'urutan' => 'integer',
    ];

    /** "Tempat, Tanggal Lahir" gabungan untuk blok detail surat. */
    public function getTempatTanggalLahirAttribute(): string
    {
        $bagian = array_filter([trim((string) $this->tempat_lahir), trim((string) $this->tanggal_lahir)]);

        return $bagian === [] ? '-' : implode(', ', $bagian);
    }

    public function suratPengantarKp(): BelongsTo
    {
        return $this->belongsTo(SuratPengantarKp::class, 'surat_pengantar_kp_id');
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
