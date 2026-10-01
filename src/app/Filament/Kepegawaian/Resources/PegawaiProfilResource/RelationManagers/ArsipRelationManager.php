<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use App\Filament\Kepegawaian\Resources\PegawaiArsipResource;
use App\Models\Kepegawaian\PegawaiArsip;
use App\Services\Kepegawaian\PegawaiArsipService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unggah')
                ->label('Unggah Arsip')
                ->icon('heroicon-o-arrow-up-tray')
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
                    Select::make('kategori')
                        ->label('Kategori')
                        ->helperText('Bebas ketik — mis. SKP, SK Kenaikan Pangkat, SK Jabatan, Foto, Ijazah.')
                        ->options(fn (): array => collect(app(PegawaiArsipService::class)->kategoriSuggestions())
                            ->mapWithKeys(fn (string $k): array => [$k => $k])
                            ->all())
                        ->searchable()
                        ->createOptionUsing(fn (string $value): string => $value)
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    PegawaiArsipResource::uploadBatch(
                        $this->getOwnerRecord()->nip,
                        $data['files'] ?? [],
                        $data['judul'] ?? null,
                        $data['kategori'] ?? null,
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('judul')
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
