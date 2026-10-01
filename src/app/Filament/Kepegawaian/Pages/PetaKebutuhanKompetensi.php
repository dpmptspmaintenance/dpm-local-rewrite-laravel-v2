<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\PetaKebutuhanSheet;
use App\Filament\Kepegawaian\Concerns\SelectsKompetensiTahun;
use App\Models\Kepegawaian\PegawaiKompetensi;
use App\Models\Kepegawaian\PegawaiProfil;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Peta kebutuhan kompetensi seluruh OPD, satu baris per jabatan: berapa
 * pegawai di jabatan itu, berapa yang sudah punya kompetensi tercatat tahun
 * ini, berapa event/JP-nya, dan berapa yang masih kosong.
 *
 * "Kebutuhan" di sini murni sebaran — tidak ada target JP atau angka baku
 * dari sumber mana pun (tabel rencana/kebutuhan diklat tidak ada di DB),
 * jadi halaman ini melaporkan apa adanya, bukan menilai kurang/sesuai.
 *
 * Sumber: scope RekapJabatan() yang sama dipakai halaman ini dan matriks.
 */
class PetaKebutuhanKompetensi extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;
    use SelectsKompetensiTahun;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Peta Kebutuhan Kompetensi';

    protected static string | \UnitEnum | null $navigationGroup = 'Kompetensi';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Peta Kebutuhan Kompetensi Seluruh OPD';

    protected string $view = 'filament.kepegawaian.pages.peta-kebutuhan-kompetensi';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportPeta(ExcelFormat::XLSX, 'xlsx')),
            Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportPeta(ExcelFormat::DOMPDF, 'pdf')),
        ];
    }

    /**
     * Ekspor tahun yang sedang terpilih — sumbernya sama dengan tabel di
     * layar (rekapJabatan), jadi angka tak bisa beda.
     */
    protected function exportPeta(string $writerType, string $extension): BinaryFileResponse
    {
        $tahun = $this->selectedTahun();
        $filename = "peta-kebutuhan-kompetensi-{$tahun}-".now()->format('Ymd-His').".{$extension}";

        return Excel::download(new PetaKebutuhanSheet($tahun), $filename, $writerType);
    }

    /**
     * Ringkasan setahun: total pegawai, berapa yang punya kompetensi, berapa
     * yang belum, total event, dan total JP.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $tahun = $this->selectedTahun();

        $totalPegawai = PegawaiProfil::count();

        $berkompetensi = PegawaiKompetensi::query()
            ->whereRaw('YEAR('.PegawaiKompetensi::TANGGAL_ACUAN.') = ?', [$tahun])
            ->distinct()
            ->count('nip');

        $agregat = PegawaiKompetensi::query()
            ->whereRaw('YEAR('.PegawaiKompetensi::TANGGAL_ACUAN.') = ?', [$tahun])
            ->selectRaw('COUNT(*) as event, COALESCE(SUM(jumlah_jam), 0) as jam')
            ->first();

        return [
            'total_pegawai' => $totalPegawai,
            'berkompetensi' => $berkompetensi,
            'belum' => max($totalPegawai - $berkompetensi, 0),
            'event' => (int) ($agregat->event ?? 0),
            'jam' => (int) ($agregat->jam ?? 0),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PegawaiKompetensi::query()->rekapJabatan($this->selectedTahun()))
            ->columns([
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('jumlah_pegawai_berkompetensi')
                    ->label('Pegawai Berkompetensi')
                    ->badge()
                    ->color('primary')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('jumlah_event')
                    ->label('Event')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_jam')
                    ->label('Total JP')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state).' JP')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('rata_jam')
                    ->label('Rata JP/Event')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1))
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('jumlah_pegawai_berkompetensi', 'desc')
            // Query agregat (GROUP BY jabatan) — id-tiebreak Filament akan
            // melanggar ONLY_FULL_GROUP_BY; jabatan sudah unik per baris.
            ->defaultKeySort(false)
            ->searchable(false)
            ->emptyStateHeading('Tidak ada data kompetensi pada tahun ini');
    }

    /**
     * Jabatan yang belum punya satu pun pegawai berkompetensi tahun ini —
     * daftar inilah yang paling berguna dari halaman ini.
     *
     * @return Collection<int, string>
     */
    public function jabatanKosong(): Collection
    {
        $terisi = PegawaiKompetensi::rekapJabatan($this->selectedTahun())
            ->get()
            ->pluck('jabatan')
            ->all();

        return PegawaiProfil::query()
            ->whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->distinct()
            ->orderBy('jabatan')
            ->pluck('jabatan')
            ->reject(fn (string $j) => in_array($j, $terisi, true))
            ->values();
    }
}
