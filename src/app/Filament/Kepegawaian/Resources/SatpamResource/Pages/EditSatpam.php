<?php

namespace App\Filament\Kepegawaian\Resources\SatpamResource\Pages;

use App\Filament\Kepegawaian\Resources\SatpamResource;
use App\Models\Kepegawaian\Satpam;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSatpam extends EditRecord
{
    protected static string $resource = SatpamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hapus lewat service supaya berkas disk ikut terhapus.
            DeleteAction::make()
                ->using(function (Satpam $record): void {
                    SatpamResource::deleteWithArsip($record);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
