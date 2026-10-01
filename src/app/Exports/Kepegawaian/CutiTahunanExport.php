<?php

namespace App\Exports\Kepegawaian;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Download Rekap Cuti Tahunan, 1 + N sheet: sheet 1 rekap per pegawai
 * (ringkas), lalu satu sheet rincian mentah per tahun yang ditampilkan —
 * "Cuti 2024", "Cuti 2025", dst. (semua jenis cuti, lihat CutiPerTahunSheet).
 * Sama pola dengan EvaluasiKesesuaianExport.
 *
 * Dipakai untuk export EXCEL saja. PDF tetap single-sheet (CutiTahunanSheet)
 * — DOMPDF crash render ratusan baris mentah (memory_limit habis), alasan
 * sama seperti Matriks/Evaluasi.
 */
class CutiTahunanExport implements Export, WithMultipleSheets
{
    /**
     * @param  int[]  $tahun
     */
    public function __construct(private readonly array $tahun) {}

    public function sheets(): array
    {
        $sheets = [new CutiTahunanSheet($this->tahun)];

        foreach ($this->tahun as $y) {
            $sheets[] = new CutiPerTahunSheet($y);
        }

        return $sheets;
    }
}
