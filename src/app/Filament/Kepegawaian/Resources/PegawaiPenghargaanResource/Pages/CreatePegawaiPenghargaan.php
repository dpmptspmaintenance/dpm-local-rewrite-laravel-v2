<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource;
use App\Models\Kepegawaian\PegawaiPenghargaan;
use Filament\Resources\Pages\CreateRecord;

class CreatePegawaiPenghargaan extends CreateRecord
{
    protected static string $resource = PegawaiPenghargaanResource::class;

    /**
     * Semua penghargaan yang ditambah lewat halaman ini sumbernya manual —
     * baris impor JSON hanya ditulis dari PegawaiImportService.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sumber'] = PegawaiPenghargaan::SUMBER_MANUAL;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
