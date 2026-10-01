<?php

namespace App\Filament\Arsip\Resources\DocumentResource\Pages;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Document;
use App\Services\ArsipDigital\DocumentService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => Auth::user()->isArsipAdmin()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tag_names'] = $this->getRecord()->tags()->pluck('name')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Tag & berkas baru disimpan lewat afterSave() (butuh sync pivot /
        // panggilan DocumentService), bukan kolom langsung pada model documents.
        unset(
            $data['tag_names'],
            $data['files'],
            $data['new_files'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $tagNames = $this->data['tag_names'] ?? [];

        $ids = collect($tagNames)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name): int => \App\Models\Tag::resolve($name)->id)
            ->values()
            ->all();

        $record->tags()->sync($ids);

        $service = app(DocumentService::class);

        // Judul berubah → rename folder "YYYY-MM-DD - Judul" di Drive supaya
        // mengikuti (permintaan user). Nama file fisik tidak diubah.
        if ($record->wasChanged('title')) {
            $service->syncDriveNames($record);
        }

        // Admin boleh mengubah status langsung dari halaman Edit (bukan hanya
        // lewat ReviewQueue). Kalau baru saja diubah ke published dan folder
        // Drive belum ada, jalankan penataan yang sama seperti Approve.
        if ($record->wasChanged('status')
            && $record->status === Document::STATUS_PUBLISHED) {
            $service->organizeOnPublish($record);
        }

        // Berkas baru yang dipilih lewat field "Tambah Berkas" — diunggah
        // sekarang (bukan sebelum record tersimpan, supaya kalau upload
        // gagal, sisa perubahan form tetap tersimpan).
        $newFiles = $this->data['new_files'] ?? [];

        if ($newFiles !== []) {
            $meta = collect($newFiles)
                ->map(fn ($file): array => [
                    'path' => $file->getRealPath(),
                    'filename' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                    'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
                    'size' => (int) $file->getSize(),
                ])
                ->all();

            try {
                $service->addFiles($record, $meta);

                Notification::make()
                    ->title(count($meta).' berkas berhasil ditambahkan')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Sebagian/semua berkas baru gagal ditambahkan')
                    ->body($e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();
            }

            // Reset field upload supaya tidak ke-submit ulang bila user
            // menyimpan form lagi tanpa refresh halaman.
            $this->data['new_files'] = [];
        }
    }

    /**
     * Dipanggil dari tombol hapus di filament.arsip.forms.file-list
     * (wire:click="removeDocumentFile('...')"). Menghapus SATU berkas
     * (baris document_files) dari dokumen ini.
     */
    public function removeDocumentFile(int $fileId): void
    {
        $record = $this->getRecord();

        if (! Auth::user()->isArsipAdmin() && $record->created_by !== Auth::id()) {
            Notification::make()->title('Tidak diizinkan')->danger()->send();

            return;
        }

        $file = $record->files()->find($fileId);

        if (! $file) {
            Notification::make()->title('Berkas tidak ditemukan')->danger()->send();

            return;
        }

        app(DocumentService::class)->removeFile($file);

        Notification::make()->title('Berkas dihapus')->success()->send();

        $record->refresh();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
