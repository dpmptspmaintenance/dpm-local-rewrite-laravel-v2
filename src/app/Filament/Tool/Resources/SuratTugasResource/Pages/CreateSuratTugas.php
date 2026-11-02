<?php

namespace App\Filament\Tool\Resources\SuratTugasResource\Pages;

use App\Filament\Tool\Resources\SuratTugasResource;
use App\Models\Kepegawaian\SuratTugasDasarHukumSetting;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSuratTugas extends CreateRecord
{
    protected static string $resource = SuratTugasResource::class;

    /**
     * Snapshot dasar hukum Setting default PADA SAAT surat dibuat (bukan
     * live-join) — supaya kalau Setting diedit belakangan, riwayat surat
     * lama tetap menunjukkan dasar hukum yang berlaku saat itu dibuat.
     * dasar_hukum_tambahan (field form) disimpan apa adanya di kolom
     * terpisah dan TIDAK ikut dicampur ke snapshot ini.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Gabung waktu_mulai + waktu_selesai jadi teks `waktu` untuk template.
        // Centang "Selesai" berarti tidak ada jam akhir (waktu_selesai kosong).
        $data['waktu'] = SuratTugasResource::composeWaktu(
            $data['waktu_mulai'] ?? null,
            ($data['selesai_tanpa_jam'] ?? false) ? 'selesai' : ($data['waktu_selesai'] ?? null),
        );
        unset($data['waktu_mulai'], $data['waktu_selesai'], $data['selesai_tanpa_jam']);

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
