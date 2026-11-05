<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\RekapAbsenResource;
use App\Filament\Kepegawaian\Resources\RekapAbsenResource\Pages\ViewRekapAbsen;
use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenDataDukung;
use App\Models\Kepegawaian\RekapAbsenHarian;
use App\Models\Kepegawaian\RekapAbsenTpp;
use App\Models\Kepegawaian\RekapAbsenTppTanggal;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deteksi baris presensi berstatus konfirmasi (KONFIRMASI) yang BELUM punya
 * data dukung pada tanggal tersebut. Data dukung dianggap menutup tanggal
 * bila rentang tanggal_mulai..tanggal_selesai memuat tanggal presensi.
 *
 * Default menampilkan yang belum ada, periode bulan lalu (h-1).
 */
class DeteksiDataDukung extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Deteksi Data Dukung';

    protected static string|\UnitEnum|null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Deteksi Data Dukung';

    protected static ?string $slug = 'deteksi-data-dukung';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected string $view = 'filament.kepegawaian.pages.deteksi-data-dukung';

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Baris konfirmasi presensi yang belum memiliki data dukung pada tanggalnya.';
    }

    public function table(Table $table): Table
    {
        $periodeDefault = now()->subMonthNoOverflow();
        $harianTable = (new RekapAbsenHarian)->getTable();
        $dukungTable = (new RekapAbsenDataDukung)->getTable();
        $rekapTable = (new RekapAbsen)->getTable();
        $tppTable = (new RekapAbsenTpp)->getTable();
        $tppTanggalTable = (new RekapAbsenTppTanggal)->getTable();

        $existsSql = 'exists (select 1 from '.$dukungTable.' as dd'
            .' where dd.rekap_absen_id = '.$harianTable.'.rekap_absen_id'
            .' and dd.tanggal_mulai is not null'
            .' and dd.tanggal_selesai is not null'
            .' and '.$harianTable.'.tanggal between dd.tanggal_mulai and dd.tanggal_selesai)';

        // Data dukung TPP hanya menutup konfirmasi bila file pendukung sudah diunggah.
        $tppCoverSql = 'exists (select 1 from '.$tppTanggalTable.' tt'
            .' join '.$tppTable.' tpp on tpp.id = tt.rekap_absen_tpp_id'
            .' join '.$rekapTable.' r on r.nip = tpp.nip and r.tahun = tpp.tahun and r.bulan = tpp.bulan'
            .' where r.id = '.$harianTable.'.rekap_absen_id'
            .' and tpp.file_pendukung_status = \'ada\''
            .' and tt.tanggal = '.$harianTable.'.tanggal)';

        $kurangSql = 'not ('.$existsSql.') and not ('.$tppCoverSql.')';

        return $table
            ->query(fn (): Builder => RekapAbsenHarian::query()
                ->with(['rekapAbsen.dataDukung'])
                ->whereNotNull('status')
                ->select($harianTable.'.*')
                ->selectRaw('('.$existsSql.' or '.$tppCoverSql.') as punya_data_dukung'))
            ->columns([
                TextColumn::make('rekapAbsen.nama')
                    ->label('Nama')
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (RekapAbsenHarian $record): string => (string) $record->rekapAbsen?->nip),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->state(fn (RekapAbsenHarian $record): string => $record->rekapAbsen?->labelPeriode() ?? '—')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('hari')
                    ->label('Hari')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('punya_data_dukung')
                    ->label('Data Dukung')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state ? 'Ada' : 'Belum ada')
                    ->color(fn (mixed $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('keterangan_dukung')
                    ->label('Keterangan Data Dukung')
                    ->state(function (RekapAbsenHarian $record): string {
                        $absen = $record->dukunganTerkait()
                            ->map(fn (RekapAbsenDataDukung $dukung): string => (string) ($dukung->keterangan ?: $dukung->jenis))
                            ->filter()
                            ->implode(', ');

                        if ($absen !== '') {
                            return $absen;
                        }

                        $tpp = $record->tppTerkait();

                        return $tpp === null ? '—' : 'TPP: '.($tpp->jenis ?: $tpp->keterangan);
                    })
                    ->wrap(),
            ])
            ->defaultSort('tanggal')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->default($periodeDefault->year)
                    ->options(fn (): array => RekapAbsen::query()
                        ->select('tahun')
                        ->distinct()
                        ->orderByDesc('tahun')
                        ->pluck('tahun', 'tahun')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $nilai = $data['value'] ?? null;

                        if ($nilai === null || $nilai === '') {
                            return $query;
                        }

                        return $query->whereHas(
                            'rekapAbsen',
                            fn (Builder $rekap): Builder => $rekap->where('tahun', $nilai),
                        );
                    }),
                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->default($periodeDefault->month)
                    ->options(RekapAbsen::BULAN)
                    ->query(function (Builder $query, array $data): Builder {
                        $nilai = $data['value'] ?? null;

                        if ($nilai === null || $nilai === '') {
                            return $query;
                        }

                        return $query->whereHas(
                            'rekapAbsen',
                            fn (Builder $rekap): Builder => $rekap->where('bulan', $nilai),
                        );
                    }),
                Filter::make('data_dukung')
                    ->label('Data Dukung')
                    ->schema([
                        Select::make('nilai')
                            ->label('Status data dukung')
                            ->options([
                                'tidak' => 'Belum ada',
                                'ada' => 'Sudah ada',
                            ])
                            ->default('tidak')
                            ->selectablePlaceholder(false),
                    ])
                    ->query(function (Builder $query, array $data) use ($kurangSql): Builder {
                        return ($data['nilai'] ?? 'tidak') === 'ada'
                            ? $query->whereRaw('not ('.$kurangSql.')')
                            : $query->whereRaw($kurangSql);
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('buka_rekap')
                    ->label('Lihat Rekap')
                    ->icon('heroicon-o-eye')
                    ->url(fn (RekapAbsenHarian $record): string => ViewRekapAbsen::getUrl(['record' => $record->rekap_absen_id])),
                Action::make('buka_lampiran')
                    ->label('Buka Lampiran')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (RekapAbsenHarian $record): ?string => $record->tautanLampiran())
                    ->openUrlInNewTab()
                    ->visible(fn (RekapAbsenHarian $record): bool => $record->tautanLampiran() !== null),
            ])
            ->emptyStateHeading('Semua konfirmasi sudah punya data dukung')
            ->emptyStateDescription('Tidak ada baris konfirmasi tanpa data dukung pada periode terpilih. Ubah filter Tahun/Bulan atau status Data Dukung.');
    }

    public static function canAccess(): bool
    {
        return RekapAbsenResource::canViewAny();
    }
}
