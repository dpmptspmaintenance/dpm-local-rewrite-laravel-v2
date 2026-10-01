<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiKompetensiResource\Pages;

use App\Exports\Kepegawaian\FormRealisasiKompetensiSheet;
use App\Exports\Kepegawaian\KompetensiExport;
use App\Filament\Kepegawaian\Resources\PegawaiKompetensiResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListPegawaiKompetensi extends ListRecords
{
    protected static string $resource = PegawaiKompetensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): BinaryFileResponse => $this->exportFiltered()),
            Action::make('formRealisasi')
                ->label('Form Realisasi (OPD)')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportFormRealisasi()),
        ];
    }

    /**
     * Export Form Realisasi Pengembangan Kompetensi (layout OPD) untuk tahun
     * yang sedang dipilih di filter — satu acara per baris, peserta dipecah
     * PNS/PPPK, kolom ANGGARAN dibiarkan kosong (diisi manual OPD).
     */
    protected function exportFormRealisasi(): BinaryFileResponse
    {
        $tahun = (int) ($this->tableFilters['tahun']['value'] ?? now()->year);

        return Excel::download(
            new FormRealisasiKompetensiSheet($tahun),
            "form-realisasi-kompetensi-{$tahun}-".now()->format('Ymd-His').'.xlsx',
        );
    }

    /**
     * Export exactly what the table currently shows: translate the live
     * table state back into the filter array KompetensiExport expects, which
     * feeds the same PegawaiKompetensi::scopeFiltered() the table uses.
     */
    protected function exportFiltered(): BinaryFileResponse
    {
        $filters = [
            'q' => $this->getTableSearch(),
            'tahun' => $this->tableFilters['tahun']['value'] ?? null,
            'jenis' => $this->tableFilters['jenis']['value'] ?? null,
        ];

        $tahunLabel = blank($filters['tahun']) ? 'semua-tahun' : $filters['tahun'];
        $filename = "kompetensi-pegawai-{$tahunLabel}-".now()->format('Ymd-His').'.xlsx';

        return Excel::download(new KompetensiExport($filters), $filename);
    }
}
