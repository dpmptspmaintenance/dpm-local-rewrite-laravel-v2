<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Filament\Kepegawaian\Widgets\PegawaiStatusOverview;
use Filament\Resources\Pages\ListRecords;

class ListPegawaiProfil extends ListRecords
{
    protected static string $resource = PegawaiProfilResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PegawaiStatusOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
