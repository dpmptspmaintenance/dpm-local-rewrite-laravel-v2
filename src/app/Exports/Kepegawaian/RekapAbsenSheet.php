<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\RekapAbsen;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet 1 export Rekap Absen Bulanan: ringkasan per pegawai + periode,
 * mengikuti filter/pencarian/urutan tabel yang sedang tampil.
 *
 * WithStrictNullComparison WAJIB: tanpa itu nilai 0 hilang jadi sel kosong.
 */
class RekapAbsenSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private int $nomor = 0;

    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        $this->nomor = 0;

        return $this->query->withCount('harianKonfirmasiTanpaDukung');
    }

    public function title(): string
    {
        return 'Rekap Absen';
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama', 'Jabatan', 'Periode', 'Hari Kerja',
            'Hadir', 'Tidak Hadir', 'Konfirmasi', 'Belum Konfirmasi',
            'Keterlambatan', 'Diunggah', 'Diunggah Oleh', 'Sumber',
        ];
    }

    public function map($row): array
    {
        /** @var RekapAbsen $row */
        return [
            ++$this->nomor,
            $row->nip,
            $row->nama,
            $row->jabatan,
            $row->labelPeriode(),
            (int) $row->hari_kerja,
            (int) $row->jumlah_hadir,
            (int) $row->jumlah_tidak_hadir,
            (int) $row->jumlah_konfirmasi,
            (int) $row->harian_konfirmasi_tanpa_dukung_count,
            $row->total_keterlambatan,
            $row->diunggah_at?->format('d-m-Y H:i'),
            $row->diunggah_oleh_nama,
            $row->sumber_label ?: $row->sumber,
        ];
    }
}
