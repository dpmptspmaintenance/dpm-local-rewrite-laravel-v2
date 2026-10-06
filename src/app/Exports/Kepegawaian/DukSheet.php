<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\Duk;
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
 * Excel DUK — satu sheet berisi seluruh baris snapshot DUK terbaru.
 * Kop judul OPD/periode diambil dari baris pertama (semua baris snapshot
 * punya nilai metadata yang sama).
 */
class DukSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private int $nomor = 0;

    private ?string $opd = null;

    private ?string $periode = null;

    public function __construct(private ?int $batchId = null)
    {
    }

    public function collection(): Collection
    {
        $this->nomor = 0;

        $rows = Duk::query()
            ->where('duk_impor_id', $this->batchId)
            ->orderBy('urutan_duk')
            ->get();

        $first = $rows->first();
        $this->opd = $first?->opd;
        $this->periode = $first?->periode;

        return $rows;
    }

    public function title(): string
    {
        return 'DUK';
    }

    public function headings(): array
    {
        return [
            'NO', 'NAMA', 'NIP', 'GOL', 'TMT', 'GOL CPNS', 'TMT CPNS',
            'JABATAN', 'ESELON', 'MASA KERJA', 'PENDIDIKAN',
        ];
    }

    public function map($row): array
    {
        /** @var Duk $row */
        return [
            ++$this->nomor,
            $row->nama ?: '',
            $row->nip ?: '',
            $row->gol ?: '',
            $row->tmt ?: '',
            $row->gol_cpns ?: '',
            $row->tmt_cpns ?: '',
            $row->jabatan ?: '',
            $row->eselon ?: '',
            $row->masaKerjaTeks(),
            $row->pendidikan ?: '',
        ];
    }

    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex(count($this->headings()));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->lastColumn();

                $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

                $sheet->getPageSetup()
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setPaperSize(PageSetup::PAPERSIZE_FOLIO);

                $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);

                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:'.$lastColumn.'1');
                $sheet->setCellValue('A1', 'DAFTAR URUTAN KEPANGKATAN');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:'.$lastColumn.'2');
                $sheet->setCellValue('A2', 'NAMA OPD : '.($this->opd ?: '-'));
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A3:'.$lastColumn.'3');
                $sheet->setCellValue('A3', 'PERIODE : '.($this->periode ?: '-'));
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A4:'.$lastColumn.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A4:'.$lastColumn.'4')->getFont()->setBold(true);

                $sheet->getStyle('A4:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J4:J'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
