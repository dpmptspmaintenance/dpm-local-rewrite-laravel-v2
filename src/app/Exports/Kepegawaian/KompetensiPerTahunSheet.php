<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\PegawaiKompetensi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet 2 export Matriks Kebutuhan Diklat DAN Evaluasi Kesesuaian Diklat:
 * daftar SEMUA baris pegawai_kompetensi untuk satu tahun (tanpa filter jenis
 * — semua jenis termasuk kosong ikut tampil), sebagai rincian mentah di
 * balik angka-angka ringkas di sheet 1 masing-masing. Kolom "Sesuai Jabatan"
 * menulis Sesuai/Tidak Sesuai per baris (flag manual, kolom sesuai_jabatan —
 * inilah rincian di balik kolom "Sesuai Jabatan" (agregat "N dari M") di
 * sheet 1 Evaluasi Kesesuaian Diklat.
 *
 * Tahun dihitung via PegawaiKompetensi::TANGGAL_ACUAN — sama dengan yang
 * dipakai matriks/peta kebutuhan/kalender/evaluasi — BUKAN via
 * PegawaiKompetensi::scopeFiltered()'s whereYear('tanggal_sertifikat', ...)
 * yang dipakai KompetensiDetailSheet, supaya "tahun" di semua sheet ini
 * berarti persis sama.
 */
class KompetensiPerTahunSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    public function __construct(private readonly int $tahun) {}

    public function query(): Builder
    {
        return PegawaiKompetensi::query()
            ->whereRaw('YEAR('.PegawaiKompetensi::TANGGAL_ACUAN.') = ?', [$this->tahun])
            ->with('profil')
            ->orderBy('nip');
    }

    public function title(): string
    {
        return 'Kompetensi Tahun '.$this->tahun;
    }

    public function headings(): array
    {
        return [
            'NIP', 'Nama Pegawai', 'Jabatan', 'Jenis', 'Sesuai Jabatan', 'Nama Kompetensi', 'Jumlah Jam (JP)',
            'Penyelenggara', 'No. Sertifikat', 'Tanggal Mulai', 'Tanggal Selesai', 'Tanggal Sertifikat',
        ];
    }

    public function map($row): array
    {
        /** @var PegawaiKompetensi $row */
        return [
            $row->nip,
            $row->profil?->nama,
            $row->profil?->jabatan,
            // Jenis kosong/NULL ditulis "(Tanpa jenis)" — jangan biarkan sel
            // kosong, sama seperti kolom di matriks sheet 1.
            blank($row->jenis) ? '(Tanpa jenis)' : $row->jenis,
            // Ditandai manual (kolom sesuai_jabatan, default Sesuai sampai
            // ditinjau — lihat daftar Kompetensi / Riwayat Kompetensi pegawai).
            $row->sesuai_jabatan ? 'Sesuai' : 'Tidak Sesuai',
            $row->nama_kompetensi,
            (int) $row->jumlah_jam,
            $row->penyelenggara,
            $row->nomor_sertifikat,
            optional($row->tanggal_mulai)->format('d-m-Y'),
            optional($row->tanggal_selesai)->format('d-m-Y'),
            optional($row->tanggal_sertifikat)->format('d-m-Y'),
        ];
    }
}
