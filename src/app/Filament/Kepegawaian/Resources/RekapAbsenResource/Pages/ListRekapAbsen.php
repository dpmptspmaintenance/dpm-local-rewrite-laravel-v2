<?php

namespace App\Filament\Kepegawaian\Resources\RekapAbsenResource\Pages;

use App\Exports\Kepegawaian\RekapAbsenExport;
use App\Filament\Kepegawaian\Resources\RekapAbsenResource;
use App\Models\Kepegawaian\RekapAbsen;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListRekapAbsen extends ListRecords
{
    protected static string $resource = RekapAbsenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhRekapAbsen')
                ->label('Unduh Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): BinaryFileResponse => $this->unduhRekapAbsen()),
        ];
    }

    /**
     * Unduh 3 sheet: rekap, data dukung, data dukung e-TPP — mengikuti
     * pencarian/filter/urutan tabel yang sedang tampil (tanpa paginasi).
     */
    protected function unduhRekapAbsen(): BinaryFileResponse
    {
        return Excel::download(
            new RekapAbsenExport(fn (): Builder => $this->getTableQueryForExport()),
            $this->namaBerkas('rekap-absen'),
        );
    }

    /**
     * Nama berkas mengikuti periode filter aktif:
     * {awalan}-{bulan}-{tahun}.xlsx (bagian yang kosong dilewati).
     */
    protected function namaBerkas(string $awalan): string
    {
        $tahun = $this->getTableFilterState('tahun')['value'] ?? null;
        $bulan = RekapAbsen::bulanAngka($this->getTableFilterState('bulan')['value'] ?? null);

        $bagian = array_filter([
            $awalan,
            $bulan !== null ? Str::slug(RekapAbsen::BULAN[$bulan]) : null,
            filled($tahun) ? (string) $tahun : null,
        ]);

        return implode('-', $bagian).'.xlsx';
    }
}
