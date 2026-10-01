<?php

namespace App\Services\Kepegawaian;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Renders selected sheet(s) of an arbitrary uploaded workbook to PDF via
 * PhpSpreadsheet's Dompdf writer. Generic tool, not tied to any kepegawaian
 * table — lives here only because it's used from the Kepegawaian panel.
 */
class ExcelToPdfService
{
    /** CSS pixels -> PDF points, at the 96 DPI Dompdf/PhpSpreadsheet assume. */
    private const PX_TO_PT = 0.75;

    /** Buffer added on top of the computed content width, in points. */
    private const WIDTH_MARGIN_PT = 36.0;

    /** PhpSpreadsheet's own Html writer default for a column with no set width. */
    private const DEFAULT_COLUMN_WIDTH_PX = 64;

    /**
     * @return list<string> Sheet names, in workbook order.
     */
    public function sheetNames(string $path): array
    {
        return IOFactory::load($path)->getSheetNames();
    }

    /**
     * Render only the given sheets — kept in their original workbook order,
     * not selection order — to a single PDF and return its binary contents.
     *
     * @param  list<string>  $sheetNames
     */
    public function toPdf(string $path, array $sheetNames): string
    {
        // Dompdf renders through an HTML layout + border-resolution pass
        // before rasterizing, and a wide sheet's custom paper (see
        // ExcelToPdfWriter) makes that pass more expensive — generous
        // headroom here, same caution already noted for other PDF exports
        // in this module (e.g. EvaluasiKesesuaianDiklat).
        ini_set('memory_limit', '1024M');

        $spreadsheet = $this->loadWithOnly($path, $sheetNames);

        $writer = (new ExcelToPdfWriter($spreadsheet))
            ->withMinWidth($this->widestSheetWidthPt($spreadsheet));

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx2pdf');

        if ($tmp === false) {
            throw new \RuntimeException('Gagal membuat berkas sementara untuk PDF.');
        }

        try {
            $writer->save($tmp);

            return file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Widest sheet in the (already sheet-filtered) workbook, in points. Only
     * the widest matters — PhpSpreadsheet's Dompdf writer sizes the whole
     * PDF's paper from a single sheet's page setup regardless of how many
     * sheets it renders, so every sheet needs to fit the one paper we pick.
     *
     * Deliberately sums *every* stored column dimension up to
     * getHighestColumn(), not just the columns inside the sheet's own print
     * area (when it has one) or the ones with visible borders/fill. Tried
     * trimming to the print area first — it back fired: PhpSpreadsheet's
     * Html output isn't `table-layout: fixed`, so the widths we compute are
     * only a floor, not a cap, and the browser/Dompdf table layout algorithm
     * is free to redistribute any *extra* page width into whichever cells
     * need more room for their content. Trimming away the "unused" trailing
     * columns removed exactly that slack and produced clipped text again on
     * a real Uraian Jabatan file, even though those columns render as blank
     * space. A wide blank margin is a cosmetic issue; clipped content is a
     * data-loss one — kept the safer, over-wide default.
     */
    private function widestSheetWidthPt(Spreadsheet $spreadsheet): float
    {
        $defaultFont = $spreadsheet->getDefaultStyle()->getFont();
        $widest = 0.0;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $widest = max($widest, $this->sheetWidthPt($sheet, $defaultFont));
        }

        return $widest;
    }

    private function sheetWidthPt(Worksheet $sheet, Font $defaultFont): float
    {
        $sheet->calculateColumnWidths();

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $widthPx = 0;

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $dimension = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col));

            if (! $dimension->getVisible()) {
                continue;
            }

            $width = $dimension->getWidth();
            $widthPx += $width < 0
                ? self::DEFAULT_COLUMN_WIDTH_PX
                : Drawing::cellDimensionToPixels($width, $defaultFont);
        }

        return ($widthPx * self::PX_TO_PT) + self::WIDTH_MARGIN_PT;
    }

    private function loadWithOnly(string $path, array $sheetNames): Spreadsheet
    {
        $spreadsheet = IOFactory::load($path);

        // Bake formulas in the sheets we're keeping into their calculated
        // values *before* dropping any sheets below. Sheets kept for display
        // often have formulas pointing at a "master data" sheet elsewhere in
        // the workbook (e.g. a lookup) — removing that sheet first turns
        // those references into #REF! once nothing recalculates them.
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);

            if ($sheet instanceof Worksheet) {
                $this->bakeFormulas($sheet);
            }
        }

        // Iterate high -> low so removing a sheet never shifts the index of
        // one still to be checked.
        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if (! in_array($spreadsheet->getSheet($i)->getTitle(), $sheetNames, true)) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }

        if ($spreadsheet->getSheetCount() === 0) {
            throw new \RuntimeException('Tidak ada sheet yang cocok untuk dikonversi.');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Replace every formula cell's formula with its calculated value, in
     * place. Must run while the whole workbook (all sheets) is still intact
     * so cross-sheet references resolve correctly.
     */
    private function bakeFormulas(Worksheet $sheet): void
    {
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);

            foreach ($cellIterator as $cell) {
                if (! $cell->isFormula()) {
                    continue;
                }

                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Throwable) {
                    // Formula PhpSpreadsheet can't evaluate (e.g. already
                    // broken in the source file) — leave it as a formula
                    // rather than fail the whole conversion.
                    continue;
                }

                if (is_array($value)) {
                    // Spilled/array formula result — no single scalar to bake in.
                    continue;
                }

                $cell->setValue($value);
            }
        }
    }
}
