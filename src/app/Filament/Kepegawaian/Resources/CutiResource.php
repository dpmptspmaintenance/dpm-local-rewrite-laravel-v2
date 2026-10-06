<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\CutiResource\Pages;
use App\Models\Kepegawaian\Cuti;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CutiResource extends Resource
{
    protected static ?string $model = Cuti::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Cuti';

    protected static string|\UnitEnum|null $navigationGroup = 'Cuti';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Cuti';

    protected static ?string $pluralModelLabel = 'Cuti';

    protected static ?string $slug = 'cuti';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')
                ->columns(2)
                ->schema([
                    TextInput::make('no_surat')->label('No. Surat')->maxLength(100),
                    Select::make('jenis')
                        ->label('Jenis Cuti')
                        ->options(array_combine(Cuti::jenisOptions(), Cuti::jenisOptions()))
                        ->searchable(),
                    TextInput::make('nip')->label('NIP')->maxLength(100),
                    TextInput::make('nama')->label('Nama Pegawai')->maxLength(255),
                ]),

            Section::make('Periode')
                ->columns(3)
                ->schema([
                    DatePicker::make('tanggal_mulai_diajukan')
                        ->label('Tanggal Mulai')
                        ->native(false)
                        ->required(),
                    DatePicker::make('tanggal_selesai_diajukan')
                        ->label('Tanggal Selesai')
                        ->native(false)
                        ->required(),
                    TextInput::make('durasi_hari')
                        ->label('Durasi (hari)')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Otomatis terisi dari tanggal saat disimpan bila rentang valid. Isi manual hanya untuk tanggal bermasalah (sama/terbalik) — dipakai sebagai pengganti hitungan.'),
                ]),

            Section::make('Unit Kerja')
                ->columns(2)
                ->schema([
                    TextInput::make('opd')->label('OPD')->maxLength(255),
                    TextInput::make('unit_kerja')->label('Unit Kerja')->maxLength(255),
                    TextInput::make('lokasi_kerja')->label('Lokasi Kerja')->maxLength(255),
                    TextInput::make('status')->label('Status')->maxLength(100),
                ]),

            Section::make('Keterangan')->schema([
                Textarea::make('keperluan')->label('Keperluan')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_surat')
                    ->label('No. Surat')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn (Cuti $record): ?string => $record->nip)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Cuti Tahunan' => 'info',
                        'Cuti Sakit' => 'warning',
                        'Cuti Melahirkan' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('tanggal_mulai_diajukan')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('tanggal_selesai_diajukan')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('durasi_hari')
                    ->label('Hari')
                    ->numeric()
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('diedit_manual')
                    ->label('Edit Manual')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->tooltip('Pernah diedit manual — tidak ditimpa import Excel')
                    ->toggleable(),
            ])
            ->defaultSort('tanggal_mulai_diajukan', 'desc')
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis Cuti')
                    ->options(array_combine(Cuti::jenisOptions(), Cuti::jenisOptions())),
                Filter::make('periode')
                    ->schema([
                        DatePicker::make('from')->label('Dari')->native(false),
                        DatePicker::make('to')->label('Sampai')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal_mulai_diajukan', '>=', $date))
                        ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('tanggal_selesai_diajukan', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data cuti')
            ->emptyStateDescription('Tambah manual, atau impor dari file Excel.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCuti::route('/'),
            'create' => Pages\CreateCuti::route('/create'),
            'view' => Pages\ViewCuti::route('/{record}'),
            'edit' => Pages\EditCuti::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['no_surat', 'nip', 'nama'];
    }

    /** Kategori global search muncul setelah Pegawai (sort 2). */
    public static function getGlobalSearchSort(): ?int
    {
        return 2;
    }

    public static function getGlobalSearchResultTitle(Model $record): string | Htmlable
    {
        return $record->nama ?? $record->no_surat ?? 'Cuti';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Jenis' => $record->jenis ?? '—',
            'No. Surat' => $record->no_surat ?? '—',
            'NIP' => $record->nip ?? '—',
        ];
    }
}
