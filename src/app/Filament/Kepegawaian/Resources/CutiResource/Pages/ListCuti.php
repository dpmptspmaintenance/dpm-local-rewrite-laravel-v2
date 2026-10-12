<?php

namespace App\Filament\Kepegawaian\Resources\CutiResource\Pages;

use App\Filament\Kepegawaian\Resources\CutiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCuti extends ListRecords
{
    protected static string $resource = CutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Cuti'),
        ];
    }
}
