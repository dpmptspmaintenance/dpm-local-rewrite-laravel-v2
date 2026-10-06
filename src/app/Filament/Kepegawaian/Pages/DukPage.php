<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Exports\Kepegawaian\DukSheet;
use App\Models\Kepegawaian\Duk;
use App\Models\Kepegawaian\DukImpor;
use App\Services\Kepegawaian\DukImportService;
use App\Services\Kepegawaian\ExcelToPdfService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * DAFTAR URUT KEPANGKATAN (DUK) — list + impor PDF + ekspor.
 *
 * Beda dari halaman penghargaan: masa kerja di DUK adalah hasil perhitungan
 * resmi kepegawaian yang dibawa dokumen, BUKAN turunan NIP — makanya DUK
 * tersimpan sendiri (tabel `duk`), bukan dihitung ulang.
 *
 * Data disimpan PER UPLOAD (riwayat): tiap impor PDF jadi batch `duk_impor`
 * baru, batch lama tetap ada. Dropdown "Periode Upload" memilih batch mana
 * yang ditampilkan (default batch terbaru).
 */
class DukPage extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'DUK';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Daftar Urut Kepangkatan (DUK)';

    protected static ?string $slug = 'duk';

    protected string $view = 'filament.kepegawaian.pages.duk';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['duk_impor_id' => $this->latestBatchId()]);
    }

    /** ID batch DUK terbaru (null bila belum ada impor). */
    protected function latestBatchId(): ?int
    {
        return DukImpor::query()->orderByDesc('diimpor_pada')->orderByDesc('id')->value('id');
    }

    /** Batch yang sedang dipilih; fallback ke terbaru. */
    protected function selectedBatchId(): ?int
    {
        return $this->data['duk_impor_id'] ?? $this->latestBatchId();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('duk_impor_id')
                            ->label('Periode Upload')
                            ->options(fn (): array => DukImpor::query()
                                ->orderByDesc('diimpor_pada')
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn (DukImpor $b): array => [$b->id => $b->label()])
                                ->all())
                            ->placeholder('— Pilih upload —')
                            ->native(false)
                            ->live()
                            ->visible(fn (): bool => DukImpor::query()->exists()),
                    ]),
            ])
            ->statePath('data');
    }

    public function getSubheading(): ?string
    {
        $batch = $this->selectedBatchId()
            ? DukImpor::query()->find($this->selectedBatchId())
            : null;

        if (! $batch) {
            return 'Belum ada data. Klik "Impor DUK (PDF)" untuk memuat dari dokumen DUK.';
        }

        return ($batch->opd ?: 'OPD tidak disebut')
            .($batch->periode ? ' — periode '.$batch->periode : '')
            .' — diimpor '.$batch->diimpor_pada?->format('d M Y H:i')
            .($batch->diimpor_oleh_nama ? ' oleh '.$batch->diimpor_oleh_nama : '')
            .'. Total '.$batch->jumlah_baris.' baris.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imporDuk')
                ->label('Impor DUK (PDF)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Impor DUK dari PDF')
                ->modalDescription('Unggah PDF DAFTAR URUT KEPANGKATAN. Tiap impor disimpan sebagai data upload baru (riwayat tetap ada). Masa kerja diambil apa adanya dari dokumen.')
                ->modalSubmitActionLabel('Impor')
                ->schema([
                    FileUpload::make('duk_file')
                        ->label('Berkas PDF DUK')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->required()
                        ->storeFiles(false),
                ])
                ->action(function (array $data): void {
                    $this->prosesImpor($data['duk_file'] ?? null);
                }),

            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->disabled(fn (): bool => $this->selectedBatchId() === null)
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new DukSheet($this->selectedBatchId()),
                    'duk-'.now()->format('Ymd-His').'.xlsx',
                    ExcelFormat::XLSX,
                )),

            Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->disabled(fn (): bool => $this->selectedBatchId() === null)
                ->action(function (): BinaryFileResponse {
                    $batchId = $this->selectedBatchId();

                    $xlsxPath = tempnam(sys_get_temp_dir(), 'duk').'.xlsx';
                    file_put_contents($xlsxPath, Excel::raw(new DukSheet($batchId), ExcelFormat::XLSX));

                    try {
                        $pdfBytes = app(ExcelToPdfService::class)->toPdf($xlsxPath, ['DUK']);
                    } finally {
                        @unlink($xlsxPath);
                    }

                    $pdfPath = tempnam(sys_get_temp_dir(), 'duk').'.pdf';
                    file_put_contents($pdfPath, $pdfBytes);

                    return response()
                        ->download($pdfPath, 'duk-'.now()->format('Ymd-His').'.pdf', ['Content-Type' => 'application/pdf'])
                        ->deleteFileAfterSend(true);
                }),
        ];
    }

    private function prosesImpor(mixed $upload): void
    {
        if (! $upload) {
            Notification::make()->title('Pilih berkas terlebih dahulu.')->danger()->send();

            return;
        }

        try {
            $result = app(DukImportService::class)->import(
                $upload->getRealPath(),
                $upload->getClientOriginalName(),
                Auth::user()?->nama ?? Auth::user()?->name,
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Impor gagal')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        // Pindah tampilan ke batch yang baru diimpor.
        $this->data['duk_impor_id'] = $result['batch_id'];
        $this->form->fill(['duk_impor_id' => $result['batch_id']]);

        Notification::make()
            ->title('Impor selesai')
            ->body("{$result['count']} baris dimuat dari PDF"
                .($result['opd'] ? " — {$result['opd']}" : '')
                .'. Tersimpan sebagai data upload baru.')
            ->success()
            ->persistent()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Duk::query()->where('duk_impor_id', $this->selectedBatchId() ?? 0))
            ->defaultSort('urutan_duk')
            ->columns([
                TextColumn::make('urutan_duk')->label('No')->alignEnd()->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->description(fn (Duk $record): ?string => $record->nip)
                    ->searchable(['nama', 'nip'])
                    ->sortable()
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('gol')->label('Gol.')->badge()->color('gray')->placeholder('—')->toggleable(),
                TextColumn::make('tmt')->label('TMT Gol.')->placeholder('—')->toggleable(),
                TextColumn::make('gol_cpns')->label('Gol. CPNS')->placeholder('—')->toggleable(),
                TextColumn::make('tmt_cpns')->label('TMT CPNS')->placeholder('—')->toggleable(),
                TextColumn::make('jabatan')->label('Jabatan')->wrap()->placeholder('—'),
                TextColumn::make('eselon')->label('Eselon')->badge()->color('info')->placeholder('—')->toggleable(),
                TextColumn::make('masa_kerja')
                    ->label('Masa Kerja')
                    ->state(fn (Duk $record): string => $record->masaKerjaTeks())
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
                TextColumn::make('pendidikan')->label('Pendidikan')->wrap()->placeholder('—')->toggleable(),
            ])
            ->emptyStateHeading('Belum ada data DUK')
            ->emptyStateDescription('Klik "Impor DUK (PDF)" untuk memuat dari dokumen DUK.');
    }
}
