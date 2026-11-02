<?php

namespace App\Filament\User\Resources\UserResource\Pages;

use App\Filament\User\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * status_jabatan/nip: NOT NULL columns with no DB default. Bila select
     * dikosongkan, field dehydrate ke null — paksa '' supaya tak gagal di
     * MySQL strict mode (sama seperti guard di CreateUser).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status_jabatan'] ??= '';
        $data['nip'] ??= '';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
