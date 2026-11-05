<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\RekapAbsenResource\Pages;
use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenDataDukung;
use App\Models\Kepegawaian\RekapAbsenTpp;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Rekap absensi bulanan per pegawai. Data HANYA masuk dari API
 * (`/api/v1/rekap-absen`, scope `rekap-absen`) yang dipanggil Chrome
 * extension presensi — panel ini baca saja: tanpa create, edit, maupun hapus.
 */
class RekapAbsenResource extends Resource
{
    protected static ?string $model = RekapAbsen::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Rekap Absen Bulanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'Rekap Absen';

    protected static ?string $pluralModelLabel = 'Rekap Absen Bulanan';

    protected static ?string $slug = 'rekap-absen';

    protected static ?string $recordTitleAttribute = 'nama';

    /**
     * Non-admin hanya melihat rekap miliknya sendiri (nip akun).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user === null) {
            return $query;
        }

        if ((int) $user->role === 1 || (bool) $user->is_admin_kepegawaian) {
            return $query;
        }

        return filled($user->nip)
            ? $query->where('nip', $user->nip)
            : $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        $periodeDefault = now()->subMonthNoOverflow();

        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable(['nama', 'nip'])
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (RekapAbsen $record): string => (string) $record->nip),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->state(fn (RekapAbsen $record): string => $record->labelPeriode())
                    ->badge()
                    ->color('gray')
                    ->sortable(['tahun', 'bulan']),
                TextColumn::make('hari_kerja')
                    ->label('Hari Kerja')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('jumlah_hadir')
                    ->label('Hadir')
                    ->numeric()
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('jumlah_tidak_hadir')
                    ->label('Tidak Hadir')
                    ->numeric()
                    ->alignCenter()
                    ->color('danger'),
                TextColumn::make('jumlah_konfirmasi')
                    ->label('Konfirmasi')
                    ->numeric()
                    ->alignCenter()
                    ->color('warning'),
                TextColumn::make('harian_konfirmasi_tanpa_dukung_count')
                    ->label('Belum Konfirmasi')
                    ->counts('harianKonfirmasiTanpaDukung')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (mixed $state): string => (int) $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('total_keterlambatan')
                    ->label('Keterlambatan')
                    ->placeholder('00:00:00')
                    // Disimpan sebagai teks "HH:MM:SS" — urutkan sebagai detik,
                    // bukan alfabetis, dan null dianggap 0.
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        'coalesce(time_to_sec(total_keterlambatan), 0) '.($direction === 'desc' ? 'desc' : 'asc'),
                    )),
                TextColumn::make('diunggah_at')
                    ->label('Diunggah')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('diunggah_at', 'desc')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->default($periodeDefault->year)
                    ->options(fn (): array => RekapAbsen::query()
                        ->select('tahun')
                        ->distinct()
                        ->orderByDesc('tahun')
                        ->pluck('tahun', 'tahun')
                        ->all()),
                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->default($periodeDefault->month)
                    ->options(RekapAbsen::BULAN),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordUrl(fn (RekapAbsen $record): string => Pages\ViewRekapAbsen::getUrl(['record' => $record]))
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('Belum ada rekap absen')
            ->emptyStateDescription('Menampilkan bulan lalu (h-1). Ubah filter Tahun/Bulan untuk periode lain — data masuk otomatis dari Chrome extension presensi lewat API.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama')
                        ->label('Nama')
                        ->weight('bold')
                        ->columnSpanFull(),
                    TextEntry::make('nip')->label('NIP'),
                    TextEntry::make('periode')
                        ->label('Periode')
                        ->state(fn (RekapAbsen $record): string => $record->labelPeriode()),
                    TextEntry::make('jabatan')->label('Jabatan')->placeholder('—'),
                    TextEntry::make('opd')->label('OPD')->placeholder('—'),
                ]),
            Section::make('Ringkasan')
                ->columns(5)
                ->schema([
                    TextEntry::make('hari_kerja')->label('Hari Kerja')->numeric(),
                    TextEntry::make('jumlah_hadir')->label('Hadir')->numeric()->color('success'),
                    TextEntry::make('jumlah_tidak_hadir')->label('Tidak Hadir')->numeric()->color('danger'),
                    TextEntry::make('jumlah_konfirmasi')->label('Konfirmasi')->numeric()->color('warning'),
                    TextEntry::make('total_keterlambatan')->label('Keterlambatan')->placeholder('00:00:00'),
                ]),
            Section::make('Data Dukung')
                ->schema([
                    RepeatableEntry::make('dataDukung')
                        ->label('')
                        ->schema([
                            TextEntry::make('tanggal_mulai')->label('Tanggal Mulai')->date('d M Y')->placeholder('—'),
                            TextEntry::make('tanggal_selesai')->label('Tanggal Selesai')->date('d M Y')->placeholder('—'),
                            TextEntry::make('keterangan')->label('Keterangan')->placeholder('—'),
                            TextEntry::make('jenis')->label('Jenis Data Dukung')->placeholder('—'),
                            TextEntry::make('lampiran')
                                ->label('Lampiran')
                                ->state(fn (RekapAbsenDataDukung $record): string => $record->tautan() === null ? '—' : 'Lihat')
                                ->url(fn (RekapAbsenDataDukung $record): ?string => $record->tautan())
                                ->openUrlInNewTab()
                                ->color('primary'),
                        ])
                        ->columns(5),
                ]),
            Section::make('Data Dukung TPP')
                ->description('Sumber: tpp.semarangkota.go.id/izin — terpisah dari data dukung absen. Tanggal di sini dianggap sudah konfirmasi.')
                ->schema([
                    RepeatableEntry::make('tpp')
                        ->label('')
                        ->schema([
                            TextEntry::make('jenis')
                                ->label('Jenis')
                                ->badge()
                                ->color('gray')
                                ->placeholder('—'),
                            TextEntry::make('tanggal_teks')
                                ->label('Tanggal')
                                ->state(fn (RekapAbsenTpp $record): string => $record->tanggalTeks())
                                ->columnSpan(2),
                            TextEntry::make('jumlah_hari')
                                ->label('Jumlah Hari')
                                ->numeric(),
                            TextEntry::make('keterangan')
                                ->label('Keterangan')
                                ->wrap()
                                ->columnSpanFull()
                                ->placeholder('—'),
                            TextEntry::make('file_pendukung_status')
                                ->label('File Pendukung')
                                ->badge()
                                ->state(fn (RekapAbsenTpp $record): string => $record->statusFileTeks())
                                ->color(fn (RekapAbsenTpp $record): string => $record->valid() ? 'success' : 'danger'),
                            TextEntry::make('file_pendukung_nama')
                                ->label('Berkas')
                                ->state(fn (RekapAbsenTpp $record): string => $record->tautanFile() === null
                                    ? ($record->file_pendukung_nama ?: 'File pendukung belum diupload')
                                    : (string) $record->file_pendukung_nama)
                                ->url(fn (RekapAbsenTpp $record): ?string => $record->tautanFile())
                                ->openUrlInNewTab()
                                ->color(fn (RekapAbsenTpp $record): string => $record->tautanFile() === null ? 'gray' : 'primary')
                                ->columnSpan(2),
                        ])
                        ->columns(4),
                ]),
            Section::make('Unggahan')
                ->columns(3)
                ->schema([
                    TextEntry::make('diunggah_at')->label('Diunggah')->dateTime('d M Y H:i')->placeholder('—'),
                    TextEntry::make('diunggah_oleh_nama')->label('Oleh')->placeholder('—'),
                    TextEntry::make('sumber_label')->label('Sumber')->placeholder('—'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RekapAbsenResource\RelationManagers\HarianRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRekapAbsen::route('/'),
            'view' => Pages\ViewRekapAbsen::route('/{record}'),
        ];
    }
}
