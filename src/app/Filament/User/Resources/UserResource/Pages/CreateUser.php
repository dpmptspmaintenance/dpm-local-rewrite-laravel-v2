<?php

namespace App\Filament\User\Resources\UserResource\Pages;

use App\Filament\User\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * password is a legacy-compat vestige (auth is Google-OAuth-only, see
     * AuthController) — ported verbatim from UserController::addUser()'s
     * md5($email) placeholder, auto-bcrypt-hashed by the model's 'hashed'
     * cast.
     *
     * status_jabatan/nip: NOT NULL columns with no DB default; the form
     * fields aren't required (most new users won't have them yet), but an
     * empty TextInput dehydrates to null, not ''. Force '' here to avoid
     * a hard failure under MySQL strict mode — ported verbatim from
     * UserController::addUser()'s blank placeholders.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = md5($data['email']);
        $data['status_jabatan'] ??= '';
        $data['nip'] ??= '';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
