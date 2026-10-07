<?php

namespace App\Filament\Arsip\Resources\OwnershipResource\Pages;

use App\Filament\Arsip\Resources\OwnershipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOwnerships extends ManageRecords
{
    protected static string $resource = OwnershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Ownership'),
        ];
    }
}
