<?php

namespace App\Filament\Kepegawaian\Resources\CutiResource\Pages;

use App\Filament\Kepegawaian\Pages\ImportData;
use App\Filament\Kepegawaian\Resources\CutiResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCuti extends ListRecords
{
    protected static string $resource = CutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Impor Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(ImportData::getUrl(['tab' => 'cuti'])),
            CreateAction::make()->label('Tambah Cuti'),
        ];
    }
}
