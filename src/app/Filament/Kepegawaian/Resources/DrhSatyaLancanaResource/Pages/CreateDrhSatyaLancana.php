<?php

namespace App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource\Pages;

use App\Filament\Kepegawaian\Resources\DrhSatyaLancanaResource;
use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDrhSatyaLancana extends CreateRecord
{
    protected static string $resource = DrhSatyaLancanaResource::class;

    /**
     * Auto-isi dari pegawai saat halaman dibuka dengan ?nip=... (dari tombol
     * "Buat DRH" di halaman Rekap Penghargaan). Form di-fill dengan snapshot
     * profil — sama seperti afterStateUpdated memanggil fillFromProfil().
     */
    public function mount(): void
    {
        parent::mount();

        $nip = request()->query('nip');

        if (blank($nip) || ! PegawaiProfil::query()->whereKey($nip)->exists()) {
            return;
        }

        $data = DrhSatyaLancanaResource::fillFromProfil($nip);

        $this->form->fill(array_filter(
            $data,
            fn ($value) => filled($value),
        ));
    }

    /**
     * Kalau nama pegawai tak tersentuh (form di-submit tanpa auto-isi jalan,
     * mis. lewat test), pastikan snapshot minimum terisi dari profil — supaya
     * dokumen tak pernah punya nama kosong. Juga catat pembuatnya.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $data['dibuat_oleh'] = $user?->id;
        $data['dibuat_oleh_nama'] = $user?->nama ?? $user?->name;

        return $data;
    }

    /**
     * Berkas lampiran (FileUpload storeFiles(false), dehydrated(false)) tak
     * ikut $data create — diambil dari raw state form dan disimpan setelah
     * record DRH punya id.
     */
    protected function afterCreate(): void
    {
        /** @var DrhSatyaLancana $record */
        $record = $this->getRecord();

        DrhSatyaLancanaResource::simpanBerkas($record, $this->form->getRawState());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
