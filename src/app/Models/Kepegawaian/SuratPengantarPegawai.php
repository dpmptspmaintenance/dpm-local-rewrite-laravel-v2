<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris pegawai pada satu Surat Pengantar — snapshot nama/jabatan/pangkat-
 * golongan/instansi saat surat dibuat (bukan live-join ke pegawai_profil),
 * supaya riwayat surat lama tak berubah kalau data pegawai di-update.
 */
class SuratPengantarPegawai extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_pengantar_pegawai';

    protected $guarded = [];

    protected $casts = [
        'urutan' => 'integer',
    ];

    /**
     * "Tempat, Tanggal Lahir" gabungan untuk blok detail surat — fallback "-"
     * bila keduanya kosong.
     */
    public function getTempatTanggalLahirAttribute(): string
    {
        $bagian = array_filter([trim((string) $this->tempat_lahir), trim((string) $this->tanggal_lahir)]);

        return $bagian === [] ? '-' : implode(', ', $bagian);
    }

    public function suratPengantar(): BelongsTo
    {
        return $this->belongsTo(SuratPengantar::class, 'surat_pengantar_id');
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
