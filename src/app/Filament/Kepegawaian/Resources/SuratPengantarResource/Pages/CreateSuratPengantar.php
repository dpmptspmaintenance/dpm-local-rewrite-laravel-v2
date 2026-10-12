<?php

namespace App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource;
use App\Models\Kepegawaian\SuratTugasDasarHukumSetting;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSuratPengantar extends CreateRecord
{
    protected static string $resource = SuratPengantarResource::class;

    /**
     * Snapshot dasar hukum Setting default saat surat dibuat + identitas
     * pembuat. Dasar hukum tambahan disimpan apa adanya di kolom terpisah.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // NIP penandatangan: key Select 'kepala-dinas' -> NIP asli; key NIP
        // pegawai sudah langsung benar; snapshot di field ttd_nip_snapshot.
        if (($data['ttd_nip'] ?? null) === 'kepala-dinas') {
            $data['ttd_nip'] = '197404131993031002';
        }

        if (blank($data['ttd_nip'] ?? null) && filled($data['ttd_nip_snapshot'] ?? null)) {
            $data['ttd_nip'] = $data['ttd_nip_snapshot'];
        }
        unset($data['ttd_nip_snapshot']);

        $data['dasar_hukum_snapshot'] = SuratTugasDasarHukumSetting::query()
            ->orderBy('urutan')
            ->pluck('teks')
            ->implode("\n");

        $user = Auth::user();
        $data['dibuat_oleh'] = $user?->id;
        $data['dibuat_oleh_nama'] = $user?->nama ?? $user?->name;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
