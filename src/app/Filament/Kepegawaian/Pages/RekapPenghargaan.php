<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\RekapPenghargaanSheet;
use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource;
use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Rekap penghargaan masa kerja (Satyalancana Karya Satya) berbentuk
 * checklist per tingkat 10/20/30 tahun, plus penanda tingkat mana yang
 * WAJIB diusulkan berikutnya sesuai syarat pengusulan resmi: PNS yang belum
 * pernah menerima SLKS hanya dapat diusulkan secara URUT dari tingkat
 * terendah — tidak boleh lompat (mis. masa kerja 23 tahun tanpa SLKS sama
 * sekali harus diusulkan 10 tahun dulu, bukan langsung 20 tahun).
 *
 * "Dimiliki" dibaca dari tabel pegawai_penghargaan (impor JSON SISDM ATAU
 * ditambah manual di halaman Daftar Penghargaan — lihat
 * PegawaiPenghargaanResource — fitur tambah manual ada persis karena
 * SIMPATIK/SISDM kadang tak lengkap diisi staf, sehingga tanpa baris manual
 * tanda centang di rekap ini bisa salah kosong).
 *
 * Kalau tingkat lebih tinggi tercatat tapi yang lebih rendah tidak (mis.
 * punya 20 tapi 10 kosong), tingkat yang lebih rendah dianggap terpenuhi
 * juga — asumsinya cuma belum diisi di SIMPATIK, bukan urutan pengusulan
 * sungguhan dilanggar (keputusan user). "Perlu Diusulkan" lompat ke tingkat
 * berikutnya di atas yang tertinggi tercatat.
 *
 * Beda dengan halaman "Penghargaan Masa Kerja" (dihapus, digantikan halaman
 * ini): itu cuma bucket dari NIP tanpa cek riwayat SLKS asli; ini menyandingkan
 * masa kerja DENGAN riwayat penghargaan yang benar-benar tercatat.
 *
 * Sumber: PegawaiProfil::rekapPenghargaan() — satu-satunya tempat rumus
 * checklist/urutan ditulis, dipakai halaman ini dan RekapPenghargaanSheet.
 */
class RekapPenghargaan extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Rekap Penghargaan';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Rekap Penghargaan Masa Kerja (Satyalancana Karya Satya)';

    protected string $view = 'filament.kepegawaian.pages.rekap-penghargaan';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new RekapPenghargaanSheet,
                    'rekap-penghargaan-'.now()->format('Ymd-His').'.xlsx',
                    ExcelFormat::XLSX,
                )),
        ];
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        $rows = PegawaiProfil::rekapPenghargaan();

        return [
            'total' => $rows->count(),
            'perlu_diusulkan' => $rows->filter(fn (array $r) => $r['perlu_diusulkan'] !== null)->count(),
            'lengkap' => $rows->filter(fn (array $r) => $r['perlu_diusulkan'] === null)->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // Data terkomputasi (join masa kerja dari NIP + riwayat
            // penghargaan), bukan query Eloquent — pola sama seperti
            // EvaluasiKesesuaianDiklat / PenghargaanMasaKerja sebelumnya.
            ->records(fn (?string $sortColumn, ?string $sortDirection): Collection => match ($sortColumn) {
                'nama' => PegawaiProfil::rekapPenghargaan()->sortBy('nama', SORT_REGULAR, $sortDirection === 'desc')->values(),
                'masa_kerja' => PegawaiProfil::rekapPenghargaan()->sortBy('masa_kerja', SORT_REGULAR, $sortDirection === 'desc')->values(),
                'jumlah_penghargaan' => PegawaiProfil::rekapPenghargaan()->sortBy('jumlah_penghargaan', SORT_REGULAR, $sortDirection === 'desc')->values(),
                default => PegawaiProfil::rekapPenghargaan(),
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
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('golongan')
                    ->label('Gol.')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('masa_kerja')
                    ->label('Masa Kerja')
                    ->formatStateUsing(fn ($state): string => $state.' th')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('masa_kerja_duk')
                    ->label('Masa Kerja DUK')
                    ->state(fn (array $record): ?string => $record['masa_kerja_duk'])
                    ->badge()
                    ->color('warning')
                    ->tooltip('Masa kerja hasil perhitungan resmi dari dokumen DUK terakhir (bukan turunan NIP).')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('has_10')
                    ->label('10 Th')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
                IconColumn::make('has_20')
                    ->label('20 Th')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
                IconColumn::make('has_30')
                    ->label('30 Th')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('jumlah_penghargaan')
                    ->label('Jumlah Penghargaan')
                    ->badge()
                    ->color('info')
                    ->tooltip('Total seluruh penghargaan tercatat (bukan cuma SLKS — semua jenis dihitung).')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('drh_terakhir')
                    ->label('Pengusulan Terakhir')
                    ->state(fn (array $record): ?string => $record['drh_terakhir_status'] === null
                        ? null
                        : ($record['drh_terakhir_tahun'] ?? '?').' — '.(DrhSatyaLancana::STATUSES[$record['drh_terakhir_status']] ?? $record['drh_terakhir_status']))
                    ->badge()
                    ->color(fn (array $record): string => $record['drh_terakhir_status'] === null
                        ? 'gray'
                        : (DrhSatyaLancana::STATUS_COLORS[$record['drh_terakhir_status']] ?? 'gray'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('perlu_diusulkan')
                    ->label('Perlu Diusulkan')
                    ->badge()
                    ->state(fn (array $record): string => $record['perlu_diusulkan'] !== null
                        ? $record['perlu_diusulkan'].' Tahun'
                        : 'Lengkap')
                    ->color(fn (array $record): string => $record['perlu_diusulkan'] !== null ? 'warning' : 'success'),
                TextColumn::make('bisa_diusulkan')
                    ->label('Bisa Diusulkan')
                    ->badge()
                    ->state(fn (array $record): string => $record['alasan_bisa_diusulkan'])
                    ->color(fn (array $record): string => $record['bisa_diusulkan']
                        ? 'success'
                        : ($record['perlu_diusulkan'] !== null ? 'danger' : 'gray')),
            ])
            ->recordActions([
                Action::make('buatDrh')
                    ->label('Buat DRH')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->url(fn (array $record): string => DrhSatyaLancanaResource::getUrl('create', ['nip' => $record['nip']]))
                    ->openUrlInNewTab(),
            ])
            ->recordUrl(fn (array $record): string => PegawaiProfilResource::getUrl('view', ['record' => $record['nip']]))
            ->emptyStateHeading('Tidak ada PNS aktif dengan masa kerja ≥ 10 tahun');
    }
}
