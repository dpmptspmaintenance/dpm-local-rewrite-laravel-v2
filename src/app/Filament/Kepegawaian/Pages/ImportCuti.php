<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\CutiResource;
use App\Services\Kepegawaian\CutiImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Impor Cuti (Excel). Dipisah dari halaman Pegawai dan DUK supaya tiap jenis
 * impor punya page sendiri di grup Master.
 *
 * Baris dengan No Surat yang sama diperbarui; belum ada ditambahkan; data lama
 * yang tak muncul di berkas tetap tersimpan. Baris yang pernah diedit manual
 * tidak ditimpa.
 */
class ImportCuti extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Impor Cuti';

    protected static string | \UnitEnum | null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Impor Cuti (Excel)';

    protected static ?string $slug = 'impor-cuti';

    protected string $view = 'filament.kepegawaian.pages.import-cuti';

    public ?array $data = [];

    /** @var list<string> */
    public array $errors = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Berkas Excel')
                    ->description('Kolom wajib berurutan: No, No Surat, Nip, Nama, Tanggal Mulai Diajukan, Tanggal Selesai Diajukan, Opd, Unit Kerja, Lokasi Kerja, Status, Keperluan, Jenis.')
                    ->schema([
                        FileUpload::make('cuti_file')
                            ->label('File (.xlsx / .xls)')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(20480)
                            ->required()
                            ->storeFiles(false),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Daftar Cuti')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(CutiResource::getUrl('index')),
        ];
    }

    public function import(CutiImportService $importer): void
    {
        $this->errors = [];

        $upload = $this->form->getState()['cuti_file'] ?? null;

        if (! $upload) {
            Notification::make()->title('Pilih berkas terlebih dahulu.')->danger()->send();

            return;
        }

        $result = $importer->replaceAll($upload->getRealPath());

        $this->errors = $result['errors'];

        if ($result['inserted'] === 0 && $result['updated'] === 0 && $result['skipped'] === 0 && ($result['tanpa_surat'] ?? 0) === 0) {
            Notification::make()
                ->title('Impor gagal')
                ->body($result['errors'][0] ?? 'Tidak ada baris yang bisa diimpor.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $body = "{$result['inserted']} data baru, {$result['updated']} diperbarui.";
        if ($result['skipped'] > 0) {
            $body .= " {$result['skipped']} baris dilewati (pernah diedit manual).";
        }
        if (($result['tanpa_surat'] ?? 0) > 0) {
            $body .= " {$result['tanpa_surat']} baris dilewati (No. Surat kosong).";
        }
        $body .= ' Data lama yang tidak ada di file tetap tersimpan.';

        Notification::make()
            ->title('Impor selesai')
            ->body($body)
            ->success()
            ->persistent()
            ->send();

        $this->form->fill();
    }
}
