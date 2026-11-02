<?php

namespace App\Filament\Arsip\Resources\DocumentResource\Pages;

use App\Filament\Arsip\Resources\DocumentResource;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Services\ArsipDigital\LocalArsipStorage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadZip')
                ->label('Unduh ZIP')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->visible(function (): bool {
                    $record = $this->getRecord();

                    return $record instanceof Document && ! $record->isUrl() && $record->files()->exists();
                })
                ->modalHeading('Unduh Berkas Terpilih')
                ->modalDescription('Centang berkas yang ingin diunduh. Semua berkas dibungkus dalam satu arsip ZIP.')
                ->modalSubmitActionLabel('Unduh')
                ->schema([
                    CheckboxList::make('file_ids')
                        ->label('Berkas')
                        ->options(function (): array {
                            $record = $this->getRecord();

                            if (! $record instanceof Document) {
                                return [];
                            }

                            return $record->files()
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn(DocumentFile $file): array => [
                                    $file->id => $file->original_filename ?: basename((string) $file->storage_path),
                                ])
                                ->all();
                        })
                        ->default(function (): array {
                            $record = $this->getRecord();

                            if (! $record instanceof Document) {
                                return [];
                            }

                            return $record->files()->orderBy('id')->pluck('id')->all();
                        })
                        ->columns(1)
                        ->required(),
                ])
                ->action(function (array $data): BinaryFileResponse {
                    $record = $this->getRecord();
                    abort_unless($record instanceof Document, 404);

                    return $this->downloadSelectedFiles($record, (array) $data['file_ids']);
                }),
            EditAction::make()->visible(fn(Document $record): bool => DocumentResource::canEdit($record)),
        ];
    }

    /**
     * Bungkus berkas terpilih jadi satu ZIP. Berkas yang hilang dari disk
     * dilewati; bila tidak ada satu pun yang bisa diunduh, buat notifikasi
     * peringatan daripada mengirim ZIP kosong.
     *
     * @param  list<int|string>  $fileIds
     */
    private function downloadSelectedFiles(Document $record, array $fileIds): BinaryFileResponse
    {
        $storage = app(LocalArsipStorage::class);

        $files = $record->files()
            ->whereIn('id', array_map('intval', $fileIds))
            ->orderBy('id')
            ->get();

        abort_if($files->isEmpty(), 404);

        $tmp = tempnam(sys_get_temp_dir(), 'arsip-zip-');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $used = [];
        $missing = 0;

        foreach ($files as $file) {
            $path = (string) $file->storage_path;

            if ($path === '' || ! $storage->exists($path)) {
                $missing++;

                continue;
            }

            $name = $file->original_filename ?: basename($path);
            $name = $this->uniqueZipName($name, $used);
            $used[] = $name;

            $zip->addFile($storage->absolutePath($path), $name);
        }

        $zip->close();

        if ($used === []) {
            @unlink($tmp);

            Notification::make()
                ->title('Berkas tidak ditemukan di penyimpanan')
                ->danger()
                ->send();

            throw new \Filament\Support\Exceptions\Halt;
        }

        if ($missing > 0) {
            Notification::make()
                ->title($missing . ' berkas tidak ditemukan dan dilewati')
                ->warning()
                ->send();
        }

        $slug = Str::slug(Str::limit((string) $record->title, 60, '')) ?: 'dokumen-' . $record->getKey();
        $filename = "{$slug}-" . now()->format('Ymd-His') . '.zip';

        return response()
            ->download($tmp, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Nama unik di dalam ZIP — bentrok nama berkas diberi akhiran angka
     * tanpa merusak ekstensi.
     *
     * @param  list<string>  $used
     */
    private function uniqueZipName(string $name, array $used): string
    {
        if (! in_array($name, $used, true)) {
            return $name;
        }

        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $suffix = 1;

        do {
            $candidate = $base . " ({$suffix})" . ($extension !== '' ? ".{$extension}" : '');
            $suffix++;
        } while (in_array($candidate, $used, true));

        return $candidate;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Section::make('Deskripsi')
                    ->columnSpanFull()
                    ->visible(fn(Document $record): bool => filled($record->description))
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->prose(),
                    ]),

                Section::make('Detail')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('title')->label('Judul'),
                        TextEntry::make('source_type')
                            ->label('Sumber')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => Document::SOURCES[$state] ?? $state)
                            ->color(fn(string $state): string => $state === Document::SOURCE_URL ? 'info' : 'gray'),
                        TextEntry::make('ownership.name')
                            ->label('Ownership')
                            ->badge()
                            ->placeholder('— (Umum / Semua Unit)')
                            ->color(fn(?string $state): string => filled($state) ? 'info' : 'gray'),
                        TextEntry::make('category.name')->label('Kategori')->placeholder('—'),
                        TextEntry::make('tags.name')->label('Tag')->badge()->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => Document::STATUSES[$state] ?? $state)
                            ->color(fn(string $state): string => match ($state) {
                                Document::STATUS_PENDING => 'warning',
                                Document::STATUS_PUBLISHED => 'success',
                                Document::STATUS_REJECTED => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('rejection_reason')
                            ->label('Alasan Ditolak')
                            ->visible(fn(Document $record): bool => filled($record->rejection_reason)),
                        TextEntry::make('source_url')
                            ->label('Tautan')
                            ->url(fn(Document $record): ?string => $record->isUrl() ? $record->source_url : null)
                            ->openUrlInNewTab()
                            ->visible(fn(Document $record): bool => $record->isUrl())
                            ->limit(60),
                        TextEntry::make('drive_folder_name')
                            ->label('Folder Penyimpanan')
                            ->placeholder('— (belum diorganisasi)')
                            ->visible(fn(Document $record): bool => ! $record->isUrl()),
                        TextEntry::make('creator.nama')->label('Diunggah Oleh')->placeholder('—'),
                        TextEntry::make('verifier.nama')->label('Diverifikasi Oleh')->placeholder('—'),
                        TextEntry::make('verified_at')->label('Tgl Verifikasi')->dateTime('d M Y H:i')->placeholder('—'),
                        TextEntry::make('created_at')->label('Tgl Unggah')->dateTime('d M Y H:i'),
                    ]),

                Section::make('Berkas')
                    // Mengambil sisa 2 kolom berdampingan dengan Detail
                    ->columnSpan(3)
                    ->visible(fn(Document $record): bool => ! $record->isUrl())
                    ->schema([
                        View::make('filament.arsip.entries.file-cards'),
                    ]),

                Section::make('Tautan')
                    ->columnSpan(2)
                    ->visible(fn(Document $record): bool => $record->isUrl())
                    ->schema([
                        TextEntry::make('source_url')
                            ->hiddenLabel()
                            ->formatStateUsing(fn() => 'Dokumen ini berupa tautan. Buka tautannya di tab baru dari kolom "Tautan" di kiri.'),
                    ]),
            ]);
    }
}
