<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\EvaluasiKesesuaianExport;
use App\Exports\Kepegawaian\EvaluasiKesesuaianSheet;
use App\Filament\Kepegawaian\Concerns\SelectsKompetensiTahun;
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
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Evaluasi berkala kesesuaian jumlah & jenis diklat per pegawai, untuk satu
 * tahun terpilih.
 *
 * "Jumlah" = realisasi JP dibanding jatah per status_pegawai
 * (PegawaiKompetensi::JATAH_JP — PNS minimal 20 JP/tahun per PP 11/2017 jo.
 * PP 17/2020; PPPK/PPPK paruh waktu hak s.d. 24 JP/tahun per Perka LAN
 * No. 15/2020).
 *
 * "Jenis" ada dua kolom terpisah (tidak digabung — sengaja, biar tak
 * menyesatkan): "Keragaman Jenis" (COUNT DISTINCT jenis — sekadar variasi
 * FORMAT diklat, Seminar/Workshop/dst; bukan ukuran kecocokan topik) dan
 * "Sesuai Jabatan" (judul-judul kompetensi tahun itu yang ditandai manual
 * "Sesuai" — lihat kolom sesuai_jabatan di daftar Kompetensi/Riwayat
 * Kompetensi pegawai; deskripsi kecil "N dari M" di bawah judul memberi
 * konteks rasio). Kolom kedua inilah yang benar-benar mengukur kecocokan ke
 * jabatan; `jenis` di pegawai_kompetensi cuma format penyampaian, tak ada
 * kolom yang cocok otomatis ke jabatan.
 *
 * Sumber: PegawaiKompetensi::evaluasiKesesuaian() (satu-satunya tempat
 * hitungan jatah/realisasi/persen/sesuai-jabatan ditulis, dipakai halaman
 * ini dan EvaluasiKesesuaianSheet/Export, jadi tak bisa beda angka).
 */
