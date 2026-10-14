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
        $files = DocumentResource::normalizeUploadedFiles($data['berkas'] ?? []);

        if ($files === []) {
            // Tidak ada berkas fisik: dokumen boleh dibuat tanpa berkas asalkan
            // judul diisi (validasi form sudah memaksa judul wajib saat berkas
            // & tautan sama-sama kosong). Judul jadi identitas dokumen.
            if (blank($title)) {
                throw new \RuntimeException('Judul dokumen wajib diisi bila tidak ada berkas/tautan.');
            }

            $status = ($data['status'] ?? null) && \Illuminate\Support\Facades\Auth::user()->isArsipAdmin()
                ? (string) $data['status']
                : \App\Models\Document::STATUS_PENDING;

            $document = \App\Models\Document::create([
                'title' => $title,
                'source_type' => \App\Models\Document::SOURCE_FILE,
                'status' => $status,
                'category_id' => $categoryId,
                'ownership_id' => $ownershipId,
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            $document->tags()->sync(collect($tagNames)
                ->map(fn (string $name): int => \App\Models\Tag::resolve(trim($name))->id)
                ->filter()
                ->unique()
                ->values()
                ->all());

            return $document;
        }

        // Admin/Superadmin boleh langsung memilih status (mis. "Terbit") saat
        // unggah; staf biasa field status tidak tampil sehingga default
        // Menunggu Verifikasi.
        $status = ($data['status'] ?? null) && \Illuminate\Support\Facades\Auth::user()->isArsipAdmin()
            ? (string) $data['status']
            : \App\Models\Document::STATUS_PENDING;

        $document = $service->createWithFiles($files, \Illuminate\Support\Facades\Auth::user(), $categoryId, $title, $tagNames, $ownershipId, $status);

        // Terbit langsung: rapikan folder Drive (rename "YYYY-MM-DD - Judul").
        if ($status === \App\Models\Document::STATUS_PUBLISHED) {
            $service->organizeOnPublish($document);
        }

        return $document;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
