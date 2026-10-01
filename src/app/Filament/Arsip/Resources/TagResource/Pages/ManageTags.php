<?php

namespace App\Filament\Arsip\Resources\TagResource\Pages;

use App\Filament\Arsip\Resources\TagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Tag'),
        ];
    }
}