class EvaluasiKesesuaianDiklat extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;
    use SelectsKompetensiTahun;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Evaluasi Kesesuaian Diklat';

    protected static string | \UnitEnum | null $navigationGroup = 'Kompetensi';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Evaluasi Berkala Kesesuaian Jumlah & Jenis Diklat';

    protected string $view = 'filament.kepegawaian.pages.evaluasi-kesesuaian-diklat';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportEvaluasi(ExcelFormat::XLSX, 'xlsx')),
            Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->exportEvaluasi(ExcelFormat::DOMPDF, 'pdf')),
        ];
    }

    /**
     * Excel: 2 sheet — evaluasi per pegawai (sumbernya evaluasiKesesuaian()
     * yang sama dipakai tabel di layar) dan daftar mentah semua kompetensi
     * tahun itu tanpa filter jenis (sheet 2, sama seperti di Matriks
     * Kebutuhan Diklat — KompetensiPerTahunSheet dipakai bersama).
     *
     * PDF: evaluasi saja (1 sheet). DOMPDF merender 200-an baris rincian
     * mentah sampai menghabiskan memory_limit (sudah dicoba di halaman
     * Matriks, fatal error) — dan PDF sepanjang itu juga tak enak
     * dibaca/dicetak, jadi PDF tetap ringkasan.
     */
    protected function exportEvaluasi(string $writerType, string $extension): BinaryFileResponse
    {
        $tahun = $this->selectedTahun();
        $filename = "evaluasi-kesesuaian-diklat-{$tahun}-".now()->format('Ymd-His').".{$extension}";

        $export = $writerType === ExcelFormat::DOMPDF
            ? new EvaluasiKesesuaianSheet($tahun)
            : new EvaluasiKesesuaianExport($tahun);

        return Excel::download($export, $filename, $writerType);
    }

    /** @return array<string, int|float> */
    public function getStats(): array
    {
        $rows = PegawaiKompetensi::evaluasiKesesuaian($this->selectedTahun());

        $total = $rows->count();
        $terpenuhi = $rows->filter(fn (array $r) => $r['terpenuhi'])->count();

        return [
            'total' => $total,
            'terpenuhi' => $terpenuhi,
            'kurang' => $total - $terpenuhi,
            'rata_persen' => $total > 0 ? (int) round($rows->avg('persen')) : 0,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // Data terkomputasi per pegawai (jatah/realisasi/persen), bukan
            // query Eloquent — filter DAN sort harus ditangani manual di
            // dalam closure ini via $filters/$sortColumn/$sortDirection yang
            // di-inject Filament. Untuk data source non-query,
            // applyFiltersToTableQuery() (Builder-only) dan ->defaultSort()
            // bawaan Filament sama sekali tak dipanggil — pelajaran dari
            // CutiTahunanRekap, kini juga berlaku utk filter, bukan cuma sort.
            ->records(function (?array $filters, ?string $sortColumn, ?string $sortDirection): Collection {
                $rows = PegawaiKompetensi::evaluasiKesesuaian($this->selectedTahun());

                if ($status = $filters['status_pegawai']['value'] ?? null) {
                    $rows = $rows->where('status_pegawai', $status);
                }

                if (filled($kesesuaian = $filters['kesesuaian']['value'] ?? null)) {
                    $rows = $rows->where('terpenuhi', $kesesuaian === '1');
                }

                $desc = $sortDirection === 'desc';

                return (match ($sortColumn) {
                    'realisasi_jp' => $rows->sortBy(fn (array $r) => $r['realisasi_jp'], SORT_REGULAR, $desc),
                    'jumlah_jenis' => $rows->sortBy(fn (array $r) => $r['jumlah_jenis'], SORT_REGULAR, $desc),
                    'jumlah_sesuai_jabatan' => $rows->sortBy(fn (array $r) => $r['jumlah_sesuai_jabatan'], SORT_REGULAR, $desc),
                    'nama' => $rows->sortBy(fn (array $r) => $r['nama'], SORT_REGULAR, $desc),
                    // Bawaan: paling kurang duluan — paling actionable buat HR.
                    default => $rows->sortBy(fn (array $r) => $r['persen'], SORT_REGULAR, $desc),
                })->values();
            })
            ->columns([
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn (array $record): ?string => $record['nip'])
                    ->wrap()
                    ->weight('medium')
                    ->sortable(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('status_pegawai')
                    ->label('Status')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'PEGAWAI NEGERI SIPIL' => 'PNS',
                        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA' => 'PPPK',
                        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA PARUH WAKTU' => 'PPPK Paruh Waktu',
                        default => $state,
                    }),
                TextColumn::make('jatah_jp')
                    ->label('Jatah JP')
                    ->formatStateUsing(fn ($state): string => $state.' JP')
                    ->alignEnd(),
                TextColumn::make('realisasi_jp')
                    ->label('Realisasi JP')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => $state.' JP')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('persen')
                    ->label('Capaian')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (int) $state >= 100 => 'success',
                        (int) $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state): string => $state.'%')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('jumlah_jenis')
                    ->label('Keragaman Jenis')
                    ->description(fn (array $record): string => $record['jumlah_event'].' event')
                    ->formatStateUsing(fn ($state): string => $state.' jenis')
                    ->tooltip('Jumlah format diklat berbeda (Seminar/Workshop/dst) — bukan ukuran kecocokan topik ke jabatan.')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('jumlah_sesuai_jabatan')
                    ->label('Sesuai Jabatan')
                    // ->state(), bukan ->formatStateUsing(): daftar judul bisa
                    // kosong ([]), dan array kosong itu "blank" di Laravel —
                    // TextColumn melewati formatStateUsing sepenuhnya bila
                    // state mentah blank (pelajaran dari kolom Penjumlahan di
                    // CutiTahunanRekap). ->state() mengisi tampilan langsung
                    // jadi tak pernah kena masalah itu.
                    ->state(fn (array $record): string => match (true) {
                        $record['jumlah_event'] === 0 => '—',
                        $record['judul_sesuai_jabatan'] === [] => 'Tidak ada yang ditandai sesuai',
                        default => implode(', ', $record['judul_sesuai_jabatan']),
                    })
                    ->description(fn (array $record): ?string => $record['jumlah_event'] === 0
                        ? null
                        : "{$record['jumlah_sesuai_jabatan']} dari {$record['jumlah_event']} kompetensi")
                    ->wrap()
                    ->color(function (array $record): string {
                        $total = $record['jumlah_event'];
                        $sesuai = $record['jumlah_sesuai_jabatan'];

                        return match (true) {
                            $total === 0 => 'gray',
                            $sesuai === $total => 'success',
                            $sesuai === 0 => 'danger',
                            default => 'warning',
                        };
                    })
                    ->tooltip('Judul kompetensi tahun ini yang ditandai manual "Sesuai Jabatan" (lihat daftar Kompetensi / Riwayat Kompetensi pegawai). Default Sesuai sampai ditinjau.')
                    ->sortable(),
                TextColumn::make('terpenuhi')
                    ->label('Kesesuaian')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Terpenuhi' : 'Kurang')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            // ->query() SENGAJA tidak diisi di kedua filter — untuk data
            // source non-query (->records()), Filament tak pernah memanggil
            // query filter (lihat komentar di ->records() di atas). Filtering
            // sungguhan ada di closure ->records(), yang membaca $filters ini
            // via nama filter yang sama (status_pegawai / kesesuaian).
            ->filters([
                SelectFilter::make('status_pegawai')
                    ->label('Status Pegawai')
                    ->options([
                        'PEGAWAI NEGERI SIPIL' => 'PNS',
                        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA' => 'PPPK',
                        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA PARUH WAKTU' => 'PPPK Paruh Waktu',
                    ]),
                SelectFilter::make('kesesuaian')
                    ->label('Kesesuaian')
                    ->options(['1' => 'Terpenuhi', '0' => 'Kurang']),
            ])
            ->recordUrl(fn (array $record): string => PegawaiProfilResource::getUrl('view', ['record' => $record['nip']]))
            ->emptyStateHeading('Tidak ada data pegawai aktif');
    }
}
