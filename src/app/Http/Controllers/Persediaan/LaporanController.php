<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ComputesLaporan;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanController extends Controller
{
    use ComputesLaporan;

    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        $bulan = (int) $request->input('bulan', date('n'));
        $tahun = (int) $request->input('tahun', date('Y'));

        return view('persediaan.laporan.index', [
            'bulanOptions' => self::BULAN_NAMA,
            'defaultBulan' => $bulan,
            'defaultTahun' => $tahun,
            'tahunOptions' => range(date('Y'), date('Y') - 3),
        ]);
    }

    /** AJAX endpoint → returns raw <tr> fragment injected into #dataLaporan. */
    public function data(Request $request)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $blocks = $this->laporanBlocks($bulan, $tahun);

        return view('persediaan.laporan.partials.data-rows', [
            'blocks' => $blocks,
        ])->render();
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];
        $blocks = $this->laporanBlocks($bulan, $tahun);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Persediaan');

        // 16 columns (A..P)
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'LAPORAN PERSEDIAAN');
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', 'Periode: '.strtoupper(self::BULAN_NAMA[$bulan]).' '.$tahun);
        // row 3 blank

        // Header group row 4 + sub-header row 5
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Nama Barang');
        $sheet->setCellValue('C4', 'Saldo Awal');
        $sheet->setCellValue('G4', 'Mutasi Masuk');
        $sheet->setCellValue('K4', 'Mutasi Keluar');
        $sheet->setCellValue('O4', 'Saldo Akhir');
        $sheet->mergeCells('C4:F4');
        $sheet->mergeCells('G4:J4');
        $sheet->mergeCells('K4:N4');
        $sheet->mergeCells('O4:P4');
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');

        $cols = [
            'C5' => 'Stok', 'D5' => 'Sat', 'E5' => 'Harga', 'F5' => 'Total (Rp)',
            'G5' => 'Stok', 'H5' => 'Sat', 'I5' => 'Harga', 'J5' => 'Total (Rp)',
            'K5' => 'Stok', 'L5' => 'Sat', 'M5' => 'Harga', 'N5' => 'Total (Rp)',
            'O5' => 'Stok', 'P5' => 'Total (Rp)',
        ];
        foreach ($cols as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->getRowDimension(5)->setRowHeight(20);

        $rowIdx = 6; // data start
        $rangkuman = ['awal' => 0, 'masuk' => 0, 'keluar' => 0, 'akhir' => 0];
        $moneyCol = ['awal' => 'F', 'masuk' => 'J', 'keluar' => 'N', 'akhir' => 'P'];

        foreach ($blocks as $b) {
            if ($b['type'] === 'rek') {
                $sheet->mergeCells("A{$rowIdx}:P{$rowIdx}");
                $sheet->setCellValue("A{$rowIdx}", $b['kode'].' - '.$b['nama']);
            } elseif ($b['type'] === 'item') {
                $it = $b['item'];
                $saldo = $b['saldo'];
                $sheet->fromArray([
                    $b['no'],
                    $it->nama_barang,
                    $it->qty_awal ?: '-',
                    $it->nama_satuan,
                    $it->harga_satuan,
                    round($saldo['awal']),
                    $it->qty_masuk ?: '-',
                    $it->nama_satuan,
                    $it->harga_satuan,
                    round($saldo['masuk']),
                    $it->qty_keluar ?: '-',
                    $it->nama_satuan,
                    $it->harga_satuan,
                    round($saldo['keluar']),
                    $it->qty_akhir ?: '-',
                    round($saldo['akhir']),
                ], null, "A{$rowIdx}");
            } else { // sub or total — label spans A:E, money at F/J/N/P
                $label = $b['type'] === 'sub' ? 'JUMLAH '.$b['kode'] : 'TOTAL KESELURUHAN';
                $sheet->setCellValue("A{$rowIdx}", $label);
                $sheet->mergeCells("A{$rowIdx}:E{$rowIdx}");
                $sheet->mergeCells("G{$rowIdx}:I{$rowIdx}");
                $sheet->mergeCells("K{$rowIdx}:M{$rowIdx}");

                if ($b['type'] === 'sub') {
                    foreach ($rangkuman as $k => $v) {
                        $rangkuman[$k] += $b['sub'][$k];
                    }
                    $sheet->setCellValue("F{$rowIdx}", round($b['sub']['awal']));
                    $sheet->setCellValue("J{$rowIdx}", round($b['sub']['masuk']));
                    $sheet->setCellValue("N{$rowIdx}", round($b['sub']['keluar']));
                    $sheet->setCellValue("P{$rowIdx}", round($b['sub']['akhir']));
                } else {
                    foreach ($rangkuman as $k => $v) {
                        $sheet->setCellValue($moneyCol[$k].$rowIdx, round($v));
                    }
                }
            }
            $rowIdx++;
        }

        // ---- styling ----
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A4:P5')->applyFromArray($headerStyle);

        $thin = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $moneyFormat = '"Rp "#,##0';
        $numberFormat = '#,##0';

        $r = 6;
        foreach ($blocks as $b) {
            if ($b['type'] === 'rek') {
                $sheet->getStyle("A{$r}:P{$r}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2F2F2']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN], 'bottom' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
            } elseif ($b['type'] === 'item') {
                $sheet->getStyle("A{$r}:P{$r}")->applyFromArray($thin);
                $sheet->getStyle("E{$r}:F{$r}")->getNumberFormat()->setFormatCode($numberFormat);
                $sheet->getStyle("I{$r}:J{$r}")->getNumberFormat()->setFormatCode($numberFormat);
                $sheet->getStyle("M{$r}:N{$r}")->getNumberFormat()->setFormatCode($numberFormat);
                $sheet->getStyle("P{$r}")->getNumberFormat()->setFormatCode($numberFormat);
                $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            } elseif ($b['type'] === 'sub') {
                $sheet->getStyle("A{$r}:P{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF2CC']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN], 'bottom' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                foreach (['F', 'J', 'N', 'P'] as $c) {
                    $sheet->getStyle("{$c}{$r}")->getNumberFormat()->setFormatCode($moneyFormat);
                }
            } else {
                $sheet->getStyle("A{$r}:P{$r}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_DOUBLE], 'bottom' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                foreach (['F', 'J', 'N', 'P'] as $c) {
                    $sheet->getStyle("{$c}{$r}")->getNumberFormat()->setFormatCode($moneyFormat);
                }
            }
            $r++;
        }

        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        foreach ([5, 30, 8, 8, 12, 15, 8, 8, 12, 15, 8, 8, 12, 15, 8, 15] as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }

        $filename = "Persediaan_".self::BULAN_NAMA[$bulan].'_'.$tahun.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
