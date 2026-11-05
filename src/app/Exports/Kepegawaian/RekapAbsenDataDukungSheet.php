<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenDataDukung;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet 2 export Rekap Absen Bulanan: daftar Data Dukung (lampiran absen)
 * untuk rekap yang sedang tampil. Rekap induk di-join hanya untuk
 * identitas + periode, dan tautan berkas dibentuk dari token secure-sadewa.
 */
class RekapAbsenDataDukungSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
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

        return RekapAbsenDataDukung::query()
            ->join('rekap_absen', 'rekap_absen.id', '=', 'rekap_absen_data_dukung.rekap_absen_id')
            ->whereIn('rekap_absen_data_dukung.rekap_absen_id', $rekapIds)
            ->orderBy('rekap_absen.nama')
            ->orderBy('rekap_absen.bulan')
            ->orderBy('rekap_absen_data_dukung.urutan')
            ->select('rekap_absen_data_dukung.*', 'rekap_absen.nip as induk_nip', 'rekap_absen.nama as induk_nama', 'rekap_absen.tahun as induk_tahun', 'rekap_absen.bulan as induk_bulan');
    }

    public function title(): string
    {
        return 'Data Dukung';
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama', 'Periode', 'Tanggal Mulai', 'Tanggal Selesai',
            'Jenis', 'Keterangan', 'Tautan',
        ];
    }

    public function map($row): array
    {
        /** @var RekapAbsenDataDukung $row */
        return [
            ++$this->nomor,
            $row->induk_nip,
            $row->induk_nama,
            ($row->induk_bulan !== null
                ? (RekapAbsen::BULAN[$row->induk_bulan] ?? 'Bulan '.$row->induk_bulan).' '.$row->induk_tahun
                : null),
            $row->tanggal_mulai?->format('d-m-Y'),
            $row->tanggal_selesai?->format('d-m-Y'),
            $row->jenis,
            $row->keterangan,
            $row->tautan(),
        ];
    }
}
