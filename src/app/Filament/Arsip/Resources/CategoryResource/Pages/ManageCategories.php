<?php

namespace App\Filament\Arsip\Resources\CategoryResource\Pages;

use App\Filament\Arsip\Resources\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Kategori'),
        ];
    }
}
