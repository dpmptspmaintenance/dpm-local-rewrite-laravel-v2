<?php

namespace App\Exports\Kepegawaian;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Download Matriks Kebutuhan Diklat, 2 sheet: matriks jabatan×jenis (ringkas)
 * dan daftar mentah semua kompetensi tahun itu (rincian di baliknya) — sama
 * seperti pola KompetensiExport (Detail + Rekap).
 */
class MatriksKebutuhanDiklatExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly int $tahun) {}

    public function sheets(): array
    {
        return [
            new MatriksKebutuhanSheet($this->tahun),
            new KompetensiPerTahunSheet($this->tahun),
        ];
    }
}
