<?php

namespace App\Filament\Arsip\Resources;

use App\Filament\Arsip\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Tag;
use App\Services\ArsipDigital\DocumentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Dokumen';

    protected static string | \UnitEnum | null $navigationGroup = 'Arsip';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen';

    protected static ?string $slug = 'dokumen';

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    /**
     * Staf biasa hanya boleh mengedit dokumen miliknya sendiri yang masih
     * pending_review. Admin arsip boleh mengedit semua.
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user->isArsipAdmin()
            || ($record->created_by === $user->id && $record->isPending());
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->isArsipAdmin();
    }

    /**
     * Form dipakai di halaman Create (batch upload), Edit, dan ReviewQueue.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Forms\Components\Radio::make('source_mode')
                ->label('Jenis Unggahan')
                ->options([
                    Document::SOURCE_FILE => 'Unggah Berkas',
                    Document::SOURCE_URL => 'Tempel Tautan (Google Drive / Web)',
                ])
                ->default(Document::SOURCE_FILE)
                ->inline()
                ->live()
                ->dehydrated(false) // bukan kolom DB — hanya penggerak visibilitas field
                ->visibleOn('create'),

            FileUpload::make('files')
                ->label('Berkas')
                ->helperText(fn (): string => 'Maks '.config('arsip.max_upload_kb').' KB. Format: '.implode(', ', config('arsip.allowed_extensions')))
                ->multiple()
                ->storeFiles(false)
                ->acceptedFileTypes(self::acceptedMimeTypes())
                ->maxSize(config('arsip.max_upload_kb'))
                ->visibleOn('create')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('source_mode') !== Document::SOURCE_URL)
                ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('source_mode') !== Document::SOURCE_URL),

            \Filament\Forms\Components\Textarea::make('urls')
                ->label('Tautan Dokumen')
                ->helperText('Satu URL per baris (boleh banyak baris sekaligus). Judul tiap tautan mengikuti segmen terakhir URL bila judul dikosongkan.')
                ->rows(5)
                ->visibleOn('create')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('source_mode') === Document::SOURCE_URL)
                ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('source_mode') === Document::SOURCE_URL),

            TextInput::make('title')
                ->label('Judul Dokumen')
                ->helperText('Opsional saat unggah batch; kosong = sistem memakai "[DRAF] - Nama File".')
                ->maxLength(255)
                ->visibleOn(['create', 'edit']),

            \Filament\Forms\Components\ViewField::make('file_list')
                ->view('filament.arsip.forms.file-list')
                ->label('Berkas Dokumen')
                ->dehydrated(false)
                ->visibleOn('edit')
                ->visible(fn (?Document $record): bool => $record !== null && ! $record->isUrl()),

            FileUpload::make('new_files')
                ->label('Tambah Berkas')
                ->helperText(fn (): string => 'Berkas baru langsung ditambahkan ke dokumen ini setelah disimpan. Maks '.config('arsip.max_upload_kb').' KB. Format: '.implode(', ', config('arsip.allowed_extensions')))
                ->multiple()
                ->storeFiles(false)
                ->acceptedFileTypes(self::acceptedMimeTypes())
                ->maxSize(config('arsip.max_upload_kb'))
                ->visibleOn('edit')
                ->visible(fn (?Document $record): bool => $record !== null && ! $record->isUrl()),

            TextInput::make('title')
                ->label('Judul Dokumen')
                ->required()
                ->maxLength(255)
                ->visibleOn('review'),

            Select::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')->required()->maxLength(150)->unique('categories', 'name'),
                ])
                ->placeholder('— Pilih kategori —')
                ->native(false),

            TagsInput::make('tag_names')
                ->label('Tag')
                ->helperText('Ketik lalu tekan Enter. Tag baru otomatis dibuat. "Arsip" dan "arsip" dianggap sama.')
                ->suggestions(fn (): array => Tag::query()->orderBy('name')->pluck('name')->all())
                ->placeholder('Tambah tag…'),

            ToggleButtons::make('status')
                ->label('Status Verifikasi')
                ->options(Document::STATUSES)
                ->colors([
                    Document::STATUS_PENDING => 'warning',
                    Document::STATUS_PUBLISHED => 'success',
                    Document::STATUS_REJECTED => 'danger',
                    Document::STATUS_ARCHIVED => 'gray',
                ])
                ->inline()
                ->visibleOn('edit')
                ->disabled(fn (): bool => ! Auth::user()->isArsipAdmin()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('source_type')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Document::SOURCES[$state] ?? $state)
                    ->color(fn (string $state): string => $state === Document::SOURCE_URL ? 'info' : 'gray')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('files_count')
                    ->label('Berkas')
                    ->counts('files')
                    ->badge()
                    ->color('gray')
                    ->tooltip('Jumlah berkas fisik dokumen ini')
                    ->toggleable(),
                TextColumn::make('files.original_filename')
                    ->label('Nama File')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Document::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Document::STATUS_PENDING => 'warning',
                        Document::STATUS_PUBLISHED => 'success',
                        Document::STATUS_REJECTED => 'danger',
                        Document::STATUS_ARCHIVED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('creator.nama')
                    ->label('Diunggah Oleh')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(fn (): bool => Auth::user()->isArsipAdmin()),
                TextColumn::make('created_at')
                    ->label('Tgl Unggah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Document::STATUSES)
                    ->native(false),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Document $record): string => $record->openUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => $record->isUrl()),
                ViewAction::make(),
                EditAction::make()->visible(fn (Document $record): bool => static::canEdit($record)),
                // Hapus lewat DocumentService supaya berkas Drive ikut terhapus —
                // jangan pakai DeleteAction bawaan yang cuma hapus baris DB.
                DeleteAction::make()
                    ->using(fn (Document $record) => app(DocumentService::class)->delete($record))
                    ->visible(fn (): bool => Auth::user()->isArsipAdmin()),
            ])
            ->emptyStateHeading('Belum ada dokumen');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! Auth::user()->isArsipAdmin()) {
            $query->where('created_by', Auth::id());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    /**
     * Proses upload batch dipanggil dari CreateDocument. Setiap berkas
     * diteruskan ke DocumentService; kegagalan satu tidak membatalkan
     * berkas lain, tapi tetap mengembalikan notifikasi ringkasan.
     *
     * @param  list<string>  $tagNames
     * @return array{ids: list<int>, errors: list<string>}
     */
    public static function uploadBatch(array $files, ?string $title, ?int $categoryId, array $tagNames, DocumentService $service): array
    {
        $createdIds = [];
        $errors = [];
        $multiple = count($files) > 1;
        $user = Auth::user();

        foreach ($files as $index => $file) {
            $fileTitle = ($multiple || blank($title)) ? null : $title;

            $meta = [
                'path' => $file->getRealPath(),
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
                'size' => (int) $file->getSize(),
            ];

            try {
                $createdIds[] = $service->upload($meta, $user, $categoryId, $fileTitle, $tagNames)->id;
            } catch (\Throwable $e) {
                $name = $file->getClientOriginalName();
                $errors[] = "{$name}: ".self::ringkasError($e);
                Log::error('[arsip] batch upload gagal untuk '.$name.': '.$e->getMessage());
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

        if ($createdIds !== [] && $errors === []) {
            Notification::make()
                ->title(count($createdIds).' dokumen berhasil diunggah')
                ->body('Dokumen baru masuk status Menunggu Verifikasi.')
                ->success()
                ->send();
        } elseif ($createdIds !== []) {
            Notification::make()
                ->title(count($createdIds).' dokumen berhasil, '.count($errors).' gagal')
                ->warning()
                ->send();
        }

        return ['ids' => $createdIds, 'errors' => $errors];
    }

    /**
     * Daftarkan batch tautan URL (satu baris DB per URL yang valid).
     * Kegagalan satu URL tidak membatalkan yang lain — diringkas di
     * notifikasi seperti uploadBatch().
     *
     * @param  list<string>  $urls
     * @param  list<string>  $tagNames
     * @return array{ids: list<int>, errors: list<string>}
     */
    public static function registerUrlBatch(array $urls, ?string $title, ?int $categoryId, array $tagNames, DocumentService $service): array
    {
        $createdIds = [];
        $errors = [];
        $multiple = count($urls) > 1;
        $user = Auth::user();

        foreach ($urls as $url) {
            try {
                $createdIds[] = $service->registerUrl(
                    (string) $url,
                    $user,
                    $categoryId,
                    ($multiple || blank($title)) ? null : $title,
                    $tagNames,
                )->id;
            } catch (\Throwable $e) {
                $errors[] = "{$url}: ".self::ringkasError($e);
                Log::error('[arsip] register URL gagal untuk '.$url.': '.$e->getMessage());
            }
        }

        if ($errors !== []) {
            Notification::make()
                ->title('Sebagian tautan gagal didaftarkan')
                ->body(implode("\n", $errors))
                ->danger()
                ->persistent()
                ->send();
        }

        if ($createdIds !== [] && $errors === []) {
            Notification::make()
                ->title(count($createdIds).' tautan berhasil didaftarkan')
                ->body('Masuk status Menunggu Verifikasi.')
                ->success()
                ->send();
        } elseif ($createdIds !== []) {
            Notification::make()
                ->title(count($createdIds).' tautan berhasil, '.count($errors).' gagal')
                ->warning()
                ->send();
        }

        return ['ids' => $createdIds, 'errors' => $errors];
    }

    /**
     * Ringkas exception jadi pesan satu-baris yang layak tampil ke user.
     * Error Google API (HTTP body JSON) dipetakan ke ringkasannya; error
     * lain dipotong 300 karakter.
     */
    private static function ringkasError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        // Google\Service\Exception membungkus body JSON {"error": {...}}
        $decoded = json_decode($msg, true);
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            $g = $decoded['error'];
            $ringkas = trim(($g['code'] ?? '').' '.$g['message']);

            // Pesan populer dipetakan ke bahasa awam supaya admin tak harus
            // mengartikan HTTP 404 mentah Google Drive.
            if (str_contains($ringkas, 'File not found: .')) {
                return 'Folder tujuan Google Drive belum diatur (ARSIP_DRIVE_FOLDER_ID kosong di .env). '
                    .'Minta admin isi ID folder induk Drive.';
            }
            if (str_contains($ringkas, 'insufficientPermissions') || ($g['code'] ?? 0) === 403) {
                return 'Service account tidak punya akses ke folder Drive. '
                    .'Bagikan folder induk ke email service account dengan hak akses Editor.';
            }

            return (string) ($g['code'] ?? '').' — '.$g['message'];
        }

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

    private static function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $u = 0;
        $size = $bytes;

        while ($size >= 1024 && $u < count($units) - 1) {
            $size /= 1024;
            $u++;
        }

        return round($size, 2).' '.$units[$u];
    }
}
