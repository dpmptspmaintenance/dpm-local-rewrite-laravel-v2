<?php

namespace App\Exports\Kepegawaian;

use App\Filament\Kepegawaian\Pages\MatriksKebutuhanDiklat;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Export "Matriks Kebutuhan Diklat" — jabatan × jenis, isi sel = jumlah
 * event. Sumbernya MatriksKebutuhanDiklat::buildMatriks()/totalPerJenis(),
 * method static yang sama dipakai halaman, jadi angka di file Excel/PDF tak
 * bisa beda dari yang tampil di layar.
 *
 * Kolom jenis dinamis (dari data), jadi headings()/map()/lastColumn() semua
 * dihitung dari matriks yang sama, bukan daftar tetap.
 *
 * WithStrictNullComparison Wajib: sel bernilai 0 (jabatan tanpa event pada
 * satu jenis) hilang jadi sel kosong (0 == null) tanpa interface ini.
 */
class MatriksKebutuhanSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** Penomoran baris — collection() mereset ini tiap dipanggil. */
    private int $nomor = 0;

    /** @var list<string>|null Kolom jenis, di-cache dari collection() supaya headings()/map() konsisten. */
    private ?array $jenisCache = null;

    public function __construct(private readonly int $tahun) {}

    public function collection(): Collection
    {
        $this->nomor = 0;

        $matriks = MatriksKebutuhanDiklat::buildMatriks($this->tahun);
        $this->jenisCache = $matriks['jenis'];

        return collect($matriks['baris']);
    }

    /** @return list<string> */
    private function jenis(): array
    {
        return $this->jenisCache ??= MatriksKebutuhanDiklat::buildMatriks($this->tahun)['jenis'];
    }

    public function title(): string
    {
        return 'Matriks Kebutuhan Diklat';
    }

    public function headings(): array
    {
        return ['No', 'Jabatan', ...$this->jenis(), 'Total'];
    }

    public function map($row): array
    {
        $mapped = [++$this->nomor, $row['jabatan']];

        foreach ($this->jenis() as $j) {
            $mapped[] = (int) $row['sel'][$j]['event'];
        }

        $mapped[] = (int) $row['total_event'];

        return $mapped;
    }

    private function judul(): string
    {
        return 'Matriks Kebutuhan Diklat Seluruh Jabatan Tahun '.$this->tahun;
    }

    /** No + Jabatan + tiap jenis + Total. */
    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex(2 + count($this->jenis()) + 1);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->lastColumn();

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                // Baris total per jenis (tfoot di halaman), ditulis sebelum
                // insertNewRowBefore supaya barisnya jadi baris terakhir data.
                $totalPerJenis = MatriksKebutuhanDiklat::totalPerJenis($this->tahun);
                $totalRow = $sheet->getHighestRow() + 1;

                $sheet->setCellValue('A'.$totalRow, '');
                $sheet->setCellValue('B'.$totalRow, 'Total');

                $col = 3; // C = kolom jenis pertama
                $grandTotal = 0;
                foreach ($this->jenis() as $j) {
                    $event = $totalPerJenis[$j]['event'] ?? 0;
                    $sheet->setCellValue([$col, $totalRow], $event);
                    $grandTotal += $event;
                    $col++;
                }
                $sheet->setCellValue([$col, $totalRow], $grandTotal);
                $sheet->getStyle('A'.$totalRow.':'.$lastColumn.$totalRow)->getFont()->setBold(true);

                // Judul di baris paling atas, dorong semua ke bawah 1 baris.
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
