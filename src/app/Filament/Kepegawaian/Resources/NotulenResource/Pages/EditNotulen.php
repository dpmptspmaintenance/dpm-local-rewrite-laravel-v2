<?php

namespace App\Filament\Kepegawaian\Resources\NotulenResource\Pages;

use App\Filament\Kepegawaian\Resources\NotulenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditNotulen extends EditRecord
{
    protected static string $resource = NotulenResource::class;

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
}
