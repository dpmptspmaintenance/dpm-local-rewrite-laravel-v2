<?php

namespace App\Filament\Tool\Resources\CertificateFontResource\Pages;

use App\Filament\Tool\Resources\CertificateFontResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCertificateFonts extends ManageRecords
{
    protected static string $resource = CertificateFontResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Font'),
        ];
    }
}
