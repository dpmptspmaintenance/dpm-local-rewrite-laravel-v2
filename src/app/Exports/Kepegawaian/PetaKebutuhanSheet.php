<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\PegawaiKompetensi;
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
 * Export "Peta Kebutuhan Kompetensi" — sebaran per jabatan untuk satu tahun,
 * sumbernya sama persis dengan halaman (PegawaiKompetensi::rekapJabatan()),
 * jadi angka di file tak akan beda dari tabel di layar.
 *
 * WithStrictNullComparison Wajib: nilai 0 di kolom jumlah_event/rata_jam
 * akan hilang jadi sel kosong (0 == null) tanpa interface ini — pelajaran
 * dari CutiTahunanSheet.
 */
class PetaKebutuhanSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** Jumlah kolom yang di-map: No + 5 kolom data. */
    private const LAST_COLUMN = 'F';

    /** Penomoran baris — map() dipanggil berurutan per baris collection(). */
    private int $nomor = 0;

    public function __construct(private readonly int $tahun) {}

    public function collection(): Collection
    {
        // Reset penomoran di sini — collection() selalu dipanggil sebelum
        // map() pada alur maatwebsite/excel, jadi nomor tak akan drift
        // meski objek sheet yang sama dipakai berulang.
        $this->nomor = 0;

        return PegawaiKompetensi::query()
            ->rekapJabatan($this->tahun)
            ->get();
    }

    public function title(): string
    {
        return 'Peta Kebutuhan Kompetensi';
    }

    public function headings(): array
    {
        return [
            'No',
            'Jabatan',
            'Pegawai Berkompetensi',
            'Event',
            'Total JP',
            'Rata JP/Event',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->nomor,
            $row->jabatan,
            (int) $row->jumlah_pegawai_berkompetensi,
            (int) $row->jumlah_event,
            (int) $row->total_jam,
            (float) $row->rata_jam,
        ];
    }

    private function judul(): string
    {
        return 'Peta Kebutuhan Kompetensi Seluruh OPD Tahun '.$this->tahun;
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

                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:'.self::LAST_COLUMN.'1');
                $sheet->setCellValue('A1', $this->judul());
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A2:'.self::LAST_COLUMN.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A2:'.self::LAST_COLUMN.'2')->getFont()->setBold(true);
            },
        ];
    }
}
