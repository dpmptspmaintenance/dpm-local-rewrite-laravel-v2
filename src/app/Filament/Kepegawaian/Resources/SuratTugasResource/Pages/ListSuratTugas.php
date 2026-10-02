<?php

namespace App\Filament\Kepegawaian\Resources\SuratTugasResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratTugasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuratTugas extends ListRecords
{
    protected static string $resource = SuratTugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Surat Tugas'),
        ];
    }
}
