<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use App\Filament\Kepegawaian\Resources\PegawaiArsipResource;
use App\Models\Kepegawaian\PegawaiArsip;
use App\Services\Kepegawaian\PegawaiArsipService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Tab "Arsip" di halaman detail pegawai — upload/lihat/hapus berkas arsip
 * KHUSUS pegawai ini (SKP, SK Kenaikan Pangkat, SK Jabatan, Foto, dll).
 * Untuk browse lintas pegawai sekaligus, pakai PegawaiArsipResource
 * (/kepegawaian/arsip-pegawai).
 *
 * Upload di sini TIDAK lewat CreateAction bawaan Filament (yang langsung
 * Eloquent create) — perlu Action custom karena upload harus ke Google Drive
 * dulu (PegawaiArsipResource::uploadBatch(), sama logic dipakai halaman list).
 */
class ArsipRelationManager extends RelationManager
{
    protected static string $relationship = 'arsip';

    protected static ?string $title = 'Arsip';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-archive-box';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('judul')
            ->headerActions([
                Action::make('unggah')
                    ->label('Tambah Arsip')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Berkas')
                            ->helperText(fn (): string => 'Bisa unggah banyak sekaligus. Maks '.config('arsip.max_upload_kb').' KB per berkas.')
                            ->multiple()
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb'))
                            ->required(),
                        TextInput::make('judul')
                            ->label('Judul')
                            ->helperText('Kosong = nama file. Diabaikan bila unggah > 1 berkas sekaligus.')
                            ->maxLength(255),
                        TextInput::make('kategori')
                            ->label('Kategori')
                            ->helperText('Bebas ketik — mis. SKP, SK Kenaikan Pangkat, SK Jabatan, Foto, Ijazah.')
                            ->datalist(fn (): array => app(PegawaiArsipService::class)->kategoriSuggestions())
                            ->maxLength(100),
                    ])
                    ->action(function (array $data): void {
                        PegawaiArsipResource::uploadBatch(
                            $this->getOwnerRecord()->nip,
                            $data['files'] ?? [],
                            $data['judul'] ?? null,
                            $data['kategori'] ?? null,
                        );
                    }),
            ])
            ->columns([
                TextColumn::make('judul')
                    ->label('Judul')
                    ->wrap()
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('original_filename')
                    ->label('Nama Berkas')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('uploaded_by_nama')
                    ->label('Diunggah Oleh')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Tgl Unggah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(fn (): array => $this->getOwnerRecord()->arsip()
                        ->whereNotNull('kategori')
                        ->distinct()
                        ->pluck('kategori', 'kategori')
                        ->all()),
            ])
            ->recordActions([
                Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (PegawaiArsip $record): string => $record->openUrl())
                    ->openUrlInNewTab(),
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->fillForm(fn (PegawaiArsip $record): array => [
                        'judul' => $record->judul,
                        'kategori' => $record->kategori,
                    ])
                    ->schema([
                        TextInput::make('judul')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('kategori')
                            ->label('Kategori')
                            ->helperText('Bebas ketik — mis. SKP, SK Kenaikan Pangkat, SK Jabatan, Foto, Ijazah.')
                            ->datalist(fn (): array => app(PegawaiArsipService::class)->kategoriSuggestions())
                            ->maxLength(100),
                        FileUpload::make('berkas')
                            ->label('Ganti Berkas (opsional)')
                            ->helperText('Biarkan kosong bila hanya ingin mengubah judul/kategori.')
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb')),
                    ])
                    ->action(function (PegawaiArsip $record, array $data): void {
                        $service = app(PegawaiArsipService::class);

                        $record->update([
                            'judul' => $data['judul'],
                            'kategori' => filled($data['kategori'] ?? null) ? trim($data['kategori']) : null,
                        ]);

                        $file = $data['berkas'] ?? null;

                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            $service->replaceFile($record, [
                                'path' => $file->getRealPath(),
                                'filename' => $file->getClientOriginalName(),
                                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                                'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
                                'size' => (int) $file->getSize(),
                            ]);
                        }

                        Notification::make()
                            ->title('Arsip diperbarui')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->using(fn (PegawaiArsip $record) => app(PegawaiArsipService::class)->delete($record)),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada arsip untuk pegawai ini');
    }

    private static function acceptedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/x-zip-compressed',
        ];
    }
}
