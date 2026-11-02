<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\SatpamResource\Pages;
use App\Models\Kepegawaian\Satpam;
use App\Services\Kepegawaian\SatpamArsipService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Data Satpam (non-ASN, di luar SISDM/pegawai_profil) + arsip berkasnya
 * (KTA, KTP, Ijazah, dll). Berkas fisik di disk lokal subfolder
 * "Kepegawaian/satpam" — lihat SatpamArsipService.
 */
class SatpamResource extends Resource
{
    protected static ?string $model = Satpam::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Data Satpam';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Satpam';

    protected static ?string $pluralModelLabel = 'Data Satpam';

    protected static ?string $slug = 'data-satpam';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Diri')
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),
                    TextInput::make('nik')
                        ->label('NIK')
                        ->maxLength(50),
                    TextInput::make('telepon')
                        ->label('No. Telepon/HP')
                        ->maxLength(50),
                    TextInput::make('alamat')
                        ->label('Alamat')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Berkas Khusus')
                ->description('Satu file untuk tiap jenis. "Lainnya" boleh banyak file. Tanpa judul/kategori — hanya terlihat di modul satpam.')
                ->columns(2)
                ->schema([
                    FileUpload::make('kta_file')
                        ->label('KTA')
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        ->visibleOn('create'),
                    FileUpload::make('kk_file')
                        ->label('KK')
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        ->visibleOn('create'),
                    FileUpload::make('ktp_file')
                        ->label('KTP')
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        ->visibleOn('create'),
                    FileUpload::make('ijazah_file')
                        ->label('Ijazah')
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        ->visibleOn('create'),
                    FileUpload::make('lainnya_files')
                        ->label('Lainnya')
                        ->helperText(fn (): string => 'Bisa unggah banyak sekaligus. Maks '.config('arsip.max_upload_kb').' KB per berkas.')
                        ->multiple()
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        ->columnSpanFull()
                        ->visibleOn('create'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->description(fn (Satpam $record): ?string => $record->nik)
                    ->searchable(['nama', 'nik'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('telepon')
                    ->label('Telepon')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('kelengkapan')
                    ->label('Kelengkapan')
                    ->state(function (Satpam $record): string {
                        $khusus = 0;

                        foreach (SatpamArsipService::JENIS_KHUSUS as $jenis) {
                            if ($record->arsip()->where('kategori', $jenis)->exists()) {
                                $khusus++;
                            }
                        }

                        $lainnya = $record->arsip()->where('kategori', SatpamArsipService::JENIS_LAINNYA)->count();

                        return $khusus.'/'.count(SatpamArsipService::JENIS_KHUSUS).' khusus + '.$lainnya.' lainnya';
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('arsip_count')
                    ->label('Arsip')
                    ->counts('arsip')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('nama')
            ->recordActions([
                EditAction::make(),
                // Hapus lewat service supaya berkas disk ikut terhapus —
                // jangan pakai DeleteAction bawaan (cuma hapus baris DB,
                // dan FK cascade pun tak menyentuh berkas fisik).
                DeleteAction::make()
                    ->using(function (Satpam $record): void {
                        $service = app(SatpamArsipService::class);

                        foreach ($record->arsip()->get() as $arsip) {
                            $service->delete($arsip);
                        }

                        $record->delete();
                    }),
            ])
            ->emptyStateHeading('Belum ada data satpam')
            ->emptyStateDescription('Tambah manual lewat tombol di atas.');
    }

    public static function getRelations(): array
    {
        return [
            SatpamResource\RelationManagers\ArsipRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSatpam::route('/'),
            'create' => Pages\CreateSatpam::route('/create'),
            'edit' => Pages\EditSatpam::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'nik'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->nama ?? 'Satpam';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'NIK' => $record->nik ?? '—',
            'Arsip' => (string) $record->arsip()->count(),
        ];
    }

    /**
     * Simpan berkas satpam: 4 slot khusus (KTA/KK/KTP/Ijazah, satu file per
     * jenis — upload baru menggantikan yang lama) + "Lainnya" (bebas
     * multiple, selalu tambah). Dipanggil dari CreateSatpam dan modal
     * "Kelola Berkas" di tab Berkas — satu-satunya tempat logic ini ditulis.
     * Kegagalan satu berkas tidak membatalkan berkas lain.
     *
     * @param  array<string, mixed>  $khusus  jenis => upload (KTA, KK, KTP, Ijazah)
     * @param  array<int, mixed>  $lainnya  daftar upload
     * @return array{count: int, errors: list<string>}
     */
    public static function saveBerkas(int $satpamId, array $khusus, array $lainnya): array
    {
        $service = app(SatpamArsipService::class);
        $user = Auth::user();
        $count = 0;
        $errors = [];

        foreach (SatpamArsipService::JENIS_KHUSUS as $jenis) {
            $file = self::firstFile($khusus[$jenis] ?? null);

            if ($file === null) {
                continue;
            }

            try {
                $service->uploadKhusus($satpamId, $jenis, self::metaOf($file), $user);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = "{$jenis}: ".self::ringkasError($e);
                Log::error('[arsip-satpam] upload '.$jenis.' gagal: '.$e->getMessage());
            }
        }

        foreach ($lainnya as $file) {
            $file = self::firstFile($file);

            if ($file === null) {
                continue;
            }

            $meta = self::metaOf($file);

            try {
                $service->upload($satpamId, $meta, $user, null, SatpamArsipService::JENIS_LAINNYA);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = "{$meta['filename']}: ".self::ringkasError($e);
                Log::error('[arsip-satpam] batch upload gagal untuk '.$meta['filename'].': '.$e->getMessage());
            }
        }

        if ($errors !== []) {
            Notification::make()
                ->title('Sebagian berkas gagal diunggah')
                ->body(implode("\n", $errors))
                ->danger()
                ->persistent()
                ->send();
        }

        if ($count > 0 && $errors === []) {
            Notification::make()
                ->title($count.' berkas berhasil disimpan')
                ->success()
                ->send();
        } elseif ($count > 0) {
            Notification::make()
                ->title($count.' berkas berhasil, '.count($errors).' gagal')
                ->warning()
                ->send();
        }

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * Normalisasi state FileUpload tunggal: objek upload, atau null bila
     * slot dikosongkan.
     */
    private static function firstFile(mixed $value): ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile
    {
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        return $value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? $value : null;
    }

    /**
     * @return array{path: string, filename: string, mime: string, extension: string, size: int}
     */
    private static function metaOf(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): array
    {
        return [
            'path' => $file->getRealPath(),
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
            'size' => (int) $file->getSize(),
        ];
    }

    /**
     * Hapus data satpam beserta SELURUH arsipnya (DB + berkas fisik).
     */
    public static function deleteWithArsip(Satpam $satpam): void
    {
        $service = app(SatpamArsipService::class);

        foreach ($satpam->arsip()->get() as $arsip) {
            $service->delete($arsip);
        }

        $satpam->delete();
    }

    /** Sama seperti App\Filament\Arsip\Resources\DocumentResource::ringkasError(). */
    private static function ringkasError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        return mb_strlen($msg) > 300 ? mb_substr($msg, 0, 297).'...' : $msg;
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
