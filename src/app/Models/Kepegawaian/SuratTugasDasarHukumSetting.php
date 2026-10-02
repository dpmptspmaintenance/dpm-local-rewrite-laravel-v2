<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

/**
 * Daftar dasar hukum BAKU yang berlaku untuk semua Surat Tugas (mis. Perda
 * APBD tahun berjalan, Perwal Penjabaran APBD) — bisa diedit/ditambah/dihapus
 * admin di halaman Pengaturan Dasar Hukum, tanpa perlu ganti file template
 * .docx tiap kali ada Perda/Perwal baru. Tiap generate Surat Tugas, baris di
 * sini digabung dengan dasar hukum tambahan (opsional, khusus surat itu saja)
 * jadi satu daftar utuh pada dokumen hasil.
 */
class SuratTugasDasarHukumSetting extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'surat_tugas_dasar_hukum_setting';

    protected $guarded = [];

    protected $casts = [
        'urutan' => 'integer',
    ];
}
