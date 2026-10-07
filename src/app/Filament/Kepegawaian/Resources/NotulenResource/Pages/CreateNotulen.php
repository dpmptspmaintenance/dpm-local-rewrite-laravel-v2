<?php

namespace App\Filament\Kepegawaian\Resources\NotulenResource\Pages;

use App\Filament\Kepegawaian\Resources\NotulenResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotulen extends CreateRecord
{
    protected static string $resource = NotulenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['waktu'] = NotulenResource::composeWaktu($data['waktu_mulai'] ?? null, $data['waktu_selesai'] ?? null);
        unset($data['waktu_mulai'], $data['waktu_selesai']);

        $user = Auth::user();
        $data['dibuat_oleh'] = $user?->id;
        $data['dibuat_oleh_nama'] = $user?->nama ?: $user?->name;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
