<?php

namespace App\Filament\Tool\Pages;

use App\Exports\Kepegawaian\CertificateNumberExport;
use App\Models\Certificate\CertificateNumberActivity;
use App\Models\Certificate\CertificateNumberRow;
use App\Services\Certificate\CertificateExcelParser;
use App\Services\Certificate\CertificateNumberService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Nomor Sertifikat — daftar kegiatan + blok penomoran sertifikatnya.
 *
 * Alur: buat kegiatan (kode, tahun, nomor awal otomatis), lalu isi penerima
 * lewat impor Excel (berkas TIDAK disimpan, hanya hasil parse) atau tambah
 * manual. Nomor berlanjut antar kegiatan pada tahun yang sama. Data ini bisa
 * langsung dipakai sebagai dataset Generator Sertifikat (lihat
 * CertificateGenerator::applyNumberActivity()).
 */
class NomorSertifikat extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'Nomor Sertifikat';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Nomor Sertifikat';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?string $slug = 'nomor-sertifikat';

    protected string $view = 'filament.tool.pages.nomor-sertifikat';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Kelola penomoran sertifikat per kegiatan. Nomor berlanjut pada tahun yang sama dan bisa dipakai langsung sebagai data template sertifikat.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CertificateNumberActivity::query()
                ->visibleTo(Auth::user())
                ->withCount('rows')
                ->with('creator'))
            ->columns([
                TextColumn::make('kegiatan')
                    ->label('Kegiatan')
                    ->wrap()
                    ->limit(90)
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('tempat')
                    ->label('Tempat')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->badge()
                    ->sortable(),
                TextColumn::make('nomor_range')
                    ->label('Rentang Nomor')
                    ->state(fn (CertificateNumberActivity $record): string => $record->nomorRangeLabel()),
                TextColumn::make('rows_count')
                    ->label('Baris')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(fn (): bool => CertificateNumberActivity::userIsAdmin(Auth::user())),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Filter langsung tampil (bukan dropdown) sesuai permintaan.
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => CertificateNumberActivity::query()
                        ->visibleTo(Auth::user())
                        ->distinct()
                        ->orderByDesc('tahun')
                        ->pluck('tahun', 'tahun')
                        ->mapWithKeys(fn ($tahun): array => [(string) $tahun => (string) $tahun])
                        ->all()),
                SelectFilter::make('kode')
                    ->label('Kode')
                    ->options(fn (): array => CertificateNumberActivity::query()
                        ->visibleTo(Auth::user())
                        ->distinct()
                        ->orderBy('kode')
                        ->pluck('kode', 'kode')
                        ->mapWithKeys(fn ($kode): array => [(string) $kode => trim((string) $kode)])
                        ->all()),
                SelectFilter::make('tempat')
                    ->label('Tempat')
                    ->options(fn (): array => CertificateNumberActivity::query()
                        ->visibleTo(Auth::user())
                        ->whereNotNull('tempat')
                        ->distinct()
                        ->orderBy('tempat')
                        ->pluck('tempat', 'tempat')
                        ->mapWithKeys(fn ($tempat): array => [(string) $tempat => (string) $tempat])
                        ->all()),
                Filter::make('tanggal')
                    ->label('Tanggal')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('dari')
                            ->label('Dari tanggal')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        \Filament\Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai tanggal')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(filled($data['dari'] ?? null), fn (Builder $query): Builder => $query->whereDate('tanggal', '>=', $data['dari']))
                            ->when(filled($data['sampai'] ?? null), fn (Builder $query): Builder => $query->whereDate('tanggal', '<=', $data['sampai']));
                    }),
                TernaryFilter::make('punya_baris')
                    ->label('Isi Baris')
                    ->placeholder('Semua kegiatan')
                    ->trueLabel('Sudah ada baris')
                    ->falseLabel('Belum ada baris')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('rows'),
                        false: fn (Builder $query): Builder => $query->doesntHave('rows'),
                    ),
            ])
            ->filtersFormColumns(4)
            ->defaultSort('tanggal', 'desc')
            ->recordActions([
                $this->manageRowsAction(),
                $this->exportActivityAction(),
                $this->editActivityAction(),
                $this->regenerateAction(),
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus kegiatan?')
                    ->modalDescription('Seluruh baris nomor pada kegiatan ini ikut terhapus.')
                    ->successNotificationTitle('Kegiatan dihapus'),
            ])
            ->emptyStateHeading('Belum ada data nomor sertifikat')
            ->emptyStateDescription('Buat kegiatan terlebih dahulu, lalu impor data penerima dari Excel.');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createActivityAction(),
            $this->exportAllAction(),
        ];
    }

    private function createActivityAction(): Action
    {
        return Action::make('createActivity')
            ->label('Kegiatan Baru')
            ->icon('heroicon-o-plus')
            ->modalHeading('Kegiatan Baru')
            ->modalSubmitActionLabel('Simpan Kegiatan')
            ->schema($this->activitySchema())
            ->action(function (array $data): void {
                $activity = CertificateNumberActivity::query()->create([
                    'kegiatan' => trim((string) $data['kegiatan']),
                    'tempat' => filled($data['tempat'] ?? null) ? trim((string) $data['tempat']) : null,
                    'tanggal' => filled($data['tanggal'] ?? null) ? $data['tanggal'] : null,
                    'kode' => filled($data['kode'] ?? null) ? trim((string) $data['kode']) : CertificateNumberService::DEFAULT_KODE,
                    'tahun' => (int) $data['tahun'],
                    'nomor_awal' => (int) $data['nomor_awal'],
                    'nomor_akhir' => null,
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Kegiatan dibuat')
                    ->body('Nomor awal '.$activity->nomor_awal.'. Lanjutkan dengan impor Excel.')
                    ->success()
                    ->send();

                $this->openRows((int) $activity->getKey());
            });
    }

    private function editActivityAction(): Action
    {
        return Action::make('editActivity')
            ->label('Ubah')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->fillForm(fn (CertificateNumberActivity $record): array => [
                'kegiatan' => $record->kegiatan,
                'tempat' => $record->tempat,
                'tanggal' => $record->tanggal?->format('Y-m-d'),
                'kode' => $record->kode,
                'tahun' => $record->tahun,
                'nomor_awal' => $record->nomor_awal,
            ])
            ->schema($this->activitySchema(withSuggest: false))
            ->action(function (CertificateNumberActivity $record, array $data): void {
                $oldKode = (string) $record->kode;
                $oldTahun = (int) $record->tahun;

                $record->update([
                    'kegiatan' => trim((string) $data['kegiatan']),
                    'tempat' => filled($data['tempat'] ?? null) ? trim((string) $data['tempat']) : null,
                    'tanggal' => filled($data['tanggal'] ?? null) ? $data['tanggal'] : null,
                    'kode' => filled($data['kode'] ?? null) ? trim((string) $data['kode']) : CertificateNumberService::DEFAULT_KODE,
                    'tahun' => (int) $data['tahun'],
                    'nomor_awal' => (int) $data['nomor_awal'],
                ]);

                $kodeTahunBerubah = $oldKode !== (string) $record->kode || $oldTahun !== (int) $record->tahun;

                if ($kodeTahunBerubah && $record->rows()->exists()) {
                    Notification::make()
                        ->title('Kegiatan diperbarui')
                        ->body('Kode/tahun berubah — teks nomor surat baris lama belum disesuaikan. Jalankan "Regenerate Nomor" bila perlu.')
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()->title('Kegiatan diperbarui')->success()->send();
            });
    }

    private function regenerateAction(): Action
    {
        return Action::make('regenerate')
            ->label('Regenerate Nomor')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Regenerate teks nomor surat')
            ->modalDescription('Seluruh baris pada kegiatan ini akan memakai kode/tahun terkini. Nomor urut tidak berubah.')
            ->action(function (CertificateNumberActivity $record): void {
                $count = app(CertificateNumberService::class)->regenerateNomorSurat($record);

                Notification::make()
                    ->title('Nomor surat diperbarui')
                    ->body($count.' baris disesuaikan.')
                    ->success()
                    ->send();
            });
    }

    private function exportActivityAction(): Action
    {
        return Action::make('exportActivity')
            ->label('Unduh Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(fn (CertificateNumberActivity $record): BinaryFileResponse => $this->exportActivities([$record]));
    }

    private function exportAllAction(): Action
    {
        return Action::make('exportAll')
            ->label('Unduh Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (): ?BinaryFileResponse {
                $activities = CertificateNumberActivity::query()
                    ->visibleTo(Auth::user())
                    ->ordered()
                    ->get()
                    ->all();

                if ($activities === []) {
                    Notification::make()->title('Belum ada data untuk diunduh.')->danger()->send();

                    return null;
                }

                return $this->exportActivities($activities);
            });
    }

    /**
     * @param  list<CertificateNumberActivity>  $activities
     */
    private function exportActivities(array $activities): BinaryFileResponse
    {
        $title = count($activities) === 1
            ? 'Nomor Sertifikat'
            : 'Nomor Sertifikat Gabungan';

        return Excel::download(
            new CertificateNumberExport($activities, $title),
            'nomor-sertifikat-'.now()->format('Ymd-His').'.xlsx',
            ExcelFormat::XLSX,
        );
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    private function activitySchema(bool $withSuggest = true): array
    {
        return [
            Textarea::make('kegiatan')
                ->label('Kegiatan')
                ->rows(3)
                ->required()
                ->maxLength(2000)
                ->columnSpanFull()
                ->helperText('Tulis nama/judul kegiatan lengkap seperti pada naskah.'),
            TextInput::make('tempat')
                ->label('Tempat')
                ->maxLength(150)
                ->placeholder('Semarang'),
            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->native(false)
                ->displayFormat('d/m/Y'),
            TextInput::make('kode')
                ->label('Kode')
                ->default(CertificateNumberService::DEFAULT_KODE)
                ->maxLength(30)
                ->required()
                ->helperText('Dipakai sebagai awalan nomor surat, mis. "800.2/".'),
            TextInput::make('tahun')
                ->label('Tahun')
                ->numeric()
                ->minValue(2000)
                ->maxValue(2100)
                ->default((int) now()->year)
                ->required()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, $state) use ($withSuggest): void {
                    if (! $withSuggest || blank($state)) {
                        return;
                    }

                    $next = app(CertificateNumberService::class)->nextNumber((int) $state);
                    $set('nomor_awal', $next);
                }),
            TextInput::make('nomor_awal')
                ->label('Nomor Awal')
                ->numeric()
                ->minValue(1)
                ->default(fn (): int => app(CertificateNumberService::class)->nextNumber((int) now()->year))
                ->required()
                ->helperText($withSuggest
                    ? 'Otomatis diisi nomor terakhir tahun tersebut + 1. Ubah manual bila ingin melompati nomor.'
                    : 'Nomor urut baris pertama kegiatan ini.'),
        ];
    }

    // ------------------------------------------------------------------
    // Panel kelola baris
    // ------------------------------------------------------------------

    /** ID kegiatan yang panel barisnya sedang terbuka. */
    public ?int $rowsActivityId = null;

    /** Nama untuk ditambahkan massal — satu nama per baris. */
    public string $bulkNama = '';

    public bool $showExcelDialog = false;

    /** Berkas Excel di dialog impor (tidak pernah dipersist). */
    public mixed $excelUpload = null;

    public string $excelName = '';

    /** @var list<string> */
    public array $excelHeaders = [];

    /** @var list<string> */
    public array $excelKeys = [];

    /** Pratinjau 10 baris pertama (baris penuh tidak disimpan di state Livewire). */
    public array $excelPreview = [];

    public int $excelRowCount = 0;

    public ?string $mapNama = null;

    public ?string $mapKeterangan = null;

    private function manageRowsAction(): Action
    {
        return Action::make('manageRows')
            ->label('Kelola Baris')
            ->icon('heroicon-o-list-bullet')
            ->action(fn (CertificateNumberActivity $record) => $this->openRows((int) $record->getKey()));
    }

    public function openRows(int $activityId): void
    {
        $activity = $this->findActivity($activityId);

        if ($activity === null) {
            return;
        }

        $this->rowsActivityId = (int) $activity->getKey();
        $this->bulkNama = '';
        $this->resetExcelDialog();
    }

    public function closeRows(): void
    {
        $this->rowsActivityId = null;
        $this->resetExcelDialog();
    }

    /**
     * Tempel daftar nama (satu nama per baris) lalu tambahkan semuanya
     * berurutan. Baris kosong dilewati; spasi berlebih dirapikan.
     */
    public function addRowsFromText(): void
    {
        $activity = $this->currentRowsActivity;

        if ($activity === null) {
            return;
        }

        $names = $this->parseNameLines($this->bulkNama);

        if ($names === []) {
            Notification::make()
                ->title('Belum ada nama')
                ->body('Tempel minimal satu nama (satu nama per baris).')
                ->danger()
                ->send();

            return;
        }

        $service = app(CertificateNumberService::class);
        $firstNomor = $this->nextNomorFor($activity);

        foreach ($names as $nama) {
            $service->appendRow($activity, $nama);
        }

        $this->bulkNama = '';
        $this->refreshActivityState();

        $activity->refresh();
        $lastNomor = (int) $activity->nomor_akhir;
        $range = $firstNomor === $lastNomor ? (string) $firstNomor : $firstNomor.'–'.$lastNomor;

        Notification::make()
            ->title(count($names).' nama ditambahkan')
            ->body('Nomor '.$range.'. Total '.$activity->rows()->count().' baris.')
            ->success()
            ->send();
    }

    /**
     * Nomor yang akan dipakai baris berikutnya pada kegiatan ini.
     */
    private function nextNomorFor(CertificateNumberActivity $activity): int
    {
        $last = $activity->rows()->reorder()->orderByDesc('urutan')->first();

        return $last !== null ? (int) $last->nomor + 1 : (int) $activity->nomor_awal;
    }

    /**
     * Pecah teks tempelan menjadi daftar nama: satu baris = satu nama.
     * Nomor urut/penomoran di depan baris (mis. "1. ", "1	", "1) ")
     * dibuang supaya hasil salin dari Excel/Word tetap bersih.
     *
     * @return list<string>
     */
    private function parseNameLines(string $text): array
    {
        $names = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+\s*[.)\-:]\s*/u', '', $line) ?? $line;
            $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
            $line = trim($line, " \t\0\x0B\r\xC2\xA0");

            if ($line === '') {
                continue;
            }

            $names[] = $line;
        }

        return $names;
    }

    public function deleteRow(int $rowId): void
    {
        $activity = $this->currentRowsActivity;

        if ($activity === null) {
            return;
        }

        app(CertificateNumberService::class)->deleteRow($activity, $rowId);
        $this->refreshActivityState();
    }

    /**
     * Kosongkan seluruh baris pada kegiatan yang sedang dibuka (empty data).
     * Baris berikutnya akan mulai lagi dari nomor_awal kegiatan.
     */
    public function emptyRows(): void
    {
        $activity = $this->currentRowsActivity;

        if ($activity === null) {
            return;
        }

        $count = app(CertificateNumberService::class)->clearRows($activity);
        $this->refreshActivityState();

        if ($count === 0) {
            Notification::make()
                ->title('Sudah kosong')
                ->body('Tidak ada baris untuk dihapus.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Data dikosongkan')
            ->body($count.' baris dihapus. Baris berikutnya mulai lagi dari nomor '.$activity->nomor_awal.'.')
            ->success()
            ->send();
    }

    public function getCurrentRowsActivityProperty(): ?CertificateNumberActivity
    {
        return $this->rowsActivityId !== null ? $this->findActivity($this->rowsActivityId) : null;
    }

    /** @return list<CertificateNumberRow> */
    public function getActivityRowsProperty(): array
    {
        return $this->currentRowsActivity?->rows()->get()->all() ?? [];
    }

    private function findActivity(int $activityId): ?CertificateNumberActivity
    {
        $activity = CertificateNumberActivity::query()->find($activityId);

        if ($activity === null || ! $activity->isVisibleTo(Auth::user())) {
            return null;
        }

        return $activity;
    }

    private function refreshActivityState(): void
    {
        $this->dispatch('$refresh');
    }

    // ------------------------------------------------------------------
    // Dialog impor Excel
    // ------------------------------------------------------------------

    public function openExcelDialog(): void
    {
        if ($this->currentRowsActivity === null) {
            return;
        }

        $this->showExcelDialog = true;
    }

    public function closeExcelDialog(): void
    {
        $this->showExcelDialog = false;
        $this->excelUpload = null;
    }

    private function resetExcelDialog(): void
    {
        $this->showExcelDialog = false;
        $this->excelUpload = null;
        $this->excelName = '';
        $this->excelHeaders = [];
        $this->excelKeys = [];
        $this->excelPreview = [];
        $this->excelRowCount = 0;
        $this->mapNama = null;
        $this->mapKeterangan = null;
    }

    /**
     * Berkas hanya dibaca dari path temporer Livewire — tidak disimpan ke
     * disk permanen (tidak ada FileUpload::saveUploadedFileUsing/storeFiles).
     */
    public function updatedExcelUpload(CertificateExcelParser $parser): void
    {
        $upload = $this->firstUploadedFile($this->excelUpload);

        if (! $upload instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $parsed = $parser->parse($upload->getRealPath());
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal membaca Excel')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if ($parsed['headers'] === [] || $parsed['rows'] === []) {
            Notification::make()
                ->title('Data kosong')
                ->body('Pastikan baris pertama berisi nama kolom dan ada data di bawahnya.')
                ->danger()
                ->send();

            return;
        }

        $this->excelHeaders = $parsed['headers'];
        $this->excelKeys = $parsed['keys'];
        $this->excelPreview = array_slice($parsed['rows'], 0, 10);
        $this->excelRowCount = count($parsed['rows']);
        $this->excelName = $upload->getClientOriginalName();

        $this->mapNama = $this->guessKey(['nama', 'name', 'pegawai', 'penerima']);
        $this->mapKeterangan = $this->guessKey(['keterangan', 'ket', 'jabatan', 'catatan']);
    }

    private function guessKey(array $candidates): ?string
    {
        foreach ($this->excelKeys as $index => $key) {
            foreach ($candidates as $candidate) {
                if ($key === $candidate || str_contains($key, $candidate)) {
                    return $key;
                }
            }
        }

        return $this->excelKeys[0] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getExcelColumnOptionsProperty(): array
    {
        $options = [];

        foreach ($this->excelKeys as $index => $key) {
            $options[$key] = $this->excelHeaders[$index] ?? $key;
        }

        return $options;
    }

    public function applyExcelImport(CertificateNumberService $service, CertificateExcelParser $parser): void
    {
        $activity = $this->currentRowsActivity;

        if ($activity === null) {
            return;
        }

        if (blank($this->mapNama)) {
            Notification::make()->title('Pilih kolom untuk Nama.')->danger()->send();

            return;
        }

        // Baris lengkap tidak disimpan di state Livewire (bisa ribuan baris);
        // berkas temporer dibaca ulang di sini, lalu dibuang otomatis oleh
        // Livewire. Tidak ada berkas yang dipersist ke storage permanen.
        $path = $this->uploadedFilePath($this->excelUpload);

        if ($path === null) {
            Notification::make()
                ->title('Berkas Excel sudah tidak tersedia')
                ->body('Pilih ulang berkasnya, lalu terapkan.')
                ->danger()
                ->send();

            return;
        }

        try {
            $parsed = $parser->parse($path);
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal membaca Excel')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $items = $service->itemsFromParsedExcel($parsed, [
            'nama' => $this->mapNama,
            'keterangan' => $this->mapKeterangan,
        ]);

        if ($items === []) {
            Notification::make()
                ->title('Tidak ada baris valid')
                ->body('Kolom Nama yang dipilih kosong pada semua baris.')
                ->danger()
                ->send();

            return;
        }

        $count = $service->replaceRows($activity, $items);

        $this->resetExcelDialog();
        $this->refreshActivityState();

        Notification::make()
            ->title('Impor selesai')
            ->body($count.' baris tersimpan. Berkas Excel tidak disimpan — hanya datanya.')
            ->success()
            ->send();
    }

    /**
     * Path berkas temporer yang masih bisa dibaca, atau null bila sudah hilang.
     */
    private function uploadedFilePath(mixed $value): ?string
    {
        $file = $this->firstUploadedFile($value);

        if ($file instanceof TemporaryUploadedFile) {
            $path = $file->getRealPath();

            return is_string($path) && is_file($path) ? $path : null;
        }

        if (is_string($file) && is_file($file)) {
            return $file;
        }

        return null;
    }

    private function firstUploadedFile(mixed $value): mixed
    {
        if (is_array($value)) {
            return reset($value) ?: null;
        }

        return $value;
    }
}
