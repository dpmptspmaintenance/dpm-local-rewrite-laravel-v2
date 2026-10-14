<?php

namespace App\Filament\Kepegawaian\Resources\SuratPengantarResource\Pages;

use App\Filament\Kepegawaian\Resources\SuratPengantarResource;
use App\Models\Kepegawaian\SuratTugasDasarHukumSetting;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateSuratPengantar extends CreateRecord
{
    protected static string $resource = SuratPengantarResource::class;

    /**
     * Snapshot field pegawai tunggal yang di-unset dari data record utama
     * sebelum disimpan sebagai baris surat_pengantar_pegawai.
     *
     * @var array<string, ?string>
     */
    private array $pegawaiSnapshot = [];

    /**
     * Pegawai diusulkan kini field TUNGGAL di form (bukan Repeater) — kolom
     * nip/nama/jabatan/pangkat_golongan/instansi bukan milik tabel
     * surat_pengantar, jadi dilepas dari record utama dan disimpan manual
     * sebagai satu baris hasMany surat_pengantar_pegawai.
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

        foreach (['nip', 'nama', 'jabatan', 'pangkat_golongan', 'instansi'] as $field) {
            $this->pegawaiSnapshot[$field] = $data[$field] ?? null;
            unset($data[$field]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        $record->pegawai()->create([
            ...$this->pegawaiSnapshot,
            'urutan' => 0,
        ]);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
