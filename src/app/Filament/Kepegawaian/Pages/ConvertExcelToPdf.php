<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Services\Kepegawaian\ExcelToPdfService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Generic Excel-to-PDF converter, not tied to any kepegawaian table. Upload
 * one or many workbooks; each workbook gets its own sheet picker (defaults
 * to its first sheet) plus a per-file "merge" toggle to combine several of
 * its sheets into one PDF. One file in, one PDF out — multiple files are
 * bundled into a single .zip.
 */
class ConvertExcelToPdf extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'Convert Excel ke PDF';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Convert Excel ke PDF';

    /** Tool disembunyikan dari navigasi (halaman tetap bisa diakses via URL). */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected string $view = 'filament.kepegawaian.pages.convert-excel-to-pdf';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Berkas Excel')
                    ->description('Upload satu atau beberapa berkas (.xlsx / .xls). Sheet tiap berkas terbaca otomatis.')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Berkas')
                            ->multiple()
                            ->live()
                            ->storeFiles(false)
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(20480)
                            ->required()
                            ->afterStateUpdated(function (Set $set, Get $get, ?array $state): void {
                                $set('sheets', $this->rebuildSheetsState((array) $state, (array) ($get('sheets') ?? [])));
                            }),
                        Repeater::make('sheets')
                            ->label('Sheet per Berkas')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Hidden::make('file_label'),
                                Hidden::make('available_sheets'),
                                Placeholder::make('file_label_display')
                                    ->label('Berkas')
                                    ->content(fn (Get $get): string => (string) $get('file_label')),
                                Toggle::make('merge')
                                    ->label('Gabung jadi satu PDF')
                                    ->helperText('Aktifkan untuk memilih lebih dari satu sheet.')
                                    ->live()
                                    ->default(false),
                                CheckboxList::make('selected_sheets')
                                    ->label('Sheet')
                                    ->options(fn (Get $get): array => array_combine(
                                        (array) $get('available_sheets'),
                                        (array) $get('available_sheets'),
                                    ))
                                    ->required()
                                    ->live()
                                    ->rule(function (Get $get) {
                                        return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                            if (! $get('merge') && count((array) $value) > 1) {
                                                $fail('Aktifkan "Gabung jadi satu PDF" untuk memilih lebih dari satu sheet.');
                                            }
                                        };
                                    }),
                            ])
                            ->visible(fn (Get $get): bool => filled($get('files')))
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Rebuild the per-file sheet metadata from the current file-upload
     * state, keyed the same way the upload's own state is keyed so a
     * `sheets` entry always lines up with its file. Files that were already
     * present keep their existing choices; only new files get read/defaulted.
     *
     * @param  array<string, TemporaryUploadedFile>  $files
     * @param  array<string, array<string, mixed>>  $existing
     * @return array<string, array<string, mixed>>
     */
    protected function rebuildSheetsState(array $files, array $existing): array
    {
        $result = [];

        foreach ($files as $key => $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            if (isset($existing[$key]) && filled($existing[$key]['available_sheets'] ?? null)) {
                $result[$key] = $existing[$key];

                continue;
            }

            try {
                $names = app(ExcelToPdfService::class)->sheetNames($file->getRealPath());
            } catch (\Throwable $e) {
                $result[$key] = [
                    'file_label' => $file->getClientOriginalName().' — gagal dibaca: '.$e->getMessage(),
                    'available_sheets' => [],
                    'selected_sheets' => [],
                    'merge' => false,
                ];

                continue;
            }

            $result[$key] = [
                'file_label' => $file->getClientOriginalName(),
                'available_sheets' => $names,
                'selected_sheets' => array_slice($names, 0, 1),
                'merge' => false,
            ];
        }

        return $result;
    }

    public function convert(ExcelToPdfService $converter): ?BinaryFileResponse
    {
        $state = $this->form->getState();

        $files = $state['files'] ?? [];
        $sheets = $state['sheets'] ?? [];

        if (blank($files)) {
            Notification::make()->title('Pilih berkas terlebih dahulu.')->danger()->send();

            return null;
        }

        $outputs = [];

        foreach ($files as $key => $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $meta = $sheets[$key] ?? null;
            $selected = $meta['selected_sheets'] ?? [];
            $label = $meta['file_label'] ?? $file->getClientOriginalName();

            if (blank($selected)) {
                Notification::make()
                    ->title('Ada berkas tanpa sheet terpilih')
                    ->body($label)
                    ->danger()
                    ->send();

                return null;
            }

            try {
                $pdf = $converter->toPdf($file->getRealPath(), $selected);
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Gagal convert')
                    ->body("{$label}: {$e->getMessage()}")
                    ->danger()
                    ->persistent()
                    ->send();

                return null;
            }

            $outputs[] = [
                'name' => Str::of($label)->beforeLast('.')->append('.pdf')->toString(),
                'binary' => $pdf,
            ];
        }

        if (count($outputs) === 1) {
            return $this->downloadSingle($outputs[0]);
        }

        return $this->downloadZip($outputs);
    }

    /**
     * @param  array{name: string, binary: string}  $output
     */
    protected function downloadSingle(array $output): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmp, $output['binary']);

        return response()
            ->download($tmp, $output['name'], ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  list<array{name: string, binary: string}>  $outputs
     */
    protected function downloadZip(array $outputs): BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $used = [];

        foreach ($outputs as $output) {
            $name = $output['name'];
            $suffix = 1;

            while (in_array($name, $used, true)) {
                $name = Str::of($output['name'])->beforeLast('.pdf')." ({$suffix}).pdf";
                $suffix++;
            }

            $used[] = $name;
            $zip->addFromString($name, $output['binary']);
        }

        $zip->close();

        $filename = 'excel-ke-pdf-'.now()->format('Ymd-His').'.zip';

        return response()
            ->download($tmp, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
