<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\KompetensiRekapSheet;
use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\PegawaiKompetensi;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Total JP (jam pelajaran) per employee, e.g. "Riyanto — 2025 — 40 JP".
 * Grouped read of pegawai_kompetensi, filtered through the same
 * PegawaiKompetensi::scopeFiltered() the detail list and KompetensiRekapSheet
 * export use, so the total here can never disagree with the Excel recap.
 */
class KompetensiRekap extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Rekap Kompetensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Kompetensi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Rekap Kompetensi per Pegawai';

    protected string $view = 'filament.kepegawaian.pages.kompetensi-rekap';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportRekap(ExcelFormat::XLSX, 'xlsx')),
            Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportRekap(ExcelFormat::DOMPDF, 'pdf')),
        ];
    }

    /**
     * Export exactly what the table currently shows: same tahun filter +
     * search, fed into the same KompetensiRekapSheet the "Ekspor Excel"
     * button on /kepegawaian/kompetensi produces — so the numbers can't
     * drift between the two pages regardless of file format.
     */
    protected function exportRekap(string $writerType, string $extension): BinaryFileResponse
    {
        $filters = [
            'q' => $this->getTableSearch(),
            'tahun' => $this->tableFilters['tahun']['value'] ?? null,
        ];

        $tahunLabel = blank($filters['tahun']) ? 'Semua Tahun' : 'Tahun '.$filters['tahun'];
        $filename = "Rekap JP {$tahunLabel}.{$extension}";

        return Excel::download(new KompetensiRekapSheet($filters), $filename, $writerType);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PegawaiKompetensi::query()
                    ->join('pegawai_profil', 'pegawai_profil.nip', '=', 'pegawai_kompetensi.nip')
                    ->selectRaw('MIN(pegawai_kompetensi.id) as id')
                    ->selectRaw('pegawai_kompetensi.nip as nip')
                    ->selectRaw('pegawai_profil.nama as nama')
                    ->selectRaw('COUNT(*) as jumlah_kompetensi')
                    ->selectRaw('SUM(pegawai_kompetensi.jumlah_jam) as total_jam')
                    ->groupBy('pegawai_kompetensi.nip', 'pegawai_profil.nama')
            )
            ->columns([
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn ($record): ?string => $record->nip)
                    ->placeholder('—')
                    ->wrap()
                    ->weight('medium')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where(function (Builder $q) use ($search) {
                            $q->where('pegawai_kompetensi.nip', 'like', "%{$search}%")
                                ->orWhere('pegawai_profil.nama', 'like', "%{$search}%");
                        })),
                TextColumn::make('jumlah_kompetensi')
                    ->label('Jumlah Kompetensi')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_jam')
                    ->label('Total JP')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state).' JP')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('total_jam', 'desc')
            // Grouped/aggregated query — pegawai_kompetensi.id isn't in the
            // GROUP BY, so Filament's automatic id-tiebreak sort (for stable
            // pagination) would violate ONLY_FULL_GROUP_BY. nip is already
            // unique per row, so pagination stays stable without it.
            ->defaultKeySort(false)
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun Sertifikat')
                    ->options(fn (): array => PegawaiKompetensi::query()
                        ->whereNotNull('tanggal_sertifikat')
                        ->selectRaw('DISTINCT YEAR(tanggal_sertifikat) as y')
                        ->orderByDesc('y')
                        ->pluck('y', 'y')
                        ->all())
                    // Default tahun berjalan — user harus pilih "Semua Tahun"
                    // secara eksplisit kalau mau lihat lintas tahun.
                    ->default((string) now()->year)
                    ->query(fn (Builder $query, array $data): Builder => $query->filtered(['tahun' => $data['value'] ?? null])),
            ])
            ->recordUrl(fn ($record): string => PegawaiProfilResource::getUrl('view', ['record' => $record->nip]))
            ->emptyStateHeading('Tidak ada data kompetensi');
    }
}
