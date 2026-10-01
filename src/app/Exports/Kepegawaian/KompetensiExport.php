<?php

namespace App\Exports\Kepegawaian;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KompetensiExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{q?: string, tahun?: int, jenis?: string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    public function sheets(): array
    {
        return [
            new KompetensiDetailSheet($this->filters),
            new KompetensiRekapSheet($this->filters),
        ];
    }
}
