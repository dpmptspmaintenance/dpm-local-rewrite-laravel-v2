<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\Cuti;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Rekap cuti tahunan per pegawai, pivot per tahun (satu pasang kolom
 * Terpakai/Sisa per tahun). Hasil kecil (terbatas jumlah pegawai), jadi
 * FromCollection — query ber-agregat tidak bisa di-chunk by id.
 *
 * Implements Export agar bisa di-Excel::download() sendiri dalam xlsx/PDF,
 * mirip KompetensiRekapSheet.
 *
 * WithStrictNullComparison WAJIB ada: PhpSpreadsheet's fromArray() (dipakai
 * internal oleh WithMapping) membandingkan tiap nilai ke null pakai `==`
 * longgar secara default — dan di PHP, `0 == null` itu true. Tanpa interface
 * ini, setiap "Terpakai"/"Sisa" yang nilainya persis 0 hilang jadi sel kosong
 * di file Excel (bukan tertulis "0"), padahal di halaman web tetap benar
 * (bug ini murni di lapisan tulis-Excel, ketemu saat verifikasi kolom No).
 */
class CutiTahunanSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** Penomoran baris — maatwebsite/excel memanggil map() berurutan per baris collection(). */
    private int $nomor = 0;

    /**
     * @param  int[]  $tahun
     */
    public function __construct(private readonly array $tahun) {}

    public function collection(): Collection
    {
        // Reset penomoran di sini — collection() selalu dipanggil sebelum
        // map() pada alur maatwebsite/excel, jadi nomor tak akan drift
        // meski objek sheet yang sama dipakai berulang.
        $this->nomor = 0;

        // Rekap dihitung di PHP (kolom Sisa per tahun mandiri, Total
        // menggabungkannya — lihat Cuti::rekapTahunan()), diurutkan nama.
        return Cuti::rekapTahunan($this->tahun)
            ->sortBy(fn (array $r) => $r['nama'])
            ->values();
    }

    public function title(): string
    {
        return 'Rekap Cuti Tahunan';
    }

    public function headings(): array
    {
        $headings = ['No', 'NIP', 'Nama Pegawai', 'Status Jabatan'];

        foreach ($this->tahun as $y) {
            $headings[] = "Terpakai {$y}";
            $headings[] = "Sisa {$y}";
        }

        $headings[] = 'Penjumlahan';
        $headings[] = 'Total';

        return $headings;
    }

    public function map($row): array
    {
        $mapped = [
            ++$this->nomor,
            $row['nip'],
            $row['nama'],
            $row['status_jabatan'],
        ];

        foreach ($this->tahun as $y) {
            $mapped[] = (int) $row['total_hari_'.$y];
            $mapped[] = (int) $row['sisa_hari_'.$y];
        }

        $mapped[] = $this->rincianTotal($row);
        $mapped[] = (int) $row['total_sisa'];

        return $mapped;
    }

    /**
     * Sama persis dengan CutiTahunanRekap::rincianTotal() di halaman — angka
     * yang dijumlahkan jadi Total, sebelum ditambahkan (mis. "2 + 6 + 9").
     *
     * @param  array<string, mixed>  $row
     */
    private function rincianTotal(array $row): string
    {
        $tahunTerakhir = $this->tahun[array_key_last($this->tahun)];
        $bagian = [];

        foreach ($this->tahun as $y) {
            $sisa = (int) $row['sisa_hari_'.$y];
            $bagian[] = (string) ($y === $tahunTerakhir ? $sisa : min($sisa, Cuti::MAX_BAWAAN));
        }

        return implode(' + ', $bagian);
    }

    private function judul(): string
    {
        return 'Rekap Cuti Tahunan '.implode(' / ', $this->tahun);
    }

    /** Huruf kolom terakhir: No + NIP + Nama + Status Jabatan + (Terpakai/Sisa per tahun) + Penjumlahan + Total. */
    private function lastColumn(): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + (count($this->tahun) * 2) + 2);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $lastColumn = $this->lastColumn();

                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:'.$lastColumn.'1');
                $sheet->setCellValue('A1', $this->judul());
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A2:'.$lastColumn.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A2:'.$lastColumn.'2')->getFont()->setBold(true);
            },
        ];
    }
}
