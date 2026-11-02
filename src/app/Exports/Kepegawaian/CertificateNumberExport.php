<?php

namespace App\Exports\Kepegawaian;

use App\Models\Certificate\CertificateNumberActivity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Unduh daftar nomor sertifikat. Format mengikuti naskah manual: satu blok
 * per kegiatan berisi judul kegiatan, tempat/tanggal, lalu tabel
 * No | Kode | Tahun | Nomor Surat | Nama | Keterangan.
 *
 * Baris "Kegiatan" dan "Tempat/Tanggal" di-merge B:F supaya teks panjang
 * tidak melebarkan kolom B (AutoSize dilewati untuk sel hasil merge) dan
 * tidak memaksa user scroll horizontal.
 *
 * Kolom "Nomor Surat" dipaksa string (StringValueBinder) supaya tidak
 * diubah Excel menjadi angka/tanggal.
 */
class CertificateNumberExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithEvents, WithTitle
{
    private const COLUMN_WIDTHS = [
        'A' => 6,
        'B' => 14,
        'C' => 8,
        'D' => 22,
        'E' => 45,
        'F' => 30,
    ];

    private const LAST_COLUMN = 'F';

    /** Perkiraan jumlah karakter per baris pada area merge B:F. */
    private const MERGED_LINE_LENGTH = 105;

    /** @var list<CertificateNumberActivity> */
    private array $activities;

    /**
     * @var list<array{kegiatan: int, tempat: int, header: int, last: int}>
     */
    private array $blocks = [];

    /**
     * @param  list<CertificateNumberActivity>  $activities
     */
    public function __construct(array $activities, private string $title = 'Nomor Sertifikat')
    {
        $this->activities = $activities;
    }

    public function array(): array
    {
        $data = [];
        $this->blocks = [];

        foreach ($this->activities as $index => $activity) {
            if ($index > 0) {
                // Sel kosong (bukan array kosong) supaya baris pemisah tidak
                // dibuang oleh writer Excel.
                $data[] = [''];
            }

            // Label di kolom A, nilai di kolom C (A:B dan C:F di-merge saat
            // AfterSheet) supaya kolom B tetap sempit.
            $kegiatanRow = count($data) + 1;
            $data[] = ['Kegiatan', '', $this->plainText((string) $activity->kegiatan)];

            $tempatRow = count($data) + 1;
            $data[] = ['Tempat/Tanggal', '', trim(implode(' ', array_filter([
                (string) ($activity->tempat ?? ''),
                $activity->tanggal?->locale('id')->translatedFormat('j F Y') ?? '',
            ])))];

            $data[] = [''];

            $headerRow = count($data) + 1;
            $data[] = ['No', 'Kode', 'Tahun', 'Nomor Surat', 'Nama', 'Keterangan'];

            foreach ($activity->rows()->get() as $row) {
                $data[] = [
                    (string) $row->nomor,
                    (string) $row->kode,
                    (string) $row->tahun,
                    (string) $row->nomor_surat,
                    (string) $row->nama,
                    (string) ($row->keterangan ?? ''),
                ];
            }

            $this->blocks[] = [
                'kegiatan' => $kegiatanRow,
                'tempat' => $tempatRow,
                'header' => $headerRow,
                'last' => count($data),
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[\\\\\/:*?"<>|]/u', '-', $this->title) ?: 'Nomor Sertifikat', 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->setColumnWidths($sheet);

                foreach ($this->blocks as $block) {
                    $this->styleHeadingRows($sheet, $block);
                    $this->styleTableHeader($sheet, $block);
                    $this->styleDataRows($sheet, $block);
                }

                $sheet->setSelectedCell('A1');
            },
        ];
    }

    private function setColumnWidths(Worksheet $sheet): void
    {
        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /**
     * @param  array{kegiatan: int, tempat: int, header: int, last: int}  $block
     */
    private function styleHeadingRows(Worksheet $sheet, array $block): void
    {
        foreach (['kegiatan', 'tempat'] as $key) {
            $row = $block[$key];

            // Label di-merge A:B (cukup lebar untuk "Tempat/Tanggal"), nilai
            // di-merge C:F supaya teks panjang tidak melebarkan kolom B.
            $sheet->mergeCells('A'.$row.':B'.$row);
            $sheet->mergeCells('C'.$row.':'.self::LAST_COLUMN.$row);

            $sheet->getStyle('A'.$row.':'.self::LAST_COLUMN.$row)
                ->getFont()
                ->setBold(true);

            $sheet->getStyle('A'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);

            $sheet->getStyle('C'.$row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);

            $value = (string) $sheet->getCell('C'.$row)->getValue();
            $label = (string) $sheet->getCell('A'.$row)->getValue();
            $lines = max(
                1,
                (int) ceil(mb_strlen($value) / self::MERGED_LINE_LENGTH),
                (int) ceil(mb_strlen($label) / 20),
            );

            $sheet->getRowDimension($row)->setRowHeight(15 * $lines);
        }
    }

    /**
     * @param  array{kegiatan: int, tempat: int, header: int, last: int}  $block
     */
    private function styleTableHeader(Worksheet $sheet, array $block): void
    {
        $row = $block['header'];
        $range = 'A'.$row.':'.self::LAST_COLUMN.$row;

        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('F3F4F6');

        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * @param  array{kegiatan: int, tempat: int, header: int, last: int}  $block
     */
    private function styleDataRows(Worksheet $sheet, array $block): void
    {
        $first = $block['header'] + 1;

        if ($first > $block['last']) {
            return;
        }

        $range = 'A'.$first.':'.self::LAST_COLUMN.$block['last'];

        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle($range)->getAlignment()->setWrapText(true);

        $sheet->getStyle('E'.$first.':E'.$block['last'])->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle('A'.$first.':A'.$block['last'])->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('D'.$first.':D'.$block['last'])->getNumberFormat()
            ->setFormatCode('@');

        $sheet->getStyle('D'.$first.':D'.$block['last'])->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function plainText(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
