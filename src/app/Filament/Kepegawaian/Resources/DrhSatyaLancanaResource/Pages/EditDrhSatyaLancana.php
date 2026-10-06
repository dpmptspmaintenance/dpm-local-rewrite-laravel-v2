<?php

namespace App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource\Pages;

use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource;
use App\Models\Kepegawaian\DrhSatyaLancana;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDrhSatyaLancana extends EditRecord
{
    protected static string $resource = DrhSatyaLancanaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Berkas lampiran diambil dari raw state form (dehydrated(false)) dan
     * disimpan setelah simpan — upload ulang jenis yang sama mengganti.
     */
    protected function afterSave(): void
    {
        /** @var DrhSatyaLancana $record */
        $record = $this->getRecord();

        DrhSatyaLancanaResource::simpanBerkas($record, $this->form->getRawState());
    }

    /**
     * tanggal_ditetapkan tersimpan sebagai teks "30 September 2026" (format
     * dokumen), bukan tanggal murni — dikonversi ke "Y-m-d" di sini SEBELUM
     * form di-fill supaya DatePicker menampilkan tanggal yang benar (pola
     * sama EditSuratTugas::mutateFormDataBeforeFill()).
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (filled($data['tanggal_ditetapkan'] ?? null)) {
            try {
                $data['tanggal_ditetapkan'] = \Illuminate\Support\Carbon::createFromLocaleFormat(
                    'j F Y',
                    'id',
                    $data['tanggal_ditetapkan'],
                )->format('Y-m-d');
            } catch (\Throwable $e) {
                $data['tanggal_ditetapkan'] = null;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
