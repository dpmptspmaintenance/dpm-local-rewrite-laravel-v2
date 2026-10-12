<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Services\Kepegawaian\PegawaiImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Impor Pegawai (JSON snapshot hasil scrape SISDM). Dipisah dari halaman Cuti
 * dan DUK supaya tiap jenis impor punya page sendiri di grup Master.
 *
 * Profil dicocokkan per NIP: sudah ada diperbarui, belum ada ditambahkan.
 * Riwayat anak & kompetensi diganti seluruhnya tiap impor (snapshot).
 */
class ImportPegawai extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Impor Pegawai';

    protected static string | \UnitEnum | null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Impor Pegawai (JSON)';

    protected static ?string $slug = 'impor-pegawai';

    protected string $view = 'filament.kepegawaian.pages.import-pegawai';

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
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Daftar Pegawai')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(PegawaiProfilResource::getUrl('index')),
        ];
    }

    public function import(PegawaiImportService $importer): void
    {
        $this->errors = [];

        $upload = $this->form->getState()['pegawai_file'] ?? null;

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

        $records = array_is_list($decoded ?? []) ? $decoded : [$decoded];

        if (empty($records)) {
            Notification::make()->title('Tidak ada data pegawai ditemukan di berkas.')->danger()->send();

            return;
        }

        $result = $importer->importMany($records);

        $this->errors = $result['errors'];

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

        $this->form->fill();
    }
}
