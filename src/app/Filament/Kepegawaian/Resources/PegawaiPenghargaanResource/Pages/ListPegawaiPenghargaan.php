<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiPenghargaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPegawaiPenghargaan extends ListRecords
{
    protected static string $resource = PegawaiPenghargaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Penghargaan'),
        ];
    }
}
