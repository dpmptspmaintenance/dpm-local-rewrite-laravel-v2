<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages;

use App\Filament\Kepegawaian\Pages\ImportData;
use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPegawaiProfil extends ListRecords
{
    protected static string $resource = PegawaiProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Impor JSON')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(ImportData::getUrl(['tab' => 'pegawai'])),
        ];
    }
}
