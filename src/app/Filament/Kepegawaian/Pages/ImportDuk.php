<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Services\Kepegawaian\DukImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * Impor DUK (PDF) sebagai page tersendiri di grup Master. Tiap impor disimpan
 * sebagai batch baru (riwayat tetap ada) — lihat DukPage untuk melihat daftar
 * dan memilih periode upload.
 */
class ImportDuk extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Impor DUK';

    protected static string | \UnitEnum | null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Impor DUK (PDF)';

    protected static ?string $slug = 'impor-duk';

    protected string $view = 'filament.kepegawaian.pages.import-duk';

    public ?array $data = [];

    public bool $sukses = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Berkas PDF DUK')
                    ->description('Unggah PDF DAFTAR URUT KEPANGKATAN. Tiap impor disimpan sebagai data upload baru (riwayat tetap ada). Masa kerja diambil apa adanya dari dokumen.')
                    ->schema([
                        FileUpload::make('duk_file')
                            ->label('Berkas PDF DUK')
                            ->acceptedFileTypes(['application/pdf'])
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
                ->label('Kembali ke DUK')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(DukPage::getUrl()),
        ];
    }

    public function import(): void
    {
        $this->sukses = false;

        $upload = $this->form->getState()['duk_file'] ?? null;

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

        $this->sukses = true;

        Notification::make()
            ->title('Impor selesai')
            ->body("{$result['count']} baris dimuat dari PDF"
                .($result['opd'] ? " — {$result['opd']}" : '')
                .'. Tersimpan sebagai data upload baru.')
            ->success()
            ->persistent()
            ->send();

        $this->form->fill();
    }
}
