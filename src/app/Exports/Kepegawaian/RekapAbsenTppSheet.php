<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenTpp;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet 3 export Rekap Absen Bulanan: daftar Data Dukung TPP
 * ("Data Pegawai Tidak Masuk Kerja" dari tpp.semarangkota.go.id/izin)
 * untuk rekap yang sedang tampil. Tabel TPP tidak punya FK ke rekap —
 * pencocokan lewat nip+tahun+bulan (subquery ke rekap terfilter).
 */
class RekapAbsenTppSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private int $nomor = 0;

    /**
     * @param  Builder  $rekapQuery  query RekapAbsen yang sudah terfilter
     */
    public function __construct(private readonly Builder $rekapQuery) {}

    public function query(): Builder
    {
        $this->nomor = 0;

        $rekapIds = $this->rekapQuery->reorder()->select('rekap_absen.id');

        return RekapAbsenTpp::query()
            ->whereExists(function ($query) use ($rekapIds): void {
                $query->selectRaw('1')
                    ->from('rekap_absen as r')
                    ->whereColumn('r.nip', 'rekap_absen_tpp.nip')
                    ->whereColumn('r.tahun', 'rekap_absen_tpp.tahun')
                    ->whereColumn('r.bulan', 'rekap_absen_tpp.bulan')
                    ->whereIn('r.id', $rekapIds);
            })
            ->with('tanggal')
            ->orderBy('nama')
            ->orderBy('bulan')
            ->orderBy('tanggal_mulai');
    }

    public function title(): string
    {
        return 'Data Dukung e-TPP';
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama', 'Periode', 'Jenis', 'Tanggal', 'Jumlah Hari',
            'Keterangan', 'File Pendukung', 'Tautan',
        ];
    }

    public function map($row): array
    {
        /** @var RekapAbsenTpp $row */
        return [
            ++$this->nomor,
            $row->nip,
            $row->nama,
            (RekapAbsen::BULAN[$row->bulan] ?? 'Bulan '.$row->bulan).' '.$row->tahun,
            $row->jenis,
            $row->tanggal
                ->map(fn ($tanggal): string => $tanggal->tanggal?->format('d-m-Y') ?? '-')
                ->implode(', ') ?: '—',
            (int) $row->jumlah_hari,
            $row->keterangan,
            $row->statusFileTeks(),
            $row->tautanFile(),
        ];
    }
}
