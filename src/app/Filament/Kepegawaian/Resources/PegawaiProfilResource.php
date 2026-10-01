<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only: pegawai_profil is sourced externally (sisdm scrape) and written
 * only by PegawaiImportService, never authored here.
 */
class PegawaiProfilResource extends Resource
{
    protected static ?string $model = PegawaiProfil::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Pegawai';

    protected static ?string $slug = 'pegawai';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')
                ->columns(3)
                ->schema([
                    TextEntry::make('nama')->label('Nama')->weight('bold'),
                    TextEntry::make('nip')->label('NIP')->copyable(),
                    TextEntry::make('nip_lama')->label('NIP Lama')->placeholder('—'),
                    TextEntry::make('gender')->label('Jenis Kelamin')->placeholder('—'),
                    TextEntry::make('agama')->placeholder('—'),
                    TextEntry::make('status_perkawinan')->label('Status Perkawinan')->placeholder('—'),
                    TextEntry::make('tempat_lahir')->label('Tempat Lahir')->placeholder('—'),
                    TextEntry::make('tanggal_lahir')->label('Tanggal Lahir')->date('d M Y')->placeholder('—'),
                    TextEntry::make('usia_keterangan')->label('Usia')->placeholder('—'),
                ]),

            Section::make('Kepegawaian')
                ->columns(3)
                ->schema([
                    TextEntry::make('jabatan')->placeholder('—')->columnSpan(2),
                    TextEntry::make('status_pegawai')->label('Status Pegawai')->badge()->placeholder('—'),
                    TextEntry::make('pangkat')->placeholder('—'),
                    TextEntry::make('golongan')->placeholder('—'),
                    TextEntry::make('tmt_golongan')->label('TMT Golongan')->date('d M Y')->placeholder('—'),
                    TextEntry::make('kelas_jabatan')->label('Kelas Jabatan')->placeholder('—'),
                    TextEntry::make('pendidikan')->placeholder('—'),
                    TextEntry::make('capaian_bangkom')->label('Capaian Bangkom')->placeholder('—'),
                    TextEntry::make('bup_but')->label('BUP/BUT')->placeholder('—'),
                    TextEntry::make('tmt_bup_but')->label('TMT BUP/BUT')->date('d M Y')->placeholder('—'),
                    TextEntry::make('kgb_selanjutnya')->label('KGB Selanjutnya')->date('d M Y')->placeholder('—'),
                ]),

            Section::make('Alamat KTP')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('alamat_ktp')->label('Alamat')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('rt_ktp')->label('RT')->placeholder('—'),
                    TextEntry::make('rw_ktp')->label('RW')->placeholder('—'),
                    TextEntry::make('kode_pos_ktp')->label('Kode Pos')->placeholder('—'),
                    TextEntry::make('kelurahan_ktp')->label('Kelurahan')->placeholder('—'),
                    TextEntry::make('kecamatan_ktp')->label('Kecamatan')->placeholder('—'),
                    TextEntry::make('kota_ktp')->label('Kota')->placeholder('—'),
                    TextEntry::make('provinsi_ktp')->label('Provinsi')->placeholder('—'),
                ]),

            Section::make('Alamat Domisili')
                ->columns(3)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('alamat_domisili')->label('Alamat')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('rt_domisili')->label('RT')->placeholder('—'),
                    TextEntry::make('rw_domisili')->label('RW')->placeholder('—'),
                    TextEntry::make('kode_pos_domisili')->label('Kode Pos')->placeholder('—'),
                    TextEntry::make('kelurahan_domisili')->label('Kelurahan')->placeholder('—'),
                    TextEntry::make('kecamatan_domisili')->label('Kecamatan')->placeholder('—'),
                    TextEntry::make('kota_domisili')->label('Kota')->placeholder('—'),
                    TextEntry::make('provinsi_domisili')->label('Provinsi')->placeholder('—'),
                    TextEntry::make('jenis_domisili')->label('Jenis Domisili')->placeholder('—'),
                ]),

            Section::make('Sumber Data')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextEntry::make('sumber_url')->label('Sumber')->url(fn (?PegawaiProfil $record) => $record?->sumber_url)->openUrlInNewTab()->placeholder('—'),
                    TextEntry::make('created_at')->label('Diimpor')->dateTime('d M Y H:i')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->description(fn (PegawaiProfil $record): ?string => $record->nip)
                    ->searchable(['nama', 'nip'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('jabatan')
                    ->searchable()
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('golongan')
                    ->label('Gol.')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status_pegawai')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => $state && str_contains(strtolower($state), 'pns') ? 'success' : 'warning')
                    ->placeholder('—'),
                TextColumn::make('tmt_bup_but')
                    ->label('TMT Pensiun')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('kompetensi_count')
                    ->label('Kompetensi')
                    ->counts('kompetensi')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('anak_count')
                    ->label('Anak')
                    ->counts('anak')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nama')
            ->filters([
                SelectFilter::make('status_pegawai')
                    ->label('Status Pegawai')
                    ->options(fn (): array => PegawaiProfil::query()
                        ->whereNotNull('status_pegawai')
                        ->distinct()
                        ->orderBy('status_pegawai')
                        ->pluck('status_pegawai', 'status_pegawai')
                        ->all()),
                SelectFilter::make('golongan')
                    ->label('Golongan')
                    ->options(fn (): array => PegawaiProfil::query()
                        ->whereNotNull('golongan')
                        ->distinct()
                        ->orderBy('golongan')
                        ->pluck('golongan', 'golongan')
                        ->all()),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('Belum ada data pegawai')
            ->emptyStateDescription('Impor dari file JSON hasil scrape SISDM.');
    }

    public static function getRelations(): array
    {
        return [
            PegawaiProfilResource\RelationManagers\CutiRelationManager::class,
            PegawaiProfilResource\RelationManagers\AnakRelationManager::class,
            PegawaiProfilResource\RelationManagers\KompetensiRelationManager::class,
            PegawaiProfilResource\RelationManagers\PenghargaanRelationManager::class,
            PegawaiProfilResource\RelationManagers\ArsipRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawaiProfil::route('/'),
            'view' => Pages\ViewPegawaiProfil::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'nip', 'jabatan'];
    }
}
