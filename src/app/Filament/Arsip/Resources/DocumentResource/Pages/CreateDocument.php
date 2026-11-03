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
        $description = $data['description'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        $ownershipId = $data['ownership_id'] ?? null;
        $tagNames = $data['tag_names'] ?? [];

        $status = ($data['status'] ?? null) && \Illuminate\Support\Facades\Auth::user()->isArsipAdmin()
            ? (string) $data['status']
            : \App\Models\Document::STATUS_PENDING;

        // Berkas fisik (multiple) + tautan berjudul (multiple) dalam SATU
        // dokumen — boleh salah satu saja atau dua-duanya sekaligus.
        $files = DocumentResource::normalizeUploadedFiles($data['berkas'] ?? []);
        $tautan = collect($data['tautan'] ?? [])
            ->map(fn ($row): array => [
                'url' => trim((string) ($row['url'] ?? '')),
                'judul' => trim((string) ($row['judul'] ?? '')) ?: null,
            ])
            ->filter(fn (array $row): bool => $row['url'] !== '')
            ->unique('url')
            ->take(100)
            ->values()
            ->all();

        if ($files === [] && $tautan === []) {
            // Tanpa berkas/tautan: dokumen boleh dibuat tanpa lampiran
            // asalkan judul diisi (validasi form sudah memaksa).
            if (blank($title)) {
                throw new \RuntimeException('Judul dokumen wajib diisi bila tidak ada berkas/tautan.');
            }

            $document = \App\Models\Document::create([
                'title' => $title,
                'description' => $description,
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

        $document = $service->createWithFilesAndLinks($files, $tautan, \Illuminate\Support\Facades\Auth::user(), $categoryId, $title, $tagNames, $ownershipId, $status, $description);

        // Terbit langsung: rapikan folder (rename "YYYY-MM-DD - Judul").
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
