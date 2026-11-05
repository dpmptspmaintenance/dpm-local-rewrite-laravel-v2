<?php

namespace App\Filament\Tool\Resources\NotulenResource\Pages;

use App\Filament\Tool\Resources\NotulenResource;
use App\Services\Kepegawaian\NotulenDocumentService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditNotulen extends EditRecord
{
    protected static string $resource = NotulenResource::class;

    /** @var list<string> */
    private array $gambarLama = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        [$mulai, $selesai] = NotulenResource::parseWaktu($data['waktu'] ?? null);
        $data['waktu_mulai'] = $mulai;
        $data['waktu_selesai'] = $selesai;

        if (filled($data['hari_tanggal'] ?? null)) {
            $data['hari_tanggal'] = $this->parseIndonesianDate($data['hari_tanggal'], 'l, j F Y');
        }

        if (filled($data['tanggal_naskah'] ?? null)) {
            $tanggalText = trim(str($data['tanggal_naskah'])->after(',')->toString());
            $data['tanggal_naskah'] = $this->parseIndonesianDate($tanggalText, 'j F Y');
        }

        return $data;
    }

    private function parseIndonesianDate(string $text, string $format): ?string
    {
        try {
            return Carbon::createFromLocaleFormat($format, 'id', $text)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['waktu'] = NotulenResource::composeWaktu($data['waktu_mulai'] ?? null, $data['waktu_selesai'] ?? null);
        unset($data['waktu_mulai'], $data['waktu_selesai']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $this->gambarLama = array_values((array) ($this->record->gambar ?? []));
    }

    /**
     * Lampiran gambar hanya terlihat di dokumen setelah snapshot diregenerasi.
     * Regenerate otomatis HANYA saat daftar gambar berubah, supaya koreksi
     * manual di editor Word tidak hilang saat menyimpan perubahan lain.
     */
    protected function afterSave(): void
    {
        $baru = array_values((array) ($this->record->gambar ?? []));

        if ($baru === $this->gambarLama) {
            return;
        }

        try {
            app(NotulenDocumentService::class)->regenerate($this->record, auth()->id());

            Notification::make()
                ->title('Dokumen diperbarui')
                ->body('Lampiran gambar sudah masuk ke dokumen word/PDF.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal memperbarui dokumen')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }
}
