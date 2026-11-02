<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Riwayat Surat Tugas yang pernah digenerate — satu baris per surat, isi
 * kegiatan (judul/hari-tanggal/waktu/tempat) + daftar pegawai (hasMany
 * SuratTugasPegawai) + snapshot dasar hukum final pada saat dibuat.
 *
 * nomor_naskah/tanggal_naskah/ttd_pengirim SENGAJA tidak disimpan di sini —
 * placeholder itu dibiarkan literal "${...}" di dokumen hasil (diisi nanti
 * di aplikasi Srikandi saat registrasi naskah dinas, bukan tanggung jawab
 * tool ini).
 */
class SuratTugas extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_tugas';

    protected $guarded = [];

    protected $casts = [
        'document_generated_at' => 'datetime',
        'document_edited_at' => 'datetime',
        'override_dihilangkan' => 'boolean',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(SuratTugasPegawai::class, 'surat_tugas_id')->orderBy('urutan');
    }

    /**
     * Sudah pernah dikoreksi manual lewat halaman Dokumen? Dipakai untuk
     * memperingatkan sebelum "Generate Ulang dari Data" menimpa koreksi.
     */
    public function hasManualEdits(): bool
    {
        return $this->document_edited_at !== null;
    }

    public function hasDocument(): bool
    {
        return filled($this->document_docx_path);
    }
}
