<?php

namespace App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditSuratPengantar extends EditRecord
{
    protected static string $resource = SuratPengantarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * tempat_tanggal tersimpan sebagai "Semarang, j F Y" — dikonversi ke
     * "Y-m-d" SEBELUM form di-fill; lihat catatan panjang di
     * EditSuratTugas::mutateFormDataBeforeFill() soal kenapa bukan
     * afterStateHydrated().
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Key Select Penandatangan = NIP; snapshot NIP tampil di field
        // ttd_nip_snapshot. Kepala Dinas dipetakan ke key khusus
        // 'kepala-dinas' (NIP-nya tidak ada di PegawaiProfil).
        $nipTersimpan = trim((string) ($data['ttd_nip'] ?? ''));
        $data['ttd_nip_snapshot'] = filled($nipTersimpan) ? $nipTersimpan : null;

        if ($nipTersimpan === '197404131993031002') {
            $data['ttd_nip'] = 'kepala-dinas';
        }

        if (filled($data['tempat_tanggal'] ?? null)) {
            $tanggalText = trim((string) str($data['tempat_tanggal'])->after(','));
            try {
                $data['tempat_tanggal'] = Carbon::createFromLocaleFormat('j F Y', 'id', $tanggalText)->format('Y-m-d');
            } catch (\Throwable $e) {
                $data['tempat_tanggal'] = null;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Key 'kepala-dinas' (Select) disimpan sebagai NIP asli Kepala Dinas.
        if (($data['ttd_nip'] ?? null) === 'kepala-dinas') {
            $data['ttd_nip'] = '197404131993031002';
        }

        if (blank($data['ttd_nip'] ?? null) && filled($data['ttd_nip_snapshot'] ?? null)) {
            $data['ttd_nip'] = $data['ttd_nip_snapshot'];
        }
        unset($data['ttd_nip_snapshot']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
