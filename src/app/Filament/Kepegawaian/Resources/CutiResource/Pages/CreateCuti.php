<?php

namespace App\Filament\Kepegawaian\Resources\CutiResource\Pages;

use App\Filament\Kepegawaian\Resources\CutiResource;
use App\Models\Kepegawaian\Cuti;
use Filament\Resources\Pages\CreateRecord;

class CreateCuti extends CreateRecord
{
    protected static string $resource = CutiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Cuti::hitungDurasiDariTanggal($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
