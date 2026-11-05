<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\RekapAbsenBelumKonfirmasiExport;
use App\Filament\Kepegawaian\Resources\RekapAbsenResource;
use App\Filament\Kepegawaian\Resources\RekapAbsenResource\Pages\ViewRekapAbsen;
use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenHarian;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Daftar pegawai yang masih punya tanggal "Belum Konfirmasi": baris harian
 * berstatus konfirmasi yang tanggalnya tidak tercakup data dukung absen
 * maupun data dukung e-TPP. Sumber data sama dengan kolom "Belum Konfirmasi"
 * di Rekap Absen Bulanan; unduhan Excel-nya juga memakai isi halaman ini.
 *
 * Default periode bulan lalu (h-1), filter tampil di atas tabel.
 */
class RekapBelumKonfirmasi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Rekap Belum Konfirmasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Rekap Belum Konfirmasi Absen';

    protected static ?string $slug = 'rekap-belum-konfirmasi';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected string $view = 'filament.kepegawaian.pages.rekap-belum-konfirmasi';

    /** Hasil Generate dari baris yang dicentang, tampil di atas tabel. */
    public ?string $teksGenerate = null;

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Pegawai yang tanggal konfirmasinya belum ditutup data dukung absen maupun data dukung e-TPP.';
    }

    public function table(Table $table): Table
    {
        $periodeDefault = now()->subMonthNoOverflow();

        return $table
            ->query(fn (): Builder => RekapAbsenResource::getEloquentQuery()
                ->whereHas('harianKonfirmasiTanpaDukung')
                ->withCount('harianKonfirmasiTanpaDukung')
                ->with(['harianKonfirmasiTanpaDukung' => fn ($query) => $query->orderBy('tanggal')]))
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
                    ->placeholder('—'),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->state(fn (RekapAbsen $record): string => $record->labelPeriode())
                    ->badge()
                    ->color('gray')
                    ->sortable(['tahun', 'bulan']),
                TextColumn::make('harian_konfirmasi_tanpa_dukung_count')
                    ->label('Jumlah')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('tanggal_belum_konfirmasi')
                    ->label('Tanggal Belum Konfirmasi')
                    ->state(fn (RekapAbsen $record): string => $record->harianKonfirmasiTanpaDukung
                        ->map(fn ($harian): ?string => self::tanggalIndo($harian->tanggal))
                        ->filter()
                        ->implode(', ') ?: '—')
                    ->wrap(),
            ])
            ->defaultSort('nama')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
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
            ->recordActions([
                Action::make('buka_rekap')
                    ->label('Lihat Rekap')
                    ->icon('heroicon-o-eye')
                    ->url(fn (RekapAbsen $record): string => ViewRekapAbsen::getUrl(['record' => $record])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('generate')
                        ->label('Generate')
                        ->icon('heroicon-o-document-text')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $this->generate($records)),
                ]),
            ])
            ->emptyStateHeading('Tidak ada yang belum konfirmasi')
            ->emptyStateDescription('Semua tanggal konfirmasi pada periode terpilih sudah punya data dukung. Ubah filter Tahun/Bulan untuk periode lain.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhBelumKonfirmasi')
                ->label('Unduh Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): BinaryFileResponse => $this->unduhBelumKonfirmasi()),
        ];
    }

    /**
     * Unduh daftar pegawai + tanggal belum konfirmasi, mengikuti
     * pencarian/filter tabel yang sedang tampil (tanpa paginasi).
     */
    protected function unduhBelumKonfirmasi(): BinaryFileResponse
    {
        return Excel::download(
            new RekapAbsenBelumKonfirmasiExport($this->getTableQueryForExport()),
            $this->namaBerkas(),
        );
    }

    /**
     * Susun teks dari baris yang dicentang, satu baris per pegawai:
     * "1. {nip} # {jumlah} # Tanggal 30, 31"
     *
     * @param  Collection<int, RekapAbsen>  $records
     */
    protected function generate(Collection $records): void
    {
        $this->teksGenerate = $records->values()
            ->map(function (RekapAbsen $record, int $index): string {
                $tanggal = $this->hariBelumKonfirmasi($record);

                return ($index + 1).'. '.$record->nip.' # '.$tanggal->count().' # Tanggal '
                    .$tanggal
                        ->map(fn (RekapAbsenHarian $harian): string => $harian->tanggal?->format('j') ?? '-')
                        ->implode(', ');
            })
            ->implode("\n");

        Notification::make()
            ->title($records->count().' baris dibuat')
            ->body('Hasil tampil di atas tabel — klik Salin untuk menyalin teks.')
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, RekapAbsenHarian>
     */
    protected function hariBelumKonfirmasi(RekapAbsen $record): Collection
    {
        return $record->relationLoaded('harianKonfirmasiTanpaDukung')
            ? $record->harianKonfirmasiTanpaDukung->sortBy('tanggal')->values()
            : $record->harianKonfirmasiTanpaDukung()->orderBy('tanggal')->get();
    }

    public function salinGenerate(): void
    {
        if (blank($this->teksGenerate)) {
            return;
        }

        // Clipboard API dengan fallback textarea + execCommand bila izin ditolak.
        $this->js(
            '(async function (teks) { try { await navigator.clipboard.writeText(teks); } catch (e) {'
            .' var ta = document.createElement("textarea"); ta.value = teks;'
            .' ta.style.position = "fixed"; ta.style.opacity = "0";'
            .' document.body.appendChild(ta); ta.select();'
            .' try { document.execCommand("copy"); } catch (err) {} ta.remove(); } })'
            .'('.Js::from($this->teksGenerate).');',
        );

        Notification::make()
            ->title('Teks disalin ke clipboard')
            ->success()
            ->send();
    }

    /**
     * Nama berkas mengikuti periode filter aktif:
     * belum-konfirmasi-{bulan}-{tahun}.xlsx.
     */
    protected function namaBerkas(string $awalan = 'belum-konfirmasi'): string
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

    private static function tanggalIndo(?\DateTimeInterface $tanggal): ?string
    {
        if ($tanggal === null) {
            return null;
        }

        $bulan = RekapAbsen::BULAN[(int) $tanggal->format('n')] ?? $tanggal->format('m');

        return $tanggal->format('j').' '.$bulan.' '.$tanggal->format('Y');
    }

    public static function canAccess(): bool
    {
        return RekapAbsenResource::canViewAny();
    }
}
