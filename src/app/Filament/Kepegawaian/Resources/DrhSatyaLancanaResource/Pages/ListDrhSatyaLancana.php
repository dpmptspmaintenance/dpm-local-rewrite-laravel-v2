<?php

namespace App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource\Pages;

use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDrhSatyaLancana extends ListRecords
{
    protected static string $resource = DrhSatyaLancanaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat DRH'),
        ];
    }
}
