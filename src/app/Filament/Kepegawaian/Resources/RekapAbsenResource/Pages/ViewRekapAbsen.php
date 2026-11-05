<?php

namespace App\Filament\Kepegawaian\Resources\RekapAbsenResource\Pages;

use App\Filament\Kepegawaian\Resources\RekapAbsenResource;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewRekapAbsen extends ViewRecord
{
    protected static string $resource = RekapAbsenResource::class;

    /**
     * Ringkasan di infolist harus ikut berubah setelah satu baris harian
     * dihapus dari RelationManager.
     */
    #[On('rekap-harian-dihapus')]
    public function segarkanRingkasan(): void
    {
        $this->record->refresh();
    }
}
