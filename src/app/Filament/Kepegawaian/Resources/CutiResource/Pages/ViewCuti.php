<?php

namespace App\Filament\Kepegawaian\Resources\CutiResource\Pages;

use App\Filament\Kepegawaian\Resources\CutiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCuti extends ViewRecord
{
    protected static string $resource = CutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
