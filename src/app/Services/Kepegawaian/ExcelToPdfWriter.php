<?php

namespace App\Services\Kepegawaian;

use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as BaseDompdfWriter;

/**
 * PhpSpreadsheet's own Dompdf writer never implements Excel's "fit to N
 * page(s) wide" print scaling — Writer\Pdf\Dompdf::save() only ever picks a
 * paper from a fixed named-size list, keyed off the sheet's raw
 * PageSetup::getPaperSize() value (falling back to plain Letter, 8.5in,
 * when that value isn't one of PhpSpreadsheet's known constants). A sheet
 * built with `fitToWidth = 1` in Excel — a very wide table meant to shrink
 * onto one printed page — just overflows that fixed paper width in our
 * export instead, and Dompdf clips content mid-row rather than shrinking it.
 *
 * PhpSpreadsheet's Html output has no "shrink the table" knob, so instead
 * we widen the *paper* to match the sheet's actual rendered width — nothing
 * left to clip, and tall content still paginates vertically as normal.
 */
class ExcelToPdfWriter extends BaseDompdfWriter
{
    private ?float $minWidthPt = null;

    public function withMinWidth(float $widthPt): static
    {
        $this->minWidthPt = $widthPt;

        return $this;
    }

    /**
     * @param  resource|string  $filename
     */
    public function save($filename, int $flags = 0): void
    {
        if ($this->minWidthPt === null) {
            parent::save($filename, $flags);

            return;
        }

        $fileHandle = $this->prepareForSave($filename);

        $setup = $this->spreadsheet->getSheet($this->getSheetIndex() ?? 0)->getPageSetup();
        $orientation = $this->getOrientation() ?? $setup->getOrientation();
        $isLandscape = $orientation === PageSetup::ORIENTATION_LANDSCAPE;

        // Normal page height regardless of width (US Letter); width is the
        // one dimension the source sheet can actually overflow.
        $heightPt = 792.0;
        $widthPt = $this->minWidthPt;

        $pdf = $this->createExternalWriterInstance();
        // Custom [x1, y1, x2, y2] box — Dompdf swaps width/height itself
        // when orientation is landscape, so always pass width as x2 here.
        $pdf->setPaper([0.0, 0.0, $widthPt, $heightPt], $isLandscape ? 'landscape' : 'portrait');

        $pdf->loadHtml($this->generateHtmlAll());
        $pdf->render();
        $this->callPageScript($pdf);

        fwrite($fileHandle, $pdf->output());

        $this->restoreStateAfterSave();
    }
}
