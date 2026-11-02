<?php

namespace App\Filament\Kepegawaian\Resources\SatpamResource\RelationManagers;

use App\Filament\Kepegawaian\Resources\SatpamResource;
use App\Models\Kepegawaian\SatpamArsip;
use App\Services\Kepegawaian\SatpamArsipService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Tab "Berkas" di halaman ubah satpam — berkas khusus (KTA/KK/KTP/Ijazah,
 * satu file per jenis) + berkas lainnya (bebas multiple). Lingkungan
 * tersendiri: tanpa judul/kategori, hanya terlihat di modul satpam
 * (tidak masuk pencarian modul arsip mana pun).
 *
 * Upload di sini TIDAK lewat CreateAction bawaan Filament (yang langsung
 * Eloquent create) — perlu Action custom karena upload harus ke disk dulu
 * (SatpamResource::saveBerkas(), sama logic dipakai halaman tambah).
 */
class ArsipRelationManager extends RelationManager
{
    protected static string $relationship = 'arsip';

    protected static ?string $title = 'Berkas';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-archive-box';

    /**
     * Nama file yang sudah tersimpan untuk satu jenis (ditampilkan sebagai
     * petunjuk di slot unggah) — null bila slot masih kosong.
     */
    private function namaBerkasJenis(string $jenis): ?string
    {
        return $this->getOwnerRecord()->berkasJenis($jenis)?->original_filename;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_filename')
            ->headerActions([
                Action::make('kelola')
                    ->label('Kelola Berkas')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        FileUpload::make('kta_file')
                            ->label('KTA')
                            ->helperText(fn (): string => $this->namaBerkasJenis('KTA') !== null
                                ? 'Saat ini: '.$this->namaBerkasJenis('KTA').' — unggah untuk mengganti.'
                                : 'Belum ada — unggah 1 file.')
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb')),
                        FileUpload::make('kk_file')
                            ->label('KK')
                            ->helperText(fn (): string => $this->namaBerkasJenis('KK') !== null
                                ? 'Saat ini: '.$this->namaBerkasJenis('KK').' — unggah untuk mengganti.'
                                : 'Belum ada — unggah 1 file.')
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb')),
                        FileUpload::make('ktp_file')
                            ->label('KTP')
                            ->helperText(fn (): string => $this->namaBerkasJenis('KTP') !== null
                                ? 'Saat ini: '.$this->namaBerkasJenis('KTP').' — unggah untuk mengganti.'
                                : 'Belum ada — unggah 1 file.')
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb')),
                        FileUpload::make('ijazah_file')
                            ->label('Ijazah')
                            ->helperText(fn (): string => $this->namaBerkasJenis('Ijazah') !== null
                                ? 'Saat ini: '.$this->namaBerkasJenis('Ijazah').' — unggah untuk mengganti.'
                                : 'Belum ada — unggah 1 file.')
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb')),
                        FileUpload::make('lainnya_files')
                            ->label('Lainnya')
                            ->helperText(fn (): string => 'Bisa unggah banyak sekaligus. Maks '.config('arsip.max_upload_kb').' KB per berkas.')
                            ->multiple()
                            ->storeFiles(false)
                            ->acceptedFileTypes(self::acceptedMimeTypes())
                            ->maxSize(config('arsip.max_upload_kb'))
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        SatpamResource::saveBerkas(
                            (int) $this->getOwnerRecord()->getKey(),
                            [
                                'KTA' => $data['kta_file'] ?? null,
                                'KK' => $data['kk_file'] ?? null,
                                'KTP' => $data['ktp_file'] ?? null,
                                'Ijazah' => $data['ijazah_file'] ?? null,
                            ],
                            $data['lainnya_files'] ?? [],
                        );
                    }),
            ])
            ->columns([
                TextColumn::make('kategori')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => $state === SatpamArsipService::JENIS_LAINNYA ? 'gray' : 'primary'),
                TextColumn::make('original_filename')
                    ->label('Nama Berkas')
                    ->wrap()
                    ->weight('medium')
                    ->searchable()
                    ->placeholder('—'),
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
                    ->label('Jenis')
                    ->options([
                        SatpamArsipService::JENIS_KTA => 'KTA',
                        SatpamArsipService::JENIS_KK => 'KK',
                        SatpamArsipService::JENIS_KTP => 'KTP',
                        SatpamArsipService::JENIS_IJAZAH => 'Ijazah',
                        SatpamArsipService::JENIS_LAINNYA => 'Lainnya',
                    ]),
            ])
            ->recordActions([
                Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (SatpamArsip $record): string => $record->openUrl())
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->using(fn (SatpamArsip $record) => app(SatpamArsipService::class)->delete($record)),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada berkas untuk satpam ini');
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
