<?php

namespace App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuratPengantar extends ListRecords
{
    protected static string $resource = SuratPengantarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Surat Pengantar'),
        ];
    }
}
