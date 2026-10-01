<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\PegawaiKompetensiResource\Pages;
use App\Models\Kepegawaian\PegawaiKompetensi;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cross-employee competency listing. Filters delegate to
 * PegawaiKompetensi::scopeFiltered() so this page and KompetensiExport can
 * never drift apart on what "filtered" means.
 */
class PegawaiKompetensiResource extends Resource
{
    protected static ?string $model = PegawaiKompetensi::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Kompetensi';

    protected static string | \UnitEnum | null $navigationGroup = 'Kompetensi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kompetensi';

    protected static ?string $pluralModelLabel = 'Kompetensi';

    protected static ?string $slug = 'kompetensi';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('profil'))
            ->columns([
                TextColumn::make('profil.nama')
                    ->label('Pegawai')
                    ->description(fn (PegawaiKompetensi $record): ?string => $record->nip)
                    ->placeholder('—')
                    ->wrap()
                    ->weight('medium')
                    // One searchable column, routed through the shared scope, so the
                    // table search covers exactly the fields the export's "q" does.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->filtered(['q' => $search])),
                TextColumn::make('nama_kompetensi')
                    ->label('Nama Kompetensi')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                ToggleColumn::make('sesuai_jabatan')
                    ->label('Sesuai Jabatan')
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip(fn (bool $state): string => $state
                        ? 'Sesuai kebutuhan jabatan — klik untuk tandai tidak sesuai'
                        : 'Tidak sesuai jabatan — klik untuk tandai sesuai'),
                TextColumn::make('penyelenggara')
                    ->label('Penyelenggara')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('jumlah_jam')
                    ->label('JP')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tanggal_sertifikat')
                    ->label('Tgl. Sertifikat')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('nomor_sertifikat')
                    ->label('No. Sertifikat')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_sertifikat', 'desc')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun Sertifikat')
                    ->options(fn (): array => PegawaiKompetensi::query()
                        ->whereNotNull('tanggal_sertifikat')
                        ->selectRaw('DISTINCT YEAR(tanggal_sertifikat) as y')
                        ->orderByDesc('y')
                        ->pluck('y', 'y')
                        ->all())
                    // Delegate to the shared scope rather than re-deriving the filter.
                    ->query(fn (Builder $query, array $data): Builder => $query->filtered(['tahun' => $data['value'] ?? null])),
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options(fn (): array => PegawaiKompetensi::query()
                        ->whereNotNull('jenis')
                        ->distinct()
                        ->orderBy('jenis')
                        ->pluck('jenis', 'jenis')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->filtered(['jenis' => $data['value'] ?? null])),
                TernaryFilter::make('sesuai_jabatan')
                    ->label('Sesuai Jabatan')
                    ->placeholder('Semua')
                    ->trueLabel('Sesuai')
                    ->falseLabel('Tidak sesuai'),
            ])
            ->searchPlaceholder('NIP / nama / kompetensi / penyelenggara')
            ->emptyStateHeading('Tidak ada data kompetensi');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawaiKompetensi::route('/'),
        ];
    }
}
