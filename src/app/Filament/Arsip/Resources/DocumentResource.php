<?php

namespace App\Filament\Arsip\Resources;

use App\Filament\Arsip\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Ownership;
use App\Models\Tag;
use App\Services\ArsipDigital\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Dokumen';

    protected static string|\UnitEnum|null $navigationGroup = 'Arsip';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen';

    protected static ?string $slug = 'dokumen';

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        /** @var Document $record */
        return $user->canAccessDocument($record);
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
            FileUpload::make('berkas')
                ->label('Berkas')
                ->helperText(fn (): string => 'Pilih bisa banyak berkas sekaligus. Maks '.config('arsip.max_upload_kb').' KB per berkas. Format: '.implode(', ', config('arsip.allowed_extensions')))
                ->multiple()
                ->maxFiles(50)
                ->storeFiles(false)
                ->acceptedFileTypes(self::acceptedMimeTypes())
                ->maxSize(config('arsip.max_upload_kb'))
                ->visibleOn('create'),

            Repeater::make('tautan')
                ->label('Tautan (opsional — bisa banyak, masing-masing berjudul)')
                ->helperText('Tambah baris per tautan. Judul dikosongkan = mengikuti segmen terakhir URL.')
                ->schema([
                    TextInput::make('url')
                        ->label('URL')
                        ->placeholder('https://drive.google.com/…')
                        ->required()
                        ->maxLength(1024)
                        ->url()
                        ->columnSpan(2),
                    TextInput::make('judul')
                        ->label('Judul Tautan')
                        ->placeholder('Opsional — mis. "SK Bupati 2024"')
                        ->maxLength(255),
                ])
                ->columns(3)
                ->addActionLabel('Tambah Tautan')
                ->reorderable()
                ->collapsible()
                ->defaultItems(0)
                ->visibleOn('create'),

            TextInput::make('title')
                ->label('Judul Dokumen')
                ->helperText('Wajib bila tidak ada berkas maupun tautan. Bila ada isi, judul boleh kosong (sistem memakai "[DRAF] - Nama File / Judul Tautan pertama").')
                ->maxLength(255)
                ->visibleOn(['create', 'edit'])
                ->required(function (string $operation, Get $get): bool {
                    // Di luar create, judul tidak dipaksa wajib di sini.
                    if ($operation !== 'create') {
                        return false;
                    }

                    $adaBerkas = collect($get('berkas') ?? [])->filter()->isNotEmpty();
                    $adaTautan = collect($get('tautan') ?? [])
                        ->map(fn ($row): string => trim((string) ($row['url'] ?? '')))
                        ->filter()
                        ->isNotEmpty();

                    // Hanya wajib bila berkas DAN tautan sama-sama kosong.
                    return ! $adaBerkas && ! $adaTautan;
                })
                ->validationMessages([
                    'required' => 'Judul dokumen wajib diisi bila tidak ada berkas/tautan.',
                ]),

            Textarea::make('description')
                ->label('Deskripsi')
                ->nullable()
                ->rows(3)
                ->maxLength(65535)
                ->columnSpanFull()
                ->visibleOn(['create', 'edit'])
                ->helperText('Opsional. Jelaskan ringkasan atau keterangan dokumen.'),

            Section::make('Berkas & Tautan')
                ->description('Kelola berkas dan tautan dokumen — hapus yang lama atau tambah yang baru (bisa banyak sekaligus).')
                ->visible(fn (string $operation, ?Document $record): bool => $operation === 'edit' && $record !== null)
                ->schema([
                    ViewField::make('file_list')
                        ->view('filament.arsip.forms.file-list')
                        ->label('Tersimpan')
                        ->dehydrated(false),

                    FileUpload::make('new_berkas')
                        ->label('Tambah Berkas Baru')
                        ->helperText(fn (): string => 'Berkas lama tampil di atas (ikon tempat sampah untuk menghapus). Pilih satu atau banyak berkas sekaligus untuk ditambahkan. Maks '.config('arsip.max_upload_kb').' KB per berkas. Format: '.implode(', ', config('arsip.allowed_extensions')))
                        ->multiple()
                        ->maxFiles(50)
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        // Preview bawaan Filament sangat tinggi (1 file bisa
                        // 1 halaman) — pakai panel ringkas.
                        ->panelLayout('compact')
                        ->panelAspectRatio('6:1')
                        ->imagePreviewHeight('70'),

                    Repeater::make('new_tautan')
                        ->label('Tambah Tautan Baru')
                        ->helperText('Satu baris per tautan, masing-masing punya judul.')
                        ->schema([
                            TextInput::make('url')
                                ->label('URL')
                                ->placeholder('https://drive.google.com/…')
                                ->required()
                                ->maxLength(1024)
                                ->url()
                                ->columnSpan(2),
                            TextInput::make('judul')
                                ->label('Judul Tautan')
                                ->placeholder('Opsional')
                                ->maxLength(255),
                        ])
                        ->columns(3)
                        ->addActionLabel('Tambah Tautan')
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0),
                ]),

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

            Select::make('ownership_id')
                ->label('Ownership / Hak Akses')
                ->relationship(
                    name: 'ownership',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query): void {
                        $user = Auth::user();
                        if ($user && ! $user->isArsipAdmin()) {
                            $query->whereIn('id', $user->ownerships()->pluck('ownerships.id'));
                        }
                    }
                )
                ->searchable()
                ->preload()
                ->placeholder('— Publik (Semua Pengguna) —')
                ->native(false)
                ->default(function (): ?int {
                    $user = Auth::user();
                    if (! $user) {
                        return null;
                    }
                    $ownerships = $user->ownerships;

                    return $ownerships->count() === 1 ? $ownerships->first()->id : null;
                })
                ->visibleOn(['create', 'edit'])
                ->disabled(fn (string $operation): bool => $operation === 'edit' && ! Auth::user()->isArsipAdmin())
                ->helperText(function (): string {
                    $user = Auth::user();
                    if ($user?->isArsipAdmin()) {
                        return 'Admin / Superadmin memiliki akses penuh ke semua dokumen. Pilih unit atau kosongkan untuk Publik.';
                    }

                    return 'Pilih unit kepemilikan dokumen Anda atau kosongkan untuk Publik. Hanya dapat diatur saat unggah.';
                }),

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
                ->default(Document::STATUS_PENDING)
                ->visibleOn(['create', 'edit'])
                // Hanya Admin/Superadmin yang melihat field ini — staf biasa
                // dokumennya selalu masuk Menunggu Verifikasi. Di create admin
                // boleh langsung memilih "Terbit" tanpa melewati verifikasi.
                ->visible(fn (): bool => (bool) Auth::user()?->isArsipAdmin())
                ->disabledOn('edit')
                ->helperText('Admin/Superadmin: pilih "Terbit" untuk langsung menerbitkan tanpa verifikasi.'),
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
                    ->color(fn (string $state): string => $state === Document::SOURCE_FILE ? 'gray' : 'info')
                    ->sortable(),
                TextColumn::make('ownership.name')
                    ->label('Ownership')
                    ->badge()
                    ->placeholder('Umum')
                    ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray')
                    ->sortable()
                    ->toggleable(),
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
                SelectFilter::make('ownership_id')
                    ->label('Ownership')
                    ->relationship('ownership', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('tag')
                    ->label('Tag')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                Action::make('buka')
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
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isArsipAdmin()) {
            return $query;
        }

        $userOwnershipIds = $user->ownerships()->pluck('ownerships.id')->all();

        return $query->where(function (Builder $q) use ($user, $userOwnershipIds): void {
            // 1. Dokumen yang diunggah oleh user ini sendiri (bisa lihat status apa pun)
            $q->where('created_by', $user->id)
              // 2. Dokumen terbit (published) yang berada di bawah ownership user atau dokumen umum (null)
                ->orWhere(function (Builder $sub) use ($userOwnershipIds): void {
                    $sub->where('status', Document::STATUS_PUBLISHED)
                        ->where(function (Builder $ownerQ) use ($userOwnershipIds): void {
                            if (! empty($userOwnershipIds)) {
                                $ownerQ->whereIn('ownership_id', $userOwnershipIds)
                                    ->orWhereNull('ownership_id');
                            } else {
                                $ownerQ->whereNull('ownership_id');
                            }
                        });
                });
        });
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
     * Normalisasi state upload berkas jadi array meta siap dipakai
     * DocumentService. Dua bentuk yang didukung:
     *   - FileUpload ->multiple(): array FLAT berisi objek file.
     *   - Repeater (lama): array item ['file' => file | array-file].
     * Nilai tiap item bisa objek tunggal atau array — semuanya diratakan di
     * sini agar aman dari error "getRealPath() on array".
     *
     * @param  mixed  $berkas
     * @return list<array{path: string, filename: string, mime: string, extension: string, size: int}>
     */
    public static function normalizeUploadedFiles(mixed $berkas): array
    {
        return collect($berkas)
            ->map(fn ($value) => is_array($value) && array_key_exists('file', $value) ? $value['file'] : $value)
            ->flatMap(function ($value): array {
                // Bisa berupa satu objek file, atau array objek file (multi).
                return is_array($value) ? $value : [$value];
            })
            ->filter()
            ->map(function ($file): ?array {
                if (! $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                    && ! $file instanceof \Illuminate\Http\UploadedFile) {
                    return null;
                }

                return [
                    'path' => $file->getRealPath(),
                    'filename' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                    'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
                    'size' => (int) $file->getSize(),
                ];
            })
            ->filter()
            ->values()
            ->all();
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
            // Video ( Arsip bisa berupa rekaman kegiatan )
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            'video/webm',
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
