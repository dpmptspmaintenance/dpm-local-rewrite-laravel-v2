<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris pegawai pada satu Surat Tugas — snapshot nama/jabatan/pangkat-golongan
 * pada saat surat dibuat (bukan live-join ke pegawai_profil), supaya riwayat
 * surat lama tidak ikut berubah kalau data pegawai di-update belakangan.
 */
class SuratTugasPegawai extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_tugas_pegawai';

    protected $guarded = [];

    protected $casts = [
        'urutan' => 'integer',
    ];

    public function suratTugas(): BelongsTo
    {
        return $this->belongsTo(SuratTugas::class, 'surat_tugas_id');
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }
}
