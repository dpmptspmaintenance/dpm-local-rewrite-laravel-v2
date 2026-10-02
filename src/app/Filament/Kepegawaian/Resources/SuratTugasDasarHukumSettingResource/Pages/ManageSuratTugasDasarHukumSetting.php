<?php

namespace App\Filament\Kepegawaian\Resources\SuratTugasDasarHukumSettingResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratTugasDasarHukumSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuratTugasDasarHukumSetting extends ManageRecords
{
    protected static string $resource = SuratTugasDasarHukumSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Dasar Hukum'),
        ];
    }
}
