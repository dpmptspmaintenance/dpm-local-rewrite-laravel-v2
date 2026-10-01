<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\Cuti;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet rincian export Rekap Cuti Tahunan: daftar SEMUA baris cuti untuk satu
 * tahun (SEMUA jenis — Sakit/Melahirkan/Alasan Penting ikut tampil walau tak
 * memotong jatah tahunan; itu sengaja, rincian mentah di balik angka sheet 1).
 *
 * Tahun = YEAR(tanggal_mulai_diajukan) — definisi sama dengan rekapTahunan().
 * Berbeda dari rekap dalam dua hal (sengaja):
 *   - rekap mengabaikan baris lintas tahun (mulai & selesai beda tahun);
 *     sheet ini tetap memuatnya di tahun mulainya (datanya tak hilang).
 *   - rekap hanya menghitung pegawai aktif; sheet ini memuat semua baris
 *     cuti tahun itu termasuk NIP nonaktif/tak-macth profil.
 *
 * Kolom "Hari" = Cuti::jumlahHari() — hitungan tanggal bila positif, kalau
 * tanggal bermasalah (sama/terbalik) pakai durasi_hari kolom DB yang bisa
 * dikoreksi manual lewat form edit.
 *
 * WithStrictNullComparison WAJIB ada (alasan sama dengan CutiTahunanSheet:
 * nilai 0/'' akan hilang jadi sel kosong tanpa interface ini).
 */
class CutiPerTahunSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** Penomoran baris. */
    private int $nomor = 0;

    public function __construct(private readonly int $tahun) {}

    public function query(): Builder
    {
        return Cuti::query()
            ->whereYear('tanggal_mulai_diajukan', $this->tahun)
            ->with('profil')
            ->orderBy('tanggal_mulai_diajukan')
            ->orderBy('nip');
    }

    public function title(): string
    {
        return 'Cuti '.$this->tahun;
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama Pegawai', 'Jenis', 'Tanggal Mulai', 'Tanggal Selesai',
            'Hari', 'No. Surat', 'Keperluan',
        ];
    }

    public function map($row): array
    {
        /** @var Cuti $row */
        return [
            ++$this->nomor,
            $row->nip,
            // Nama dari profil bila cuti.nama kosong — sama dengan rekap.
            $row->nama ?: ($row->profil?->nama ?? $row->nip),
            $row->jenis,
            $row->tanggal_mulai_diajukan?->format('d-m-Y'),
            $row->tanggal_selesai_diajukan?->format('d-m-Y'),
            $row->jumlahHari(),
            $row->no_surat,
            $row->keperluan,
        ];
    }
}
