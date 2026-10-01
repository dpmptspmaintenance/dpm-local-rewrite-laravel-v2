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
 * Export "Evaluasi Kesesuaian Diklat" — satu baris per pegawai aktif untuk
 * satu tahun, sumbernya sama persis dengan halaman
 * (PegawaiKompetensi::evaluasiKesesuaian()), jadi angka di file tak akan
 * beda dari tabel di layar. Diurutkan sama seperti bawaan tabel: persen
 * capaian menaik (paling kurang duluan).
 *
 * WithStrictNullComparison Wajib: pegawai dengan realisasi/persen 0 (belum
 * ada kompetensi tercatat tahun itu) hilang jadi sel kosong (0 == null)
 * tanpa interface ini — pelajaran dari CutiTahunanSheet.
 */
class EvaluasiKesesuaianSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** Penomoran baris — collection() mereset ini tiap dipanggil. */
    private int $nomor = 0;

    public function __construct(private readonly int $tahun) {}

    public function collection(): Collection
    {
        $this->nomor = 0;

        return PegawaiKompetensi::evaluasiKesesuaian($this->tahun)
            ->sortBy(fn (array $r) => $r['persen'])
            ->values();
    }

    public function title(): string
    {
        return 'Evaluasi Kesesuaian Diklat';
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama Pegawai', 'Jabatan', 'Status Pegawai',
            'Jatah JP', 'Realisasi JP', 'Capaian (%)', 'Keragaman Jenis',
            // Field terpisah (bukan teks gabungan "N dari M") supaya masing-
            // masing bisa di-sort/dihitung sendiri di Excel.
            'Kompetensi Sesuai Jabatan', 'Total Kompetensi', 'Sesuai Jabatan (%)',
            'Kesesuaian',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->nomor,
            $row['nip'],
            $row['nama'],
            $row['jabatan'],
            $this->labelStatus($row['status_pegawai']),
            (int) $row['jatah_jp'],
            (int) $row['realisasi_jp'],
            (int) $row['persen'],
            (int) $row['jumlah_jenis'],
            (int) $row['jumlah_sesuai_jabatan'],
            (int) $row['jumlah_event'],
            (int) $row['persen_sesuai_jabatan'],
            $row['terpenuhi'] ? 'Terpenuhi' : 'Kurang',
        ];
    }

    private function labelStatus(string $status): string
    {
        return match ($status) {
            'PEGAWAI NEGERI SIPIL' => 'PNS',
            'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA' => 'PPPK',
            'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA PARUH WAKTU' => 'PPPK Paruh Waktu',
            default => $status,
        };
    }

    private function judul(): string
    {
        return 'Evaluasi Berkala Kesesuaian Jumlah & Jenis Diklat Tahun '.$this->tahun;
    }

    /**
     * Huruf kolom terakhir, dihitung dari jumlah heading — BUKAN konstanta
     * hardcode. Konstanta lama (LAST_COLUMN='J') pernah salah satu huruf
     * (harusnya K untuk 11 kolom saat itu) karena tak ikut disesuaikan waktu
     * kolom ditambah; dengan dihitung dari headings() langsung, kelas bug
     * ini tak bisa terulang lagi kalau kolom berubah lagi nanti.
     */
    private function lastColumn(): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headings()));
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

                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:'.$lastColumn.'1');
                $sheet->setCellValue('A1', $this->judul());
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->insertNewRowBefore(2, 1);
                $sheet->mergeCells('A2:'.$lastColumn.'2');
                $sheet->setCellValue('A2', 'PNS minimal 20 JP/tahun (PP 11/2017 jo. PP 17/2020); PPPK & PPPK paruh waktu hak s.d. 24 JP/tahun (Perka LAN No. 15/2020). Keragaman jenis = jumlah jenis diklat berbeda, bukan kesesuaian ke jabatan. Kompetensi Sesuai Jabatan/Total Kompetensi/Sesuai Jabatan (%) = ditandai manual "Sesuai" di daftar Kompetensi (default Sesuai sampai ditinjau).');
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A3:'.$lastColumn.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A3:'.$lastColumn.'3')->getFont()->setBold(true);
            },
        ];
    }
}
