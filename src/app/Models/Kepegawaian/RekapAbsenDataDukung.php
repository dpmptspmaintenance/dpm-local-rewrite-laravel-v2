<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;

/**
 * Lampiran "Data Dukung" pada rekap absen (mis. Surat Tugas / St Lion Pro
 * untuk hari konfirmasi). Yang disimpan hanya token terenkripsi dari
 * aplikasi presensi; tautan berkas dibentuk saat ditampilkan.
 */
class RekapAbsenDataDukung extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'rekap_absen_data_dukung';

    public const FILE_BASE = 'https://secure-sadewa.semarangkota.go.id/get-file/data-dukung/';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function tautan(): ?string
    {
        $token = trim((string) $this->token);

        return $token === '' ? null : self::FILE_BASE.$token;
    }
}
