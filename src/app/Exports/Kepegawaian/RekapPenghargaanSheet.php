<?php

namespace App\Exports\Kepegawaian;

use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\PegawaiProfil;
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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Export "Rekap Penghargaan" — checklist SLKS 10/20/30 tahun per PNS aktif,
 * sumbernya sama persis dengan halaman (PegawaiProfil::rekapPenghargaan()),
 * jadi tanda centang di file tak akan beda dari tabel di layar.
 */
class RekapPenghargaanSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    /** Baris yang perlu highlight kuning (perlu_diusulkan tak null) — direset tiap collection(). */
    private array $barisPerluDiusulkan = [];

    /** Baris yang BELUM bisa diajukan (perlu_diusulkan tak null tapi status sukses). */
    private array $barisTidakBisaDiusulkan = [];

    private int $nomor = 0;

    public function collection(): Collection
    {
        $this->nomor = 0;
        $this->barisPerluDiusulkan = [];
        $this->barisTidakBisaDiusulkan = [];

        return PegawaiProfil::rekapPenghargaan();
    }

    public function title(): string
    {
        return 'Rekap Penghargaan';
    }

    public function headings(): array
    {
        return [
            'No', 'NIP', 'Nama Pegawai', 'Jabatan', 'Golongan', 'Masa Kerja (Tahun)',
            'Masa Kerja DUK', '10 Tahun', '20 Tahun', '30 Tahun', 'Perlu Diusulkan',
            'Pengusulan Terakhir', 'Bisa Diusulkan',
        ];
    }

    public function map($row): array
    {
        $nomor = ++$this->nomor;

        if ($row['perlu_diusulkan'] !== null) {
            $this->barisPerluDiusulkan[] = $nomor;

            if (! $row['bisa_diusulkan']) {
                $this->barisTidakBisaDiusulkan[] = $nomor;
            }
        }

        $status = $row['drh_terakhir_status'];

        return [
            $nomor,
            $row['nip'],
            $row['nama'],
            $row['jabatan'] ?: '—',
            $row['golongan'] ?: '—',
            $row['masa_kerja'],
            $row['masa_kerja_duk'] ?: '—',
            $row['has_10'] ? 'V' : '',
            $row['has_20'] ? 'V' : '',
            $row['has_30'] ? 'V' : '',
            $row['perlu_diusulkan'] !== null ? $row['perlu_diusulkan'].' Tahun' : '—',
            $status === null
                ? ''
                : ($row['drh_terakhir_tahun'] ?? '?').' — '.(DrhSatyaLancana::STATUSES[$status] ?? $status),
            $row['alasan_bisa_diusulkan'],
        ];
    }

    private function judul(): string
    {
        return 'Rekap Penghargaan Masa Kerja (Satyalancana Karya Satya) — '.now()->format('d M Y');
    }

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

                $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()->setLeft(0.5)->setRight(0.5)->setTop(0.6)->setBottom(0.6);

                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:'.$lastColumn.'1');
                $sheet->setCellValue('A1', $this->judul());
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:'.$lastColumn.'2');
                $sheet->setCellValue('A2', 'Hanya PNS aktif dengan masa kerja ≥ 10 tahun. Sesuai syarat pengusulan: SLKS diusulkan urut dari tingkat terendah yang belum pernah dimiliki — tidak boleh lompat. Kolom "Perlu Diusulkan" = tingkat terendah yang wajib diajukan berikutnya (baris kuning). Bila tingkat lebih tinggi tercatat tapi tingkat di bawahnya kosong, tingkat bawah itu dianggap sudah terpenuhi (anggap belum diisi di SIMPATIK).');
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A3:'.$lastColumn.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A3:'.$lastColumn.'3')->getFont()->setBold(true);

                foreach (['G', 'H', 'I', 'J', 'K', 'L', 'M'] as $col) {
                    $sheet->getStyle($col.'4:'.$col.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->barisPerluDiusulkan as $nomor) {
                    $baris = $nomor + 3;
                    $sheet->getStyle('A'.$baris.':'.$lastColumn.$baris)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
                }

                // Baris yang perlu diusulkan TAPI belum bisa (DRH terakhir
                // sukses) — sel "Bisa Diusulkan" (M) disorot merah tegas.
                foreach ($this->barisTidakBisaDiusulkan as $nomor) {
                    $baris = $nomor + 3;
                    $sheet->getStyle('M'.$baris)->getFont()->setBold(true)->getColor()->setRGB('C00000');
                }
            },
        ];
    }
}
