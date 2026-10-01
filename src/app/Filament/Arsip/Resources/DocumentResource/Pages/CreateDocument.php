<?php

namespace App\Filament\Arsip\Resources\DocumentResource\Pages;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Services\ArsipDigital\DocumentService;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // FileUpload multiple + storeFiles(false) menyimpan array objek
        // TemporaryUploadedFile di state. Logika insert sebenarnya ditangani
        // di handleRecordCreation(), bukan lewat Eloquent create biasa.
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = app(DocumentService::class);
        $title = $data['title'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        $tagNames = $data['tag_names'] ?? [];

        // Cabang mode URL: satu baris per baris teks di textarea `urls`.
        if (($data['source_mode'] ?? \App\Models\Document::SOURCE_FILE) === \App\Models\Document::SOURCE_URL) {
            $urls = collect(preg_split('/\r\n|\r|\n/', (string) ($data['urls'] ?? '')))
                ->map(fn (string $line): string => trim($line))
                ->filter()
                ->unique()
                ->take(100) // batasi supaya form tidak disalahgunakan jadi ribuan baris
                ->values()
                ->all();

            $result = DocumentResource::registerUrlBatch($urls, $title, $categoryId, $tagNames, $service);
        } else {
            $result = DocumentResource::uploadBatch($data['files'] ?? [], $title, $categoryId, $tagNames, $service);
        }

        if ($result['ids'] === []) {
            // Tampilkan error asli yang sudah diringkas uploadBatch, jangan
            // pesan generik — user perlu tahu akar masalahnya (mis. folder
            // Drive belum diatur), bukan cuma "gagal".
            throw new \RuntimeException(
                "Tidak ada dokumen yang berhasil diunggah.\n\n".implode("\n", $result['errors'])
            );
        }

        // Return dokumen pertama supaya Filament punya record untuk redirect
        // ke halaman view/edit standar. Untuk batch, ini memang cuma salah
        // satu dari banyak dokumen; user bisa kembali ke daftar.
        return DocumentResource::getModel()::findOrFail($result['ids'][0]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
