<?php

namespace App\Exports\Kepegawaian;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Download Evaluasi Kesesuaian Diklat, 2 sheet: evaluasi per pegawai (ringkas)
 * dan daftar mentah semua kompetensi tahun itu (rincian di baliknya) — sama
 * pola dengan MatriksKebutuhanDiklatExport (reuse KompetensiPerTahunSheet
 * yang sama, supaya "tahun" berarti persis sama di semua halaman).
 */
class EvaluasiKesesuaianExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly int $tahun) {}

    public function sheets(): array
    {
        return [
            new EvaluasiKesesuaianSheet($this->tahun),
            new KompetensiPerTahunSheet($this->tahun),
        ];
    }
}
