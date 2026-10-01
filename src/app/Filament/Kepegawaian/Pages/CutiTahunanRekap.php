<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\CutiTahunanExport;
use App\Exports\Kepegawaian\CutiTahunanSheet;
use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\Cuti;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Rekap cuti tahunan per pegawai, pivot per tahun: satu pasang kolom
 * Terpakai/Sisa (mandiri, tanpa bawaan) untuk tahun berjalan dan dua tahun
 * sebelumnya, plus satu kolom Total yang menggabungkan ketiganya (tahun lama
 * dibatasi kontribusi Cuti::MAX_BAWAAN, tahun terakhir penuh). Tabel dan
 * export melewati Cuti::rekapTahunan() dengan daftar tahun yang sama, jadi
 * angkanya tak bisa beda.
 */
class CutiTahunanRekap extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Rekap Cuti Tahunan';

    protected static string|\UnitEnum|null $navigationGroup = 'Cuti';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Rekap Cuti Tahunan per Pegawai';

    protected string $view = 'filament.kepegawaian.pages.cuti-tahunan-rekap';

    /** @return int[] Tahun berjalan + dua tahun ke belakang, urut menaik. */
    private function tahunList(): array
    {
        $current = (int) now()->year;

        return [$current - 2, $current - 1, $current];
    }

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

    protected function exportRekap(string $writerType, string $extension): BinaryFileResponse
    {
        $tahun = $this->tahunList();
        $filename = 'rekap-cuti-tahunan-'.implode('-', $tahun).'-'.now()->format('Ymd-His').".{$extension}";

        // Excel = multi-sheet (sheet 1 rekap + sheet rincian per tahun);
        // PDF = sheet rekap saja — DOMPDF crash render ratusan baris mentah
        // (memory_limit habis), alasan sama seperti export Matriks/Evaluasi.
        $export = $writerType === ExcelFormat::XLSX
            ? new CutiTahunanExport($tahun)
            : new CutiTahunanSheet($tahun);

        return Excel::download($export, $filename, $writerType);
    }

    public function table(Table $table): Table
    {
        $tahun = $this->tahunList();
        $tahunTerakhir = end($tahun);

        return $table
            // Rekap dihitung di PHP (Cuti::rekapTahunan). Tabel berbasis Collection
            // (bukan query Eloquent) — sorting di sini HARUS ditangani di dalam
            // closure ini sendiri lewat $sortColumn/$sortDirection yang di-inject
            // Filament; ->defaultSort() bawaan Filament tak berlaku untuk data
            // source non-query (closure-nya tak pernah dipanggil).
            ->records(function (?string $sortColumn, ?string $sortDirection) use ($tahun): Collection {
                $records = Cuti::rekapTahunan($tahun);
                $desc = $sortDirection === 'desc';

                return (match ($sortColumn) {
                    'total_sisa' => $records->sortBy(fn (array $r) => $r['total_sisa'], SORT_REGULAR, $desc),
                    default => $records->sortBy(fn (array $r) => $r['nama'], SORT_REGULAR, $desc),
                })->values();
            })
            ->columns([
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn (array $record): ?string => $record['nip'])
                    ->placeholder('—')
                    ->wrap()
                    ->weight('medium')
                    ->sortable(),
                TextColumn::make('status_jabatan')
                    ->label('Status Jabatan')
                    ->placeholder('—')
                    ->wrap()
                    ->sortable(),
                ...$this->kolomTahun($tahun),
                TextColumn::make('rincian_total')
                    ->label('Penjumlahan')
                    // ->state(), BUKAN ->formatStateUsing(): kolom ini tak
                    // punya key asli di $record, dan TextColumn melewati
                    // formatStateUsing sepenuhnya bila state mentah (hasil
                    // data_get($record, 'rincian_total')) blank/null —
                    // ->state() mengisi state itu sendiri jadi tak pernah blank.
                    ->state(fn (array $record): string => $this->rincianTotal($record, $tahun, $tahunTerakhir))
                    ->description('sebelum ditambah')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->alignEnd(),
                TextColumn::make('total_sisa')
                    ->label('Total')
                    ->badge()
                    ->size('lg')
                    ->color(fn ($state): string => match (true) {
                        (int) $state < 0 => 'danger',
                        (int) $state === 0 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state): string => (int) $state < 0
                        ? 'hutang '.number_format(abs((int) $state)).' hari'
                        : number_format((int) $state).' hari')
                    ->tooltip('Dibatasi maksimal '.Cuti::MAX_SISA.' hari')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->recordUrl(fn (array $record): ?string => PegawaiProfilResource::getUrl('view', ['record' => $record['nip']]))
            ->emptyStateHeading('Tidak ada data cuti tahunan');
    }

    /**
     * Satu pasang kolom Terpakai/Sisa per tahun. Sisa MANDIRI (jatah tahun itu
     * dikurangi terpakai tahun itu saja) — tidak menumpuk bawaan dari tahun
     * sebelumnya; penggabungan antar tahun ada di kolom Total terpisah.
     *
     * @param  int[]  $tahun
     * @return TextColumn[]
     */
    private function kolomTahun(array $tahun): array
    {
        $kolom = [];

        foreach ($tahun as $y) {
            $kolom[] = TextColumn::make('total_hari_'.$y)
                ->label("Terpakai {$y}")
                ->badge()
                ->color('info')
                ->formatStateUsing(fn ($state): string => number_format((int) $state).' hari')
                ->alignEnd();

            $kolom[] = TextColumn::make('sisa_hari_'.$y)
                ->label("Sisa {$y}")
                ->badge()
                // Sisa negatif = hutang tahun itu sendiri (terpakai > jatah tahun
                // itu), dibedakan dari sisa 0 yang sekadar pas habis.
                ->color(fn ($state): string => match (true) {
                    (int) $state < 0 => 'danger',
                    (int) $state === 0 => 'warning',
                    default => 'success',
                })
                ->formatStateUsing(fn ($state): string => (int) $state < 0
                    ? 'hutang '.number_format(abs((int) $state)).' hari'
                    : number_format((int) $state).' hari')
                ->tooltip(fn (array $record): string => "Jatah {$y}: {$record['jatah_'.$y]} hari")
                ->alignEnd();
        }

        return $kolom;
    }

    /**
     * Angka-angka yang dijumlahkan jadi Total, SEBELUM ditambahkan — mis.
     * "2 + 6 + 9". Tahun-tahun sebelum yang terakhir sudah dipotong ke
     * Cuti::MAX_BAWAAN (kalau sisa mandirinya lebih besar), tahun terakhir
     * ditulis apa adanya; harus identik dengan penjumlahan yang menghasilkan
     * total_sisa di Cuti::rekapTahunan() supaya kolom ini tak pernah beda
     * dari kolom Total di sebelahnya.
     *
     * @param  array<string, mixed>  $record
     * @param  int[]  $tahun
     */
    private function rincianTotal(array $record, array $tahun, int $tahunTerakhir): string
    {
        $bagian = [];

        foreach ($tahun as $y) {
            $sisa = (int) $record['sisa_hari_'.$y];
            $bagian[] = (string) ($y === $tahunTerakhir ? $sisa : min($sisa, Cuti::MAX_BAWAAN));
        }

        return implode(' + ', $bagian);
    }
}
