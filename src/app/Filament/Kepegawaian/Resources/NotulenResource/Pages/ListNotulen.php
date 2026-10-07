<?php

namespace App\Filament\Kepegawaian\Resources\NotulenResource\Pages;

use App\Filament\Kepegawaian\Resources\NotulenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotulen extends ListRecords
{
    protected static string $resource = NotulenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Notulen Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}
