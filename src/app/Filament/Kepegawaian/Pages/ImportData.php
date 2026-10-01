<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\CutiResource;
use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Services\Kepegawaian\CutiImportService;
use App\Services\Kepegawaian\PegawaiImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\Url;

/**
 * Combined import page: Pegawai (JSON snapshot from SISDM scrape) and Cuti
 * (Excel, upsert by No. Surat). Two tabs, each with its own separate form
 * and backed by its own service.
 */
class ImportData extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Impor Data';

    protected static string | \UnitEnum | null $navigationGroup = 'Tool';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Impor Data';

    protected string $view = 'filament.kepegawaian.pages.import-data';

    #[Url]
    public string $tab = 'pegawai';

    public ?array $pegawaiData = [];

    public ?array $cutiData = [];

    /**
     * Per-record errors from the last pegawai import.
     *
     * @var list<string>
     */
    public array $pegawaiErrors = [];

    /**
     * Per-row notes from the last cuti import (bad dates, unrecoverable NIPs, ...).
     *
     * @var list<string>
     */
    public array $cutiErrors = [];

    public function mount(): void
    {
        if (! in_array($this->tab, ['pegawai', 'cuti'], true)) {
            $this->tab = 'pegawai';
        }

        $this->pegawaiForm->fill();
        $this->cutiForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'pegawaiForm',
            'cutiForm',
        ];
    }

    public function pegawaiForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Berkas JSON')
                    ->description('Satu objek pegawai, atau array berisi banyak objek — format hasil scrape sisdm.semarangkota.go.id/pegawai/{id}.')
                    ->schema([
                        FileUpload::make('pegawai_file')
                            ->label('File (.json)')
                            ->acceptedFileTypes(['application/json', 'text/json', 'text/plain'])
                            ->maxSize(20480)
                            ->required()
                            ->storeFiles(false),
                    ]),
            ])
            ->statePath('pegawaiData');
    }

    public function cutiForm(Schema $schema): Schema
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
            ->statePath('cutiData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backPegawai')
                ->label('Kembali ke Daftar Pegawai')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(PegawaiProfilResource::getUrl('index'))
                ->visible(fn (): bool => $this->tab === 'pegawai'),
            Action::make('backCuti')
                ->label('Kembali ke Daftar Cuti')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(CutiResource::getUrl('index'))
                ->visible(fn (): bool => $this->tab === 'cuti'),
        ];
    }

    public function importPegawai(PegawaiImportService $importer): void
    {
        $this->pegawaiErrors = [];

        $upload = $this->pegawaiForm->getState()['pegawai_file'] ?? null;

        if (! $upload) {
            Notification::make()->title('Pilih berkas terlebih dahulu.')->danger()->send();

            return;
        }

        $decoded = json_decode(file_get_contents($upload->getRealPath()), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Notification::make()
                ->title('Berkas bukan JSON yang valid')
                ->body(json_last_error_msg())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        // Accept either one employee object or a JSON array of them.
        $records = array_is_list($decoded ?? []) ? $decoded : [$decoded];

        if (empty($records)) {
            Notification::make()->title('Tidak ada data pegawai ditemukan di berkas.')->danger()->send();

            return;
        }

        $result = $importer->importMany($records);

        $this->pegawaiErrors = $result['errors'];

        if ($result['created'] === 0 && $result['updated'] === 0) {
            Notification::make()
                ->title('Impor gagal')
                ->body($result['errors'][0] ?? 'Tidak ada data yang bisa diimpor.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Impor selesai')
            ->body("{$result['created']} pegawai baru, {$result['updated']} diperbarui.")
            ->success()
            ->persistent()
            ->send();

        $this->pegawaiForm->fill();
    }

    public function importCuti(CutiImportService $importer): void
    {
        $this->cutiErrors = [];

        $upload = $this->cutiForm->getState()['cuti_file'] ?? null;

        if (! $upload) {
            Notification::make()->title('Pilih berkas terlebih dahulu.')->danger()->send();

            return;
        }

        $result = $importer->replaceAll($upload->getRealPath());

        $this->cutiErrors = $result['errors'];

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

        $this->cutiForm->fill();
    }
}
