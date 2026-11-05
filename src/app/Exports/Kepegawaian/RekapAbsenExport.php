<?php

namespace App\Exports\Kepegawaian;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Unduh Rekap Absen Bulanan, 3 sheet:
 *  1. "Rekap Absen"     — baris rekap yang sedang tampil (search + filter tabel)
 *  2. "Data Dukung"     — daftar lampiran absen (rekap_absen_data_dukung)
 *  3. "Data Dukung TPP" — daftar data pegawai tidak masuk kerja (rekap_absen_tpp)
 *
 * Sheet 2 & 3 dibatasi pada rekap yang sama dengan sheet 1 (nip+tahun+bulan),
 * jadi isinya konsisten dengan apa yang terlihat di tabel.
 *
 * Query diberikan sebagai Closure agar tiap sheet mendapat builder baru
 * (tidak saling menimpa where/orderBy).
 */
class RekapAbsenExport implements Export, WithMultipleSheets
{
    /**
     * @param  Closure(): Builder  $rekapQuery
     */
    public function __construct(private readonly Closure $rekapQuery) {}

    public function sheets(): array
    {
        return [
            new RekapAbsenSheet(($this->rekapQuery)()),
            new RekapAbsenDataDukungSheet(($this->rekapQuery)()),
            new RekapAbsenTppSheet(($this->rekapQuery)()),
        ];
    }
}
