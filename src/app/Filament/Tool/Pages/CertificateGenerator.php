<?php

namespace App\Filament\Tool\Pages;

use App\Models\Certificate\CertificateDataset;
use App\Models\Certificate\CertificateDatasetRow;
use App\Models\Certificate\CertificateField;
use App\Models\Certificate\CertificateFont;
use App\Models\Certificate\CertificateGeneration;
use App\Models\Certificate\CertificateNumberActivity;
use App\Models\Certificate\CertificateTemplate;
use App\Services\Certificate\CertificateExcelParser;
use App\Services\Certificate\CertificateNamingService;
use App\Services\Certificate\CertificateNumberService;
use App\Services\Certificate\CertificateRenderer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Generator Sertifikat — satu halaman Livewire untuk seluruh alur:
 * unggah gambar template, unggah Excel (header bebas sesuai isi berkas),
 * tambah/geser/ubah ukuran field di kanvas (posisi mm absolut), pratinjau
 * per baris, simpan template + dataset, lalu unduh satuan atau batch (ZIP).
 *
 * Posisi field disimpan milimeter — kanvas hanya menampilkan skala (zoom),
 * sehingga pratinjau sama persis dengan PDF hasil Dompdf.
 */
class CertificateGenerator extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Generator Sertifikat';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Generator Sertifikat';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?string $slug = 'generator-sertifikat';

    protected string $view = 'filament.tool.pages.certificate-generator';

    /**
     * Halaman editor hanya dibuka lewat tombol "Template Baru"/"Buka" di
     * daftar Template Sertifikat — tidak ditampilkan sebagai menu sendiri.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];

    /** ID template yang dibuka (null = template baru yang belum disimpan). */
    public ?int $templateId = null;

    /** Query string ?template= — dipakai tombol "Buka" di daftar template. */
    #[\Livewire\Attributes\Url(as: 'template')]
    public ?int $openTemplate = null;

    /** Path background relatif disk privat untuk pratinjau kanvas. */
    public ?string $backgroundUrl = null;

    public int $bgWidthPx = 0;
    public int $bgHeightPx = 0;
    public float $pageWidthMm = 297.0;
    public float $pageHeightMm = 210.0;

    /** @var list<array<string, mixed>> field posisi mm */
    public array $fields = [];

    public ?int $selectedFieldIndex = null;

    /** @var list<string> label kolom dataset aktif (judul di chip/Excel) */
    public array $headers = [];

    /** @var list<string> key kolom dataset aktif (dipakai placeholder <<key>>) */
    public array $keys = [];

    /** @var list<array<string, string>> */
    public array $rows = [];

    public ?int $datasetPreviewIndex = 0;

    /** Nomor data yang diketik user di kotak "lompat ke data" (string). */
    public string $previewGoto = '1';

    /** @var array<string, string> nilai baris pratinjau (key => value) */
    public array $previewRow = [];

    /** Tampilkan isi data asli di kanvas, atau hanya penanda {key}. */
    public bool $showPreview = true;

    public bool $datasetLoaded = false;

    /** Template lama yang sudah punya dataset tersimpan di DB. */
    public ?int $savedDatasetId = null;

    // ------------------------------------------------------------------
    // Dialog Data Excel (terpisah dari form template)
    // ------------------------------------------------------------------

    public bool $showExcelDialog = false;

    /** Berkas Excel yang sedang diunggah di dalam dialog. */
    public mixed $excelUpload = null;

    /** @var list<string> label kolom hasil parse di dialog */
    public array $pendingExcelHeaders = [];

    /** @var list<string> key kolom hasil parse di dialog */
    public array $pendingExcelKeys = [];

    /** @var list<array<string, string>> semua baris hasil parse di dialog */
    public array $pendingExcelRows = [];

    public ?string $pendingExcelName = null;

    /** Penanda dataset berubah dan perlu ditulis ke DB saat simpan. */
    public bool $datasetDirty = false;

    /** Dialog "Ambil Data dari Nomor Surat". */
    public bool $showNumbersDialog = false;

    /**
     * Kegiatan tersimpan (menu Nomor Sertifikat) yang ditampilkan sebagai
     * tabel pilihan di dialog.
     *
     * @var list<array{id: int, kegiatan: string, tempat: string, tanggal: string, kode: string, tahun: int, range: string, jumlah: int}>
     */
    public array $numberActivityOptions = [];

    public ?int $numberActivityId = null;

    /** Kata kunci pencarian kegiatan di dialog Nomor Surat. */
    public string $numberSearch = '';

    /** @var list<array{family: string, label: string, generic: string}> font aktif untuk dropdown */
    public array $fontOptions = [];

    /** @var array<string, string> css_family => generic fallback (serif/sans-serif/monospace) */
    public array $fontGenerics = [];

    /** CSS @font-face untuk kanvas preview (font non-sistem). */
    public string $fontFaceCss = '';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function mount(): void
    {
        $this->form->fill();
        $this->loadFonts();

        // Parameter ?template= dibaca lewat properti #[Url] ($openTemplate),
        // bukan argumen mount(): Livewire 4 hanya mengisi argumen mount dari
        // parameter ROUTE, bukan query string.
        //
        // $openTemplate TIDAK dikosongkan di sini: properti #[Url] ikut
        // menulis ulang query string saat dehydrate, jadi mengosongkannya
        // menghapus ?template= dari URL — refresh berikutnya lalu memuat
        // halaman sebagai template baru (semua state hilang). Nilainya
        // dibiarkan; $templateId yang menjadi penanda mode edit.
        $templateId = $this->openTemplate;

        /** @var CertificateTemplate|null $existing */
        $existing = $templateId ? CertificateTemplate::query()->find($templateId) : null;

        if ($existing !== null && ! $existing->isVisibleTo(Auth::user())) {
            abort(403);
        }

        if ($existing === null) {
            return;
        }

        $this->loadTemplate($existing);
        $this->appendMissingFieldFonts();
    }

    /**
     * Ambil master font aktif yang benar-benar bisa dirender: dipakai untuk
     * dropdown properti field dan @font-face kanvas preview.
     */
    public function loadFonts(): void
    {
        $fonts = CertificateFont::usable();

        $this->fontGenerics = $fonts
            ->mapWithKeys(fn (CertificateFont $font): array => [
                (string) $font->css_family => match ($font->category) {
                    'serif' => 'serif',
                    'mono' => 'monospace',
                    default => 'sans-serif',
                },
            ])
            ->all();

        $this->fontOptions = $fonts
            ->map(fn (CertificateFont $font): array => [
                'family' => (string) $font->css_family,
                'label' => (string) $font->name,
                'generic' => $this->fontGenerics[(string) $font->css_family] ?? 'sans-serif',
            ])
            ->values()
            ->all();

        $this->fontFaceCss = $fonts
            ->filter(fn (CertificateFont $font): bool => $font->isEmbedded())
            ->map(function (CertificateFont $font): string {
                $family = str_replace(['"', "'"], '', $font->safeFamily());

                if ($family === '') {
                    return '';
                }

                $css = '';

                $regular = $font->absolutePath('regular');

                foreach ($font->variants() as $key => $variant) {
                    if ($font->absolutePath($key) === null && $regular === null) {
                        continue;
                    }

                    $css .= '@font-face{font-family:\''.$family.'\';font-style:'.$variant['style']
                        .';font-weight:'.$variant['weight'].';src:url(\''
                        .route('tool.certificate.font', [
                            'font' => $font->getKey(),
                            'variant' => $font->absolutePath($key) !== null ? $key : 'regular',
                        ])
                        .'\') format(\'truetype\');}';
                }

                return $css;
            })
            ->implode('');
    }

    /**
     * Template lama bisa memakai font yang sudah dinonaktifkan/dihapus dari
     * master. Nilainya tetap ditampilkan di dropdown (ditandai) supaya tidak
     * berubah diam-diam jadi DejaVu Sans saat halaman disimpan ulang.
     */
    private function appendMissingFieldFonts(): void
    {
        $known = array_column($this->fontOptions, 'family');

        foreach ($this->fields as $field) {
            $family = trim((string) ($field['font_family'] ?? ''));

            if ($family === '' || in_array($family, $known, true)) {
                continue;
            }

            $known[] = $family;
            $this->fontOptions[] = ['family' => $family, 'label' => $family.' (tidak aktif)'];
        }
    }

    public function getTitle(): string
    {
        return $this->templateId !== null ? 'Editor Sertifikat' : 'Generator Sertifikat';
    }

    public function getSubheading(): ?string
    {
        return 'Unggah gambar template, isi Data Excel lewat tombolnya, lalu tarik field ke posisi yang diinginkan.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('1. Template & Data')
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama Template')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('file_name_pattern')
                            ->label('Nama Berkas Hasil')
                            ->helperText('Gunakan placeholder <<key>>, mis. "sertifikat anti korupsi <<nama>>". Placeholder tersedia setelah Excel diunggah.')
                            ->default('sertifikat <<nama>>')
                            ->required()
                            ->maxLength(200),
                        FileUpload::make('background')
                            ->label('Gambar Template (JPG/PNG)')
                            ->helperText('Rasio gambar menentukan ukuran halaman PDF. Disarankan rasio A4 (297×210 landscape) atau A4 portrait (210×297).')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->disk('local')
                            ->directory('certificate/templates')
                            ->storeFiles(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    // ------------------------------------------------------------------
    // Muat template lama
    // ------------------------------------------------------------------

    private function loadTemplate(CertificateTemplate $template): void
    {
        $this->templateId = $template->id;
        $this->bgWidthPx = $template->bg_width_px;
        $this->bgHeightPx = $template->bg_height_px;
        $this->pageWidthMm = $template->page_width_mm;
        $this->pageHeightMm = $template->page_height_mm;
        $this->backgroundUrl = $this->backgroundUrlFor($template->background_path);

        $this->form->fill([
            'title' => $template->title,
            'file_name_pattern' => $template->file_name_pattern,
        ]);

        $this->fields = $template->fields->map(fn (CertificateField $field): array => $this->fieldToArray($field))->all();

        $dataset = $template->latestDataset;

        if ($dataset !== null) {
            $this->savedDatasetId = $dataset->id;

            $rows = $this->rowsFromDataset($dataset);
            // Key asli tersimpan sebagai key array data baris; kalau dataset
            // kosong, turunkan ulang dari label header seperti parser.
            $keys = $rows !== []
                ? array_keys($rows[0])
                : array_map(fn (string $header): string => Str::slug($header, '_'), $dataset->headers ?? []);

            $this->applyDataset($dataset->headers ?? [], $keys, $rows, $dataset->row_count);
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function rowsFromDataset(CertificateDataset $dataset): array
    {
        return $dataset->rows->map(fn (CertificateDatasetRow $row): array => (array) $row->data)->all();
    }

    /**
     * @param  list<string>  $headers  label kolom
     * @param  list<string>  $keys  key kolom (dipakai di data baris)
     * @param  list<array<string, string>>  $rows
     */
    private function applyDataset(array $headers, array $keys, array $rows, ?int $rowCount = null): void
    {
        $this->headers = array_values($headers);
        $this->keys = array_values($keys);
        $this->rows = array_values($rows);
        $this->datasetLoaded = true;
        $this->datasetPreviewIndex = $this->rows === [] ? null : 0;
        $this->previewRow = $this->rows[0] ?? [];
        $this->previewGoto = '1';

        $this->normalizeFieldKeysToDataset();

        if ($rowCount !== null && $rowCount !== count($this->rows)) {
            // Baris kosong dibuang saat impor; gunakan jumlah baris nyata.
        }
    }

    /**
     * Field lama bisa tersimpan dengan key = label header (mis. "Nama")
     * padahal data baris memakai key slug ("nama"). Petakan ulang supaya
     * isi field tetap tampil di pratinjau/PDF tanpa perlu dibuat ulang.
     */
    private function normalizeFieldKeysToDataset(): void
    {
        if ($this->keys === []) {
            return;
        }

        foreach ($this->fields as $index => $field) {
            $key = (string) ($field['key'] ?? '');

            if ($key === '' || in_array($key, $this->keys, true)) {
                continue;
            }

            $labelIndex = array_search($key, $this->headers, true);

            if ($labelIndex !== false && isset($this->keys[$labelIndex])) {
                $this->fields[$index]['key'] = $this->keys[$labelIndex];

                continue;
            }

            $slug = Str::slug($key, '_');

            if (in_array($slug, $this->keys, true)) {
                $this->fields[$index]['key'] = $slug;
            }
        }
    }

    private function fieldToArray(CertificateField $field): array
    {
        return [
            'id' => $field->id,
            'key' => $field->key,
            'label' => $field->label,
            'x_mm' => (float) $field->x_mm,
            'y_mm' => (float) $field->y_mm,
            'w_mm' => (float) $field->w_mm,
            'h_mm' => (float) $field->h_mm,
            'font_family' => $field->font_family,
            'font_size_pt' => (float) $field->font_size_pt,
            'color' => $field->color,
            'bold' => (bool) $field->bold,
            'italic' => (bool) $field->italic,
            'align' => $field->align,
            'valign' => $field->valign,
            'sort_order' => (int) $field->sort_order,
        ];
    }

    // ------------------------------------------------------------------
    // Aksi: muat Excel
    // ------------------------------------------------------------------

    public function openExcelDialog(): void
    {
        // Saat edit template, dialog langsung menampilkan data yang sudah
        // tersimpan (bukan kosong) supaya user bisa memeriksa semuanya.
        if ($this->pendingExcelRows === [] && $this->rows !== []) {
            $this->pendingExcelHeaders = $this->headers;
            $this->pendingExcelKeys = $this->keys;
            $this->pendingExcelRows = $this->rows;
        }

        $this->excelUpload = null;
        $this->showExcelDialog = true;
    }

    public function closeExcelDialog(): void
    {
        $this->showExcelDialog = false;
        $this->excelUpload = null;
    }

    /**
     * File input dialog selesai diunggah Livewire → langsung parse dan
     * tampilkan SEMUA baris di tabel pratinjau dalam dialog.
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

        $this->pendingExcelHeaders = $parsed['headers'];
        $this->pendingExcelKeys = $parsed['keys'];
        $this->pendingExcelRows = $parsed['rows'];
        $this->pendingExcelName = $upload->getClientOriginalName();
    }

    /**
     * Pakai data hasil parse di dialog untuk template aktif.
     */
    public function applyExcelDialog(): void
    {
        if ($this->pendingExcelRows === []) {
            Notification::make()
                ->title('Belum ada data Excel')
                ->body('Pilih berkas Excel terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        $this->applyDataset(
            $this->pendingExcelHeaders,
            $this->pendingExcelKeys,
            $this->pendingExcelRows,
        );

        $this->datasetDirty = true;
        $this->savedDatasetId = null;

        // Simpan senyap bila template sudah ada, supaya data tetap tampil
        // saat halaman dibuka lagi (edit template).
        $this->persistDatasetSilently();

        $this->closeExcelDialog();

        Notification::make()
            ->title('Data Excel dipakai')
            ->body(count($this->rows).' baris, '.count($this->headers).' kolom.')
            ->success()
            ->send();
    }

    // ------------------------------------------------------------------
    // Aksi: ambil dataset dari nomor sertifikat
    // ------------------------------------------------------------------

    public function openNumbersDialog(): void
    {
        $this->numberSearch = '';
        $this->loadNumberActivityOptions();

        $this->numberActivityId = $this->numberActivityOptions[0]['id'] ?? null;
        $this->showNumbersDialog = true;
    }

    public function updatedNumberSearch(): void
    {
        $this->loadNumberActivityOptions();

        // Jangan biarkan pilihan menunjuk kegiatan yang tidak lagi tampil;
        // bila belum ada pilihan, pilih hasil pertama supaya "Gunakan Data"
        // langsung bisa dipakai.
        $ids = array_column($this->numberActivityOptions, 'id');

        if ($this->numberActivityId === null || ! in_array($this->numberActivityId, $ids, true)) {
            $this->numberActivityId = $ids[0] ?? null;
        }
    }

    /**
     * Muat kegiatan (dengan filter pencarian) untuk tabel di dialog.
     */
    private function loadNumberActivityOptions(): void
    {
        $search = trim($this->numberSearch);

        $this->numberActivityOptions = CertificateNumberActivity::query()
            ->visibleTo(Auth::user())
            ->withCount('rows')
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query->where('kegiatan', 'like', $like)
                        ->orWhere('tempat', 'like', $like)
                        ->orWhere('kode', 'like', $like)
                        ->orWhere('tahun', 'like', $like)
                        // Cari lewat nama penerima / nomor surat juga.
                        ->orWhereHas('rows', function ($query) use ($like): void {
                            $query->where('nama', 'like', $like)
                                ->orWhere('nomor_surat', 'like', $like);
                        });
                });
            })
            ->ordered()
            ->get()
            ->filter(fn (CertificateNumberActivity $activity): bool => (int) $activity->rows_count > 0)
            ->map(fn (CertificateNumberActivity $activity): array => [
                'id' => (int) $activity->getKey(),
                'kegiatan' => trim(preg_replace('/\s+/u', ' ', (string) $activity->kegiatan) ?? ''),
                'tempat' => (string) ($activity->tempat ?? ''),
                'tanggal' => $activity->tanggal?->locale('id')->translatedFormat('j F Y') ?? '',
                'kode' => (string) $activity->kode,
                'tahun' => (int) $activity->tahun,
                'range' => $activity->nomorRangeLabel(),
                'jumlah' => (int) $activity->rows_count,
            ])
            ->values()
            ->all();
    }

    public function closeNumbersDialog(): void
    {
        $this->showNumbersDialog = false;
        $this->numberActivityId = null;
        $this->numberSearch = '';
    }

    /**
     * Pilih kegiatan lewat klik baris tabel di dialog.
     */
    public function selectNumberActivity(int $activityId): void
    {
        $this->numberActivityId = $activityId;
    }

    /**
     * Isi dataset template dari daftar nomor sertifikat sebuah kegiatan.
     * Data ini ikut disimpan ke DB seperti dataset Excel biasa, tetapi tidak
     * ada berkas Excel yang disimpan.
     */
    public function applyNumberActivity(CertificateNumberService $service): void
    {
        if ($this->numberActivityId === null) {
            Notification::make()->title('Pilih kegiatan terlebih dahulu.')->danger()->send();

            return;
        }

        $activity = CertificateNumberActivity::query()->find($this->numberActivityId);

        if ($activity === null || ! $activity->isVisibleTo(Auth::user())) {
            Notification::make()->title('Kegiatan tidak ditemukan.')->danger()->send();

            return;
        }

        $dataset = $service->toDataset($activity);

        if ($dataset['rows'] === []) {
            Notification::make()
                ->title('Kegiatan belum punya baris')
                ->body('Tambahkan penerima lewat halaman Nomor Sertifikat.')
                ->danger()
                ->send();

            return;
        }

        $this->applyDataset(
            array_merge($dataset['headers'], ['Kegiatan', 'Tempat', 'Tanggal Kegiatan']),
            array_merge($dataset['keys'], ['kegiatan', 'tempat', 'tanggal_kegiatan']),
            $dataset['rows'],
        );

        $this->datasetDirty = true;
        $this->savedDatasetId = null;
        $this->persistDatasetSilently();

        $this->showNumbersDialog = false;
        $this->numberActivityId = null;

        Notification::make()
            ->title('Data nomor sertifikat dipakai')
            ->body(count($this->rows).' baris dari kegiatan terpilih.')
            ->success()
            ->send();
    }

    /**
     * Hapus data Excel aktif (tidak menghapus template/field).
     */
    public function clearExcelData(): void
    {
        $this->headers = [];
        $this->keys = [];
        $this->rows = [];
        $this->previewRow = [];
        $this->datasetPreviewIndex = null;
        $this->datasetLoaded = false;
        $this->pendingExcelHeaders = [];
        $this->pendingExcelKeys = [];
        $this->pendingExcelRows = [];
        $this->pendingExcelName = null;
        $this->excelUpload = null;
        $this->datasetDirty = true;
        $this->savedDatasetId = null;

        if ($this->templateId !== null) {
            CertificateDataset::query()->where('template_id', $this->templateId)->delete();
        }
    }

    // ------------------------------------------------------------------
    // Aksi: kelola field
    // ------------------------------------------------------------------

    public function updatedDataBackground(mixed $value): void
    {
        $value = $this->firstUploadedFile($value);

        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        $size = @getimagesize($value->getRealPath());

        if ($size === false) {
            Notification::make()
                ->title('Berkas bukan gambar yang valid')
                ->body('Gunakan JPG, PNG, atau WEBP.')
                ->danger()
                ->send();

            return;
        }

        // Kanvas langsung tampil dari berkas sementara — user tidak perlu
        // menyimpan dulu untuk mulai menempatkan field.
        [$this->bgWidthPx, $this->bgHeightPx, $this->pageWidthMm, $this->pageHeightMm] = $this->pageSizeFromImage(
            (int) $size[0],
            (int) $size[1],
        );

        $this->backgroundUrl = $value->temporaryUrl();
    }

    /**
     * Filament FileUpload menyimpan state sebagai array (single => 1 elemen).
     * Autosave butuh berkasnya langsung.
     */
    private function firstUploadedFile(mixed $value): mixed
    {
        if (is_array($value)) {
            return reset($value) ?: null;
        }

        return $value;
    }

    public function addField(string $key): void
    {
        $this->addFieldAt($key, 20.0, min(20.0 + count($this->fields) * 4, max(20.0, $this->pageHeightMm - 30)));
    }

    /**
     * Tambah field pada posisi mm tertentu — dipakai saat kolom Excel
     * dijatuhkan (drag & drop) di kanvas.
     */
    public function addFieldAt(string $key, float $xMm, float $yMm): void
    {
        $knownKeys = $this->keys !== [] ? $this->keys : $this->headers;

        if (! in_array($key, $knownKeys, true)) {
            return;
        }

        $width = (float) min(120.0, max(40.0, $this->pageWidthMm - 20));
        $height = 20.0;

        $x = min(max(0.0, $xMm - $width / 2), max(0.0, $this->pageWidthMm - $width));
        $y = min(max(0.0, $yMm - $height / 2), max(0.0, $this->pageHeightMm - $height));

        $this->fields[] = [
            'id' => null,
            'key' => $key,
            'label' => $this->labelForKey($key),
            'x_mm' => round($x, 2),
            'y_mm' => round($y, 2),
            'w_mm' => round($width, 2),
            'h_mm' => round($height, 2),
            'font_family' => 'DejaVu Sans',
            'font_size_pt' => 24.0,
            'color' => '#000000',
            'bold' => false,
            'italic' => false,
            'align' => 'center',
            'valign' => 'middle',
            'sort_order' => count($this->fields),
        ];

        $this->selectedFieldIndex = count($this->fields) - 1;

        // Drop di kanvas langsung disimpan (tanpa notifikasi) supaya posisi
        // field tidak hilang saat halaman di-refresh.
        $this->persistFieldsSilently();
    }

    private function labelForKey(string $key): string
    {
        $index = array_search($key, $this->keys, true);

        if ($index === false) {
            return Str::headline($key);
        }

        return $this->headers[$index] ?? Str::headline($key);
    }

    public function selectField(int $index): void
    {
        $this->selectedFieldIndex = array_key_exists($index, $this->fields) ? $index : null;
    }

    public function removeField(int $index): void
    {
        if (! array_key_exists($index, $this->fields)) {
            return;
        }

        array_splice($this->fields, $index, 1);
        $this->reindexFields();

        $this->persistFieldsSilently();
    }

    /**
     * Terima posisi/ukuran baru dari kanvas (sudah dalam mm).
     */
    public function updateFieldGeometry(int $index, float $x, float $y, float $w, float $h): void
    {
        if (! array_key_exists($index, $this->fields)) {
            return;
        }

        $w = max(5, min($w, $this->pageWidthMm));
        $h = max(3, min($h, $this->pageHeightMm));
        $x = max(0, min($x, $this->pageWidthMm - $w));
        $y = max(0, min($y, $this->pageHeightMm - $h));

        $this->fields[$index]['x_mm'] = round($x, 2);
        $this->fields[$index]['y_mm'] = round($y, 2);
        $this->fields[$index]['w_mm'] = round($w, 2);
        $this->fields[$index]['h_mm'] = round($h, 2);

        $this->persistFieldsSilently();
    }

    /**
     * Ubah satu komponen posisi/ukuran field lewat input angka di panel
     * properti (presisi mm, tanpa harus drag). Nilai di-clamp sama seperti
     * updateFieldGeometry() supaya field tidak keluar halaman.
     */
    public function updateFieldGeometryPart(int $index, string $part, mixed $value): void
    {
        if (! array_key_exists($index, $this->fields)) {
            return;
        }

        $part = in_array($part, ['x_mm', 'y_mm', 'w_mm', 'h_mm'], true) ? $part : null;

        if ($part === null || ! is_numeric($value)) {
            return;
        }

        $number = round((float) $value, 2);
        $field = $this->fields[$index];

        $w = (float) ($field['w_mm'] ?? 0);
        $h = (float) ($field['h_mm'] ?? 0);
        $x = (float) ($field['x_mm'] ?? 0);
        $y = (float) ($field['y_mm'] ?? 0);

        match ($part) {
            'w_mm' => $w = max(5, min($number, $this->pageWidthMm)),
            'h_mm' => $h = max(3, min($number, $this->pageHeightMm)),
            'x_mm' => $x = $number,
            'y_mm' => $y = $number,
        };

        $x = max(0, min($x, max(0, $this->pageWidthMm - $w)));
        $y = max(0, min($y, max(0, $this->pageHeightMm - $h)));

        $this->fields[$index]['x_mm'] = round($x, 2);
        $this->fields[$index]['y_mm'] = round($y, 2);
        $this->fields[$index]['w_mm'] = round($w, 2);
        $this->fields[$index]['h_mm'] = round($h, 2);

        $this->persistFieldsSilently();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function updateFieldProperties(int $index, array $properties): void
    {
        if (! array_key_exists($index, $this->fields)) {
            return;
        }

        $allowed = ['label', 'font_family', 'font_size_pt', 'color', 'bold', 'italic', 'align', 'valign'];

        foreach ($properties as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }

            $this->fields[$index][$key] = match ($key) {
                'font_family' => $this->normalizeFontFamily($value),
                'font_size_pt' => max(4.0, min(200.0, (float) $value)),
                'bold', 'italic' => (bool) $value,
                'align' => in_array($value, ['left', 'center', 'right'], true) ? $value : 'center',
                'valign' => in_array($value, ['top', 'middle', 'bottom'], true) ? $value : 'middle',
                'color' => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $value) === 1 ? $value : '#000000',
                default => $value,
            };
        }

        $this->persistFieldsSilently();
    }

    /**
     * Hanya izinkan keluarga font dari master font aktif, atau nilai lama
     * yang masih dipakai template ini (agar tidak berubah diam-diam saat
     * font dinonaktifkan).
     */
    private function normalizeFontFamily(mixed $value): string
    {
        $family = trim((string) $value);

        if ($family === '') {
            return 'DejaVu Sans';
        }

        $allowed = array_column($this->fontOptions, 'family');

        if (in_array($family, $allowed, true)) {
            return $family;
        }

        $exists = CertificateFont::query()->where('css_family', $family)->exists();

        return $exists ? $family : 'DejaVu Sans';
    }

    private function reindexFields(): void
    {
        foreach ($this->fields as $i => &$field) {
            $field['sort_order'] = $i;
        }
        unset($field);

        $this->selectedFieldIndex = null;
    }

    public function updatedDatasetPreviewIndex(mixed $value): void
    {
        $index = (int) $value;
        $this->previewRow = $this->rows[$index] ?? [];
        $this->previewGoto = (string) ($index + 1);
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function previousPreviewRow(): void
    {
        $this->movePreview(-1);
    }

    public function nextPreviewRow(): void
    {
        $this->movePreview(1);
    }

    private function movePreview(int $delta): void
    {
        if ($this->rows === []) {
            return;
        }

        $current = (int) ($this->datasetPreviewIndex ?? 0);
        $next = max(0, min($current + $delta, count($this->rows) - 1));

        $this->datasetPreviewIndex = $next;
        $this->previewRow = $this->rows[$next] ?? [];
        $this->previewGoto = (string) ($next + 1);
    }

    /**
     * Lompat langsung ke nomor data yang diketik (1-based). Nilai di luar
     * rentang dijepit ke baris pertama/terakhir, bukan diabaikan, supaya
     * perilakunya sama seperti tombol prev/next.
     */
    public function gotoPreviewRow(): void
    {
        if ($this->rows === []) {
            $this->previewGoto = '1';

            return;
        }

        $nomor = (int) preg_replace('/[^0-9]/', '', $this->previewGoto);
        $index = max(0, min($nomor - 1, count($this->rows) - 1));

        $this->datasetPreviewIndex = $index;
        $this->previewRow = $this->rows[$index] ?? [];
        $this->previewGoto = (string) ($index + 1);
    }

    // ------------------------------------------------------------------
    // Aksi: simpan template
    // ------------------------------------------------------------------

    public function save(): void
    {
        $state = $this->form->getState();

        $title = trim((string) ($state['title'] ?? ''));

        if ($title === '') {
            Notification::make()->title('Nama template wajib diisi.')->danger()->send();

            return;
        }

        if ($this->fields === []) {
            Notification::make()->title('Tambahkan minimal satu field ke template.')->danger()->send();

            return;
        }

        $template = $this->templateId !== null
            ? CertificateTemplate::query()->find($this->templateId)
            : null;

        if ($template !== null && ! $template->isVisibleTo(Auth::user())) {
            abort(403);
        }

        // Background: unggahan baru, atau pakai milik template lama.
        $background = $this->firstUploadedFile($state['background'] ?? null);

        if ($background instanceof TemporaryUploadedFile) {
            $backgroundPath = $this->storeBackground($background);

            if ($backgroundPath === null) {
                Notification::make()->title('Gambar template tidak valid.')->danger()->send();

                return;
            }

            // Ganti gambar: buang berkas lama supaya tidak menumpuk di disk.
            if ($template !== null && $template->background_path !== $backgroundPath) {
                Storage::disk('local')->delete($template->background_path);
            }
        } elseif ($template !== null) {
            $backgroundPath = $template->background_path;
        } else {
            Notification::make()->title('Unggah gambar template terlebih dahulu.')->danger()->send();

            return;
        }

        // Data Excel kini berasal dari dialog (bukan state form). Wajib ada
        // sebelum template disimpan.
        if ($this->headers === [] || $this->keys === [] || $this->rows === []) {
            Notification::make()
                ->title('Data Excel belum ada')
                ->body('Buka tombol "Data Excel" dan unggah berkas terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        DB::transaction(function () use ($template, $state, $title, $backgroundPath): void {
            [$bgWidth, $bgHeight, $pageWidth, $pageHeight] = $this->pageSizeFromBackground($backgroundPath);

            $attributes = [
                'title' => $title,
                'background_path' => $backgroundPath,
                'bg_width_px' => $bgWidth,
                'bg_height_px' => $bgHeight,
                'page_width_mm' => $pageWidth,
                'page_height_mm' => $pageHeight,
                'file_name_pattern' => trim((string) ($state['file_name_pattern'] ?? 'sertifikat <<nama>>')),
            ];

            if ($template === null) {
                $template = CertificateTemplate::query()->create($attributes + [
                    'created_by' => Auth::id(),
                ]);
                $isNew = true;
            } else {
                $template->update($attributes);
                $isNew = false;
            }

            $this->syncFieldsToTemplate($template);

            $this->replaceDatasetForTemplate($template, force: $isNew);

            $this->templateId = $template->id;
        });

        // Muat ulang dari DB supaya state kanvas memuat id field yang benar.
        $fresh = CertificateTemplate::query()->find($this->templateId);
        $fresh?->load('fields');

        // Master font bisa berubah selama halaman terbuka (font baru/aktif).
        $this->loadFonts();

        // Setelah simpan pertama, page harus berlanjut sebagai mode edit dan
        // URL wajib memuat ?template= agar refresh tidak kembali ke kosong.
        if ($fresh !== null) {
            $this->openTemplate = $fresh->id;
        }

        $this->fields = $fresh
            ? $fresh->fields->map(fn (CertificateField $field): array => $this->fieldToArray($field))->all()
            : $this->fields;

        if ($fresh !== null) {
            $this->bgWidthPx = $fresh->bg_width_px;
            $this->bgHeightPx = $fresh->bg_height_px;
            $this->pageWidthMm = $fresh->page_width_mm;
            $this->pageHeightMm = $fresh->page_height_mm;
            $this->backgroundUrl = $this->backgroundUrlFor($fresh->background_path);
        }

        Notification::make()
            ->title('Template tersimpan')
            ->body('Anda bisa lanjut mengunduh sertifikat satuan atau batch.')
            ->success()
            ->send();
    }

    /**
     * Tulis ulang dataset template dari state editor (headers/keys/rows).
     * Dataset lama dihapus supaya tidak menumpuk; hanya dipanggil bila data
     * berubah, template baru, atau memang belum punya dataset.
     */
    private function replaceDatasetForTemplate(CertificateTemplate $template, bool $force = false): void
    {
        if ($this->headers === [] || $this->rows === []) {
            return;
        }

        $hasDataset = $template->datasets()->exists();

        if (! $force && ! $this->datasetDirty && $hasDataset) {
            return;
        }

        $dataset = CertificateDataset::query()->create([
            'template_id' => $template->id,
            'created_by' => Auth::id(),
            'original_name' => $this->pendingExcelName,
            'headers' => $this->headers,
            'row_count' => count($this->rows),
        ]);

        foreach ($this->rows as $index => $row) {
            CertificateDatasetRow::query()->create([
                'dataset_id' => $dataset->id,
                'row_index' => $index,
                'data' => $row,
            ]);
        }

        // Dataset lama (dari impor sebelumnya) tidak lagi relevan.
        CertificateDataset::query()
            ->where('template_id', $template->id)
            ->whereKeyNot($dataset->id)
            ->delete();

        $this->savedDatasetId = $dataset->id;
        $this->datasetDirty = false;
    }

    /**
     * Simpan dataset tanpa notifikasi (dipakai tombol "Gunakan Data" pada
     * dialog Excel supaya data langsung tersimpan pada template yang ada).
     */
    private function persistDatasetSilently(): void
    {
        if ($this->templateId === null || $this->headers === [] || $this->rows === []) {
            return;
        }

        try {
            $template = CertificateTemplate::query()->find($this->templateId);

            if ($template === null || ! $template->isVisibleTo(Auth::user())) {
                return;
            }

            DB::transaction(fn () => $this->replaceDatasetForTemplate($template, force: true));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Tulis ulang daftar field milik template (delete + insert, urutan array
     * = sort_order). Dipakai simpan penuh maupun autosave senyap.
     */
    private function syncFieldsToTemplate(CertificateTemplate $template): void
    {
        $template->fields()->delete();

        foreach ($this->fields as $i => $field) {
            $template->fields()->create([
                'key' => $field['key'],
                'label' => $field['label'],
                'x_mm' => $field['x_mm'],
                'y_mm' => $field['y_mm'],
                'w_mm' => $field['w_mm'],
                'h_mm' => $field['h_mm'],
                'font_family' => $field['font_family'],
                'font_size_pt' => $field['font_size_pt'],
                'color' => $field['color'],
                'bold' => $field['bold'],
                'italic' => $field['italic'],
                'align' => $field['align'],
                'valign' => $field['valign'],
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Autosave ringan untuk perubahan posisi/daftar field. Hanya berlaku bila
     * template sudah pernah tersimpan; template baru tetap menunggu tombol
     * "Simpan Template" (butuh nama, gambar, dan Excel). Tidak mengirim
     * notifikasi apa pun — termasuk saat gagal, agar drag & drop tetap senyap.
     */
    private function persistFieldsSilently(): void
    {
        try {
            $template = $this->templateId !== null
                ? CertificateTemplate::query()->find($this->templateId)
                : $this->createTemplateSilently();

            if ($template === null || ! $template->isVisibleTo(Auth::user())) {
                return;
            }

            DB::transaction(fn () => $this->syncFieldsToTemplate($template));

            $template->load('fields');
            $this->templateId = $template->id;
            $this->openTemplate = $template->id;
            $this->fields = $template->fields
                ->map(fn (CertificateField $field): array => $this->fieldToArray($field))
                ->all();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Buat template pertama kali secara senyap saat field dijatuhkan, supaya
     * drop langsung tersimpan tanpa harus klik "Simpan Template". Hanya jalan
     * bila nama template dan gambar sudah tersedia — selain itu null (drop
     * tetap tampil di kanvas tetapi belum tersimpan).
     */
    private function createTemplateSilently(): ?CertificateTemplate
    {
        // Baca state mentah ($this->data), bukan getState(): autosave tidak
        // boleh memicu validasi form (judul/berkas wajib) saat drop.
        $state = $this->data ?? [];
        $title = trim((string) ($state['title'] ?? ''));

        if ($title === '' || $this->fields === []) {
            return null;
        }

        $background = $this->firstUploadedFile($state['background'] ?? null);

        if (! $background instanceof TemporaryUploadedFile) {
            return null;
        }

        $backgroundPath = $this->storeBackground($background);

        if ($backgroundPath === null) {
            return null;
        }

        [$bgWidth, $bgHeight, $pageWidth, $pageHeight] = $this->pageSizeFromBackground($backgroundPath);

        $template = CertificateTemplate::query()->create([
            'created_by' => Auth::id(),
            'title' => $title,
            'background_path' => $backgroundPath,
            'bg_width_px' => $bgWidth,
            'bg_height_px' => $bgHeight,
            'page_width_mm' => $pageWidth,
            'page_height_mm' => $pageHeight,
            'file_name_pattern' => trim((string) ($state['file_name_pattern'] ?? 'sertifikat <<nama>>')),
        ]);

        $this->bgWidthPx = $bgWidth;
        $this->bgHeightPx = $bgHeight;
        $this->pageWidthMm = $pageWidth;
        $this->pageHeightMm = $pageHeight;
        $this->backgroundUrl = $this->backgroundUrlFor($backgroundPath);

        // Gambar sudah tersalin ke disk privat; kosongkan state unggahan agar
        // "Simpan Template" berikutnya tidak menyimpan salinan kedua.
        $this->data['background'] = null;

        return $template;
    }

    private function storeBackground(TemporaryUploadedFile $file): ?string
    {
        $size = @getimagesize($file->getRealPath());

        if ($size === false) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'png';

        $name = 'certificate/templates/'.Str::uuid().'.'.$extension;

        Storage::disk('local')->put($name, file_get_contents($file->getRealPath()));

        return $name;
    }

    /**
     * @return array{0: int, 1: int, 2: float, 3: float} [widthPx, heightPx, pageWidthMm, pageHeightMm]
     */
    private function pageSizeFromBackground(string $path): array
    {
        $absolute = storage_path('app/private/'.ltrim($path, '/'));

        $size = is_file($absolute) ? @getimagesize($absolute) : false;

        if ($size === false) {
            return [0, 0, 297.0, 210.0];
        }

        return $this->pageSizeFromImage((int) $size[0], (int) $size[1]);
    }

    /**
     * @return array{0: int, 1: int, 2: float, 3: float} [widthPx, heightPx, pageWidthMm, pageHeightMm]
     */
    private function pageSizeFromImage(int $widthPx, int $heightPx): array
    {
        if ($widthPx <= 0 || $heightPx <= 0) {
            return [0, 0, 297.0, 210.0];
        }

        // Anggap gambar 96 dpi; halaman PDF mengikuti rasio gambar asli.
        $pageWidth = round($widthPx * 25.4 / 96, 2);
        $pageHeight = round($heightPx * 25.4 / 96, 2);

        // Batasi agar halaman masuk akal untuk cetak (A6..A2).
        $maxMm = 594.0;
        if ($pageWidth > $maxMm || $pageHeight > $maxMm) {
            $scale = $maxMm / max($pageWidth, $pageHeight);
            $pageWidth = round($pageWidth * $scale, 2);
            $pageHeight = round($pageHeight * $scale, 2);
        }

        return [$widthPx, $heightPx, $pageWidth, $pageHeight];
    }

    private function backgroundUrlFor(string $path): ?string
    {
        if (! is_file(storage_path('app/private/'.ltrim($path, '/')))) {
            return null;
        }

        return route('tool.certificate.background', ['path' => $this->backgroundToken($path)]);
    }

    /**
     * Editor dan route penyaji background memakai token HMAC, bukan path
     * mentah — mencegah pengguna menebak/membaca berkas milik template lain.
     */
    private function backgroundToken(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    // ------------------------------------------------------------------
    // Unduh
    // ------------------------------------------------------------------

    public function downloadSingle(
        int $rowIndex,
        ?CertificateRenderer $renderer = null,
        ?CertificateNamingService $naming = null,
    ): ?BinaryFileResponse {
        $renderer ??= app(CertificateRenderer::class);
        $naming ??= app(CertificateNamingService::class);

        // Simpan dulu perubahan terakhir (posisi, font, judul, pola nama)
        // supaya PDF memakai setting yang sedang terlihat di kanvas.
        $this->persistDraftBeforeDownload();

        $template = $this->currentTemplate();

        if ($template === null) {
            Notification::make()->title('Simpan template terlebih dahulu.')->danger()->send();

            return null;
        }

        $row = $this->rows[$rowIndex] ?? null;

        if ($row === null) {
            Notification::make()->title('Baris data tidak ditemukan.')->danger()->send();

            return null;
        }

        try {
            $binary = $renderer->renderPdf($template, $row);
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Gagal membuat PDF')->body($e->getMessage())->danger()->persistent()->send();

            return null;
        }

        $fileName = $naming->fileNameFor($template, $row, $rowIndex).'.pdf';

        CertificateGeneration::query()->create([
            'template_id' => $template->id,
            'dataset_id' => $this->savedDatasetId,
            'created_by' => Auth::id(),
            'mode' => 'single',
            'file_name' => $fileName,
            'row_count' => 1,
        ]);

        return $naming->pdfResponse($binary, $fileName);
    }

    /**
     * Popup pilih rentang unduhan batch. Dua titik slider memilih data
     * ke-berapa sampai ke-berapa; jarak titik dibatasi 24 (maksimal 25 PDF
     * per klik) supaya satu request tetap di bawah batas waktu
     * PHP-FPM/Nginx — untuk 50+ baris user menggeser ke blok berikutnya dari
     * popup yang sama.
     */
    public function downloadBatchRangeAction(): \Filament\Actions\Action
    {
        $total = count($this->rows);
        $defaultTo = min(25, max(1, $total));

        $schema = $total > 1
            ? [
                Slider::make('range')
                    ->label('Rentang data (geser dua titik)')
                    ->range(1, $total)
                    ->step(1)
                    ->decimalPlaces(0)
                    ->tooltips()
                    ->maxDifference(24)
                    ->default([1, $defaultTo])
                    ->live()
                    ->required(),
                Placeholder::make('rentang_info')
                    ->label('Dipilih')
                    ->content(fn (Get $get): string => $this->batchRangeInfo($get('range'), $total)),
            ]
            : [
                Placeholder::make('rentang_info')
                    ->label('Dipilih')
                    ->content('Data 1 · 1 PDF'),
            ];

        return \Filament\Actions\Action::make('downloadBatchRange')
            ->label('Unduh Batch')
            ->modalHeading('Unduh Sertifikat Batch')
            ->modalDescription($total.' data tersedia. Pilih data ke-berapa sampai ke-berapa — maksimal 25 PDF per klik agar tidak timeout.')
            ->modalSubmitActionLabel('Unduh')
            ->schema($schema)
            ->action(fn (array $data) => $this->downloadRange(
                $total > 1 ? (int) ($data['range'][0] ?? 1) : 1,
                $total > 1 ? (int) ($data['range'][1] ?? $defaultTo) : 1,
            ))
            ->visible(fn (): bool => $total > 0);
    }

    /**
     * Ringkasan rentang terpilih untuk pratinjau di popup batch.
     */
    private function batchRangeInfo(mixed $range, int $total): string
    {
        $from = (int) (is_array($range) ? ($range[0] ?? 1) : 1);
        $to = (int) (is_array($range) ? ($range[1] ?? $from) : $from);

        $from = max(1, min($from, max(1, $total)));
        $to = max($from, min($to, max(1, $total)));

        return 'Data '.$from.'–'.$to.' · '.($to - $from + 1).' PDF';
    }

    /**
     * Catatan: parameter $renderer/$naming sengaja opsional. Metode ini juga
     * dipanggil dari closure Action (bukan lewat dispatcher Livewire), dan di
     * jalur itu container TIDAK menyuntik dependensi — dulu menyebabkan
     * "Too few arguments" saat Unduh Batch.
     */
    public function downloadRange(
        int $from,
        ?int $to = null,
        ?CertificateRenderer $renderer = null,
        ?CertificateNamingService $naming = null,
    ): ?BinaryFileResponse {
        $renderer ??= app(CertificateRenderer::class);
        $naming ??= app(CertificateNamingService::class);

        // Batch juga memakai state terbaru, bukan snapshot DB yang lama.
        $this->persistDraftBeforeDownload();

        $template = $this->currentTemplate();

        if ($template === null) {
            Notification::make()->title('Simpan template terlebih dahulu.')->danger()->send();

            return null;
        }

        $total = count($this->rows);

        if ($total === 0) {
            Notification::make()->title('Tidak ada data untuk diunduh.')->danger()->send();

            return null;
        }

        $from = max(1, min($from, $total));
        // Rentang pilihan user (dari slider) dijepit ke batas 25 PDF per
        // klik supaya tidak timeout — sama seperti blok bawaan dulu.
        $to = $to === null ? min($from + 24, $total) : max($from, min($to, $from + 24, $total));

        set_time_limit(120);

        $outputs = [];

        for ($index = $from - 1; $index < $to; $index++) {
            $row = $this->rows[$index];

            try {
                $binary = $renderer->renderPdf($template, $row);
            } catch (\Throwable $e) {
                report($e);
                Notification::make()
                    ->title('Gagal membuat PDF pada baris '.($index + 1))
                    ->body($e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();

                return null;
            }

            $outputs[] = [
                'name' => $naming->fileNameFor($template, $row, $index).'.pdf',
                'binary' => $binary,
            ];
        }

        $zipName = (Str::slug($template->title) ?: 'sertifikat')
            .'-'.$from.'-'.$to.'-'.now()->format('Ymd-His').'.zip';

        CertificateGeneration::query()->create([
            'template_id' => $template->id,
            'dataset_id' => $this->savedDatasetId,
            'created_by' => Auth::id(),
            'mode' => 'batch',
            'file_name' => $zipName,
            'row_count' => count($outputs),
        ]);

        return $naming->zip($outputs, $zipName);
    }

    /**
     * Simpan senyap state editor (judul, pola nama berkas, seluruh field)
     * sebelum PDF dirender. Tanpa ini, perubahan yang belum di-"Simpan
     * Template" (mis. font 36pt) tidak ikut ke hasil unduhan.
     */
    private function persistDraftBeforeDownload(): void
    {
        if ($this->templateId === null) {
            return;
        }

        try {
            $template = CertificateTemplate::query()->find($this->templateId);

            if ($template === null || ! $template->isVisibleTo(Auth::user())) {
                return;
            }

            $state = $this->data ?? [];
            $attributes = [];

            if (filled($state['title'] ?? null)) {
                $attributes['title'] = trim((string) $state['title']);
            }

            if (array_key_exists('file_name_pattern', $state)) {
                $attributes['file_name_pattern'] = trim((string) $state['file_name_pattern'])
                    ?: 'sertifikat <<nama>>';
            }

            DB::transaction(function () use ($template, $attributes): void {
                if ($attributes !== []) {
                    $template->update($attributes);
                }

                $this->syncFieldsToTemplate($template);
            });

            $template->load('fields');

            $this->fields = $template->fields
                ->map(fn (CertificateField $field): array => $this->fieldToArray($field))
                ->all();
        } catch (\Throwable $e) {
            // Jangan blokir unduhan; template lama tetap dipakai bila gagal.
            report($e);
        }
    }

    private function currentTemplate(): ?CertificateTemplate
    {
        if ($this->templateId === null) {
            return null;
        }

        $template = CertificateTemplate::query()->with('fields')->find($this->templateId);

        if ($template === null || ! $template->isVisibleTo(Auth::user())) {
            return null;
        }

        return $template;
    }

    // ------------------------------------------------------------------
    // Pratinjau
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>|null
     */
    /**
     * Nilai satu field untuk pratinjau: isi data bila mode pratinjau aktif,
     * atau penanda {key} bila mata ditutup (hanya melihat posisi field).
     */
    public function previewValueFor(array $field): string
    {
        if (! $this->showPreview) {
            return '{{'.$field['key'].'}}';
        }

        return (string) ($this->previewRow[$field['key']] ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Daftar Template')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ManageCertificates::getUrl()),
            $this->downloadBatchRangeAction(),
        ];
    }
}
