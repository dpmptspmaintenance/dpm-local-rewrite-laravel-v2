<?php

namespace App\Filament\Kepegawaian\Resources\CutiResource\Pages;

use App\Filament\Kepegawaian\Resources\CutiResource;
use App\Models\Kepegawaian\Cuti;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCuti extends EditRecord
{
    protected static string $resource = CutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * Tandai baris sebagai hasil koreksi manual — CutiImportService melewati
     * baris ber-flag ini saat upsert by no_surat, jadi edit tak tertimpa
     * import berikutnya.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['diedit_manual'] = true;

        return Cuti::hitungDurasiDariTanggal($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
