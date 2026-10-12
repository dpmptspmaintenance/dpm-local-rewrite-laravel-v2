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
        $ownershipId = $data['ownership_id'] ?? null;
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

            $result = DocumentResource::registerUrlBatch($urls, $title, $categoryId, $tagNames, $service, $ownershipId);

            if ($result['ids'] === []) {
                throw new \RuntimeException(
                    "Tidak ada dokumen yang berhasil didaftarkan.\n\n".implode("\n", $result['errors'])
                );
            }

            return DocumentResource::getModel()::findOrFail($result['ids'][0]);
        }

        // Mode berkas: satu DOKUMEN dengan banyak baris berkas (repeater
        // "Tambah Berkas" — konsep sama Surat Tugas: satu induk, banyak anak).
        $files = collect($data['berkas'] ?? [])
            ->pluck('file')
            ->filter()
            ->map(fn ($file): array => [
                'path' => $file->getRealPath(),
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
                'size' => (int) $file->getSize(),
            ])
            ->values()
            ->all();

        if ($files === []) {
            throw new \RuntimeException('Pilih minimal satu berkas untuk diunggah.');
        }

        $document = $service->createWithFiles($files, \Illuminate\Support\Facades\Auth::user(), $categoryId, $title, $tagNames, $ownershipId);

        return $document;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
