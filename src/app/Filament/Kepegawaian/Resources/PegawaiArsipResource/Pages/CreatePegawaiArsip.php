<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiArsipResource\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiArsipResource;
use App\Models\Kepegawaian\PegawaiArsip;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePegawaiArsip extends CreateRecord
{
    protected static string $resource = PegawaiArsipResource::class;

    /**
     * FileUpload multiple + storeFiles(false) menyimpan array objek
     * TemporaryUploadedFile di state — proses upload sungguhan (Drive +
     * catat metadata) ditangani di handleRecordCreation(), bukan Eloquent
     * create biasa. Pola sama seperti App\Filament\Arsip\Resources\DocumentResource\Pages\CreateDocument.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $result = PegawaiArsipResource::uploadBatch(
            $data['nip'],
            $data['files'] ?? [],
            $data['judul'] ?? null,
            $data['kategori'] ?? null,
        );

        if ($result['ids'] === []) {
            throw new \RuntimeException(
                "Tidak ada berkas yang berhasil diunggah.\n\n".implode("\n", $result['errors'])
            );
        }

        return PegawaiArsip::findOrFail($result['ids'][0]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
