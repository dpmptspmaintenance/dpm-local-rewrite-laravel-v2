<?php

namespace App\Filament\Kepegawaian\Resources\SatpamResource\Pages;

use App\Filament\Kepegawaian\Resources\SatpamResource;
use App\Models\Kepegawaian\Satpam;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateSatpam extends CreateRecord
{
    protected static string $resource = SatpamResource::class;

    /**
     * Buat baris satpam dulu, lalu simpan berkas khususnya (KTA/KK/KTP/
     * Ijazah @1 file + Lainnya multiple). FileUpload + storeFiles(false)
     * menyimpan TemporaryUploadedFile di state — proses upload sungguhan
     * (disk + catat metadata) ditangani di sini, bukan Eloquent create
     * biasa. Berkas boleh kosong semua (bisa dilengkapi lewat tab Berkas).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $satpam = Satpam::create([
            'nama' => trim((string) $data['nama']),
            'nik' => filled($data['nik'] ?? null) ? trim((string) $data['nik']) : null,
            'telepon' => filled($data['telepon'] ?? null) ? trim((string) $data['telepon']) : null,
            'alamat' => filled($data['alamat'] ?? null) ? trim((string) $data['alamat']) : null,
            'keterangan' => filled($data['keterangan'] ?? null) ? trim((string) $data['keterangan']) : null,
            'created_by' => Auth::id(),
        ]);

        $khusus = [
            'KTA' => $data['kta_file'] ?? null,
            'KK' => $data['kk_file'] ?? null,
            'KTP' => $data['ktp_file'] ?? null,
            'Ijazah' => $data['ijazah_file'] ?? null,
        ];

        $hasil = SatpamResource::saveBerkas(
            (int) $satpam->getKey(),
            $khusus,
            $data['lainnya_files'] ?? [],
        );

        if ($hasil['count'] === 0 && $hasil['errors'] === []) {
            Notification::make()
                ->title('Data satpam tersimpan (tanpa berkas)')
                ->body('Berkas bisa dilengkapi lewat tab Berkas.')
                ->success()
                ->send();
        }

        return $satpam;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
