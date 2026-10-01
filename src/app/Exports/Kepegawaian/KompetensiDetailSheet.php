<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\PegawaiKompetensi;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class KompetensiDetailSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /** Nomor urut baris (map() dipanggil berurutan per baris oleh maatwebsite). */
    private int $nomor = 0;

    /**
     * @param  array{q?: string, tahun?: int, jenis?: string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    public function query(): Builder
    {
        return PegawaiKompetensi::query()->filtered($this->filters)->with('profil');
    }

    public function title(): string
    {
        return 'Detail Kompetensi';
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP', 'Nama Pegawai', 'Jenis', 'Nama Kompetensi', 'Jumlah Jam',
            'Penyelenggara', 'No. Sertifikat', 'Tanggal Mulai', 'Tanggal Selesai', 'Tanggal Sertifikat',
        ];
    }

    public function map($row): array
    {
        /** @var PegawaiKompetensi $row */
        return [
            ++$this->nomor,
            $row->nip,
            $row->profil?->nama,
            $row->jenis,
            $row->nama_kompetensi,
            $row->jumlah_jam,
            $row->penyelenggara,
            $row->nomor_sertifikat,
            optional($row->tanggal_mulai)->format('d-m-Y'),
            optional($row->tanggal_selesai)->format('d-m-Y'),
            optional($row->tanggal_sertifikat)->format('d-m-Y'),
        ];
    }
}
