<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiArsipResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiArsipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPegawaiArsip extends ListRecords
{
    protected static string $resource = PegawaiArsipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Unggah Arsip'),
        ];
    }
}
