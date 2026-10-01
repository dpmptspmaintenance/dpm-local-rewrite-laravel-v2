<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource;
use Filament\Resources\Pages\EditRecord;

class EditPegawaiPenghargaan extends EditRecord
{
    protected static string $resource = PegawaiPenghargaanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
