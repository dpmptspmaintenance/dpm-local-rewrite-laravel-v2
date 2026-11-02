<?php

namespace App\Filament\Kepegawaian\Resources\SatpamResource\Pages;

use App\Filament\Kepegawaian\Resources\SatpamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSatpam extends ListRecords
{
    protected static string $resource = SatpamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Satpam'),
        ];
    }
}
