<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource\Pages;
use App\Models\Kepegawaian\PegawaiPenghargaan;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar mentah riwayat penghargaan pegawai (pegawai_penghargaan) — sumber
 * utamanya impor JSON SISDM (PegawaiImportService), tapi karena data
 * SIMPATIK/SISDM sering tak lengkap diisi staf, halaman ini juga menyediakan
 * tambah/edit/hapus manual. Baris manual ditandai sumber='manual' dan tidak
 * pernah ditimpa oleh impor berikutnya (lihat migration kolom sumber +
 * PegawaiImportService::importOne()).
 *
 * Baris di sini adalah bahan mentah untuk halaman Rekap Penghargaan
 * (checklist SLKS 10/20/30 per pegawai) — menambah baris manual di sini
 * langsung membuat centang muncul di rekap.
 */
class PegawaiPenghargaanResource extends Resource
{
    protected static ?string $model = PegawaiPenghargaan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Daftar Penghargaan';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Penghargaan';

    protected static ?string $pluralModelLabel = 'Penghargaan';

    protected static ?string $slug = 'daftar-penghargaan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pegawai')
                ->columns(2)
                ->schema([
                    Select::make('nip')
                        ->label('Pegawai')
                        ->options(fn (): array => PegawaiProfil::query()
                            ->orderBy('nama')
                            ->get(['nip', 'nama'])
                            ->mapWithKeys(fn (PegawaiProfil $p): array => [$p->nip => "{$p->nama} ({$p->nip})"])
                            ->all())
                        ->searchable()
                        ->required()
                        ->disabledOn('edit'),
                ]),

            Section::make('Penghargaan')
                ->columns(2)
                ->schema([
                    Select::make('jenis_penghargaan')
                        ->label('Tingkat SLKS')
                        ->options(PegawaiPenghargaan::TIER_LABELS)
                        ->helperText('Menentukan tingkat (10/20/30 tahun) yang tercentang di halaman Rekap Penghargaan.')
                        ->live()
                        ->afterStateUpdated(fn (Select $component, $state, callable $set) => $set(
                            'nama_penghargaan',
                            PegawaiPenghargaan::NAMA_DEFAULT[$state] ?? null,
                        ))
                        ->required(),
                    TextInput::make('nama_penghargaan')
                        ->label('Nama Penghargaan')
                        ->maxLength(255)
                        ->required(),
                    TextInput::make('asal_perolehan_penghargaan')
                        ->label('Asal Perolehan')
                        ->maxLength(255)
                        ->placeholder('Presiden RI'),
                    TextInput::make('nomor_sk_penghargaan')
                        ->label('Nomor SK')
                        ->maxLength(100),
                    DatePicker::make('tanggal_sk_penghargaan')
                        ->label('Tanggal SK')
                        ->native(false),
                    TextInput::make('peringkat_penghargaan')
                        ->label('Peringkat')
                        ->maxLength(100),
                    TextInput::make('file_penghargaan_url')
                        ->label('URL Berkas (opsional)')
                        ->url()
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('profil.nama')
                    ->label('Pegawai')
                    ->description(fn (PegawaiPenghargaan $record): ?string => $record->nip)
                    ->searchable(['nip'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('nama_penghargaan')
                    ->label('Nama Penghargaan')
                    ->description(fn (PegawaiPenghargaan $record): ?string => $record->jenis_penghargaan)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('nomor_sk_penghargaan')
                    ->label('Nomor SK')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('tanggal_sk_penghargaan')
                    ->label('Tanggal SK')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('sumber')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (string $state): string => $state === PegawaiPenghargaan::SUMBER_MANUAL ? 'warning' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === PegawaiPenghargaan::SUMBER_MANUAL ? 'Manual' : 'Impor SISDM')
                    ->tooltip(fn (string $state): string => $state === PegawaiPenghargaan::SUMBER_MANUAL
                        ? 'Ditambah manual — aman, tidak ikut tertimpa impor JSON berikutnya'
                        : 'Dari impor JSON SISDM — akan ditimpa/diganti pada impor pegawai ini berikutnya'),
            ])
            ->defaultSort('tanggal_sk_penghargaan', 'desc')
            ->filters([
                SelectFilter::make('jenis_penghargaan')
                    ->label('Tingkat SLKS')
                    ->options(PegawaiPenghargaan::TIER_LABELS),
                SelectFilter::make('sumber')
                    ->label('Sumber')
                    ->options([
                        PegawaiPenghargaan::SUMBER_IMPOR => 'Impor SISDM',
                        PegawaiPenghargaan::SUMBER_MANUAL => 'Manual',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data penghargaan')
            ->emptyStateDescription('Tambah manual, atau impor dari JSON SISDM di halaman Pegawai.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawaiPenghargaan::route('/'),
            'create' => Pages\CreatePegawaiPenghargaan::route('/create'),
            'edit' => Pages\EditPegawaiPenghargaan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nip', 'nama_penghargaan', 'nomor_sk_penghargaan'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {
        return $record->nama_penghargaan ?? 'Penghargaan';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'NIP' => $record->nip ?? '—',
            'No. SK' => $record->nomor_sk_penghargaan ?? '—',
        ];
    }
}
