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
 * Unduh daftar pegawai yang masih punya tanggal "Belum Konfirmasi":
 * baris harian berstatus konfirmasi yang tanggalnya TIDAK tercakup data
 * dukung absen maupun data dukung e-TPP (relasi yang sama dengan kolom
 * tabel "Belum Konfirmasi"). Satu baris per pegawai, tanggal digabung.
 */
class RekapAbsenBelumKonfirmasiExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private int $nomor = 0;

    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        $this->nomor = 0;

        return $this->query
            ->whereHas('harianKonfirmasiTanpaDukung')
            ->with(['harianKonfirmasiTanpaDukung' => fn ($query) => $query->orderBy('tanggal')]);
    }

    public function title(): string
    {
        return 'Belum Konfirmasi';
    }

    public function headings(): array
    {
        return ['No', 'NIP', 'Nama', 'Jabatan', 'Jumlah', 'Tanggal Belum Konfirmasi'];
    }

    public function map($row): array
    {
        /** @var RekapAbsen $row */
        return [
            ++$this->nomor,
            $row->nip,
            $row->nama,
            $row->jabatan,
            $row->harianKonfirmasiTanpaDukung->count(),
            $row->harianKonfirmasiTanpaDukung
                ->map(fn ($harian): ?string => self::tanggalIndo($harian->tanggal))
                ->filter()
                ->implode(', '),
        ];
    }

    /**
     * 22-10-2026 → "22 Oktober 2026".
     */
    private static function tanggalIndo(?\DateTimeInterface $tanggal): ?string
    {
        if ($tanggal === null) {
            return null;
        }

        $bulan = RekapAbsen::BULAN[(int) $tanggal->format('n')] ?? $tanggal->format('m');

        return $tanggal->format('j').' '.$bulan.' '.$tanggal->format('Y');
    }
}
