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
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Recap sheet: total jam pelajaran (JP) per employee, for whatever the same
 * filters (search/tahun/jenis) that the detail sheet is showing. Small
 * result set (bounded by employee count) — FromCollection rather than
 * FromQuery, since an aggregated/grouped query can't be chunked by id.
 *
 * Implements the Export marker interface (not just used as a sheet inside
 * KompetensiExport) so /kepegawaian/kompetensi-rekap can Excel::download()
 * it standalone, in both xlsx and PDF.
 */
class KompetensiRekapSheet implements Export, FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    /** Nomor urut baris — collection() mereset ini tiap dipanggil. */
    private int $nomor = 0;

    /**
     * Baris (nomor urut) yang selisih JP-nya minus — dipakai AfterSheet untuk
     * mewarnai sel Selisih JP merah. Diisi ulang tiap map() dipanggil.
     *
     * @var int[]
     */
    private array $barisMinus = [];

    /**
     * Number of mapped columns (headings()), used to size the title row and border range.
     * No/NIP/Nama/Jumlah/Total/Target/Selisih — hurufnya dihitung di lastColumn(), bukan
     * konstanta, supaya tak meleset lagi kalau kolom bertambah (pelajaran dari
     * EvaluasiKesesuaianSheet).
     */
    public function __construct(private readonly array $filters = []) {}

    public function collection(): Collection
    {
        $this->nomor = 0;
        $this->barisMinus = [];

        return PegawaiKompetensi::query()
            ->filtered($this->filters)
            ->join('pegawai_profil', 'pegawai_profil.nip', '=', 'pegawai_kompetensi.nip')
            ->selectRaw('pegawai_kompetensi.nip as nip')
            ->selectRaw('pegawai_profil.nama as nama')
            ->selectRaw('pegawai_profil.status_pegawai as status_pegawai')
            ->selectRaw('COUNT(*) as jumlah_kompetensi')
            ->selectRaw('SUM(pegawai_kompetensi.jumlah_jam) as total_jam')
            ->groupBy('pegawai_kompetensi.nip', 'pegawai_profil.nama', 'pegawai_profil.status_pegawai')
            ->orderByDesc('total_jam')
            ->get();
    }

    public function title(): string
    {
        return 'Rekap per Pegawai';
    }

    public function headings(): array
    {
        return ['No', 'NIP', 'Nama Pegawai', 'Jumlah Kompetensi', 'Total Jam (JP)', 'Target JP', 'Selisih JP'];
    }

    public function map($row): array
    {
        // Nomor urut mengikuti urutan collection() (total_jam menurun).
        // map() dipanggil berurutan per baris oleh maatwebsite/excel.
        $nomor = ++$this->nomor;

        $target = PegawaiKompetensi::JATAH_JP[$row->status_pegawai] ?? PegawaiKompetensi::JATAH_JP_DEFAULT;
        $totalJam = (int) $row->total_jam;
        $selisih = $totalJam - $target;

        if ($selisih < 0) {
            $this->barisMinus[] = $nomor;
        }

        return [
            $nomor,
            $row->nip,
            $row->nama,
            $row->jumlah_kompetensi,
            $totalJam,
            $target,
            $selisih,
        ];
    }

    /** "Rekap Kompetensi Tahun 2025", atau "Semua Tahun" bila filter tahun kosong. */
    private function judul(): string
    {
        $tahun = $this->filters['tahun'] ?? null;

        return 'Rekap JP '.(blank($tahun) ? 'Semua Tahun' : "Tahun {$tahun}");
    }

    /**
     * Huruf kolom terakhir, dihitung dari jumlah heading — bukan konstanta
     * hardcode, supaya tak meleset kalau kolom berubah (pelajaran dari
     * EvaluasiKesesuaianSheet: konstanta lama pernah salah satu huruf).
     */
    private function lastColumn(): string
    {
        return Coordinate::stringFromColumnIndex(count($this->headings()));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastColumn();

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                // Margin kertas — default PhpSpreadsheet (0.7in) dirender dompdf
                // hampir tanpa jarak kiri karena setFitToWidth(1) melebarkan tabel
                // pas-pasan sampai tepi margin. Naikkan margin biar tak mepet.
                $sheet->getPageMargins()
                    ->setLeft(0.5)
                    ->setRight(0.5)
                    ->setTop(0.6)
                    ->setBottom(0.6);

                // Font default (isi tabel) diperkecil — writer PDF Dompdf
                // TIDAK menerapkan setFitToWidth() sama sekali (dibaca dari
                // vendor source), jadi ukuran font besar bawaan (11pt) yang
                // sebelumnya bikin tabel melebar sampai nempel tepi kertas.
                // Judul (A1) tetap 14pt, diset lagi di bawah setelah baris ini.
                $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

                // Title + nama OPD rows above the headings, pushing everything
                // else down two rows (bukan satu seperti sebelumnya).
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:'.$last.'1');
                $sheet->setCellValue('A1', $this->judul());
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:'.$last.'2');
                $sheet->setCellValue('A2', 'OPD: DPMPTSP Kota Semarang');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A3:'.$last.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A3:'.$last.'3')->getFont()->setBold(true);
                // Kolom No rata tengah, NIP rata tengah.
                $sheet->getStyle('A4:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B4:B'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Kolom Target JP (F) & Selisih JP (G) rata tengah; baris
                // dengan selisih minus (barisMinus, nomor urut → baris sheet
                // = nomor+3 karena row 1=judul, row 2=OPD, row 3=header)
                // diwarnai merah.
                $kolTarget = Coordinate::stringFromColumnIndex(6);
                $kolSelisih = Coordinate::stringFromColumnIndex(7);
                $sheet->getStyle($kolTarget.'4:'.$kolSelisih.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                foreach ($this->barisMinus as $nomor) {
                    $baris = $nomor + 3;
                    $sheet->getStyle($kolSelisih.$baris)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED))->setBold(true);
                }
            },
        ];
    }
}
