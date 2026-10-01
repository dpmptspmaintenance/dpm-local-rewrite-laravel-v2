<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPegawaiProfil extends ViewRecord
{
    protected static string $resource = PegawaiProfilResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->nama ?? 'Detail Pegawai';
    }

    public function getSubheading(): ?string
    {
        return $this->getRecord()->nip;
    }
}
