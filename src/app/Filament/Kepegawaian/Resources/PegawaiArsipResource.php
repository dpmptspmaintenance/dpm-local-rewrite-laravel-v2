<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\PegawaiArsipResource\Pages;
use App\Models\Kepegawaian\PegawaiArsip;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Services\Kepegawaian\PegawaiArsipService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Arsip berkas pegawai (SKP, SK Kenaikan Pangkat, SK Jabatan, Foto, Ijazah,
 * dll) — beda dari modul Arsip Digital umum (/arsip, App\Models\Document).
 * Ini KHUSUS per pegawai, reuse Google Drive yang sama tapi lewat subfolder
 * "Kepegawaian" tersendiri (lihat PegawaiArsipService).
 *
 * Halaman list ini menampilkan LINTAS pegawai (filter pilih pegawai
 * tersedia) — untuk lihat/upload/hapus arsip khusus SATU pegawai yang
 * sedang dibuka, pakai tab "Arsip" di halaman detail pegawai
 * (PegawaiProfilResource\RelationManagers\ArsipRelationManager).
 */
class PegawaiArsipResource extends Resource
{
    protected static ?string $model = PegawaiArsip::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Arsip Pegawai';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Arsip Pegawai';

    protected static ?string $pluralModelLabel = 'Arsip Pegawai';

    protected static ?string $slug = 'arsip-pegawai';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pegawai')
                ->columns(1)
                ->schema([
                    Select::make('nip')
                        ->label('Pegawai')
                        ->options(fn (): array => PegawaiProfil::query()
                            ->orderBy('nama')
                            ->get(['nip', 'nama'])
                            ->mapWithKeys(fn (PegawaiProfil $p): array => [$p->nip => "{$p->nama} ({$p->nip})"])
                            ->all())
                        ->searchable()
                        ->required(),
                ]),

            Section::make('Berkas')
                ->columns(2)
                ->schema([
                    FileUpload::make('files')
                        ->label('Berkas')
                        ->helperText(fn (): string => 'Bisa unggah banyak sekaligus. Maks '.config('arsip.max_upload_kb').' KB per berkas. Format: '.implode(', ', config('arsip.allowed_extensions')))
                        ->multiple()
                        ->storeFiles(false)
                        ->acceptedFileTypes(self::acceptedMimeTypes())
                        ->maxSize(config('arsip.max_upload_kb'))
                        ->required()
                        ->columnSpanFull()
                        ->visibleOn('create'),
                    TextInput::make('judul')
                        ->label('Judul')
                        ->helperText('Kosong = nama file. Diabaikan bila unggah lebih dari 1 berkas sekaligus (tiap berkas pakai nama filenya sendiri).')
                        ->maxLength(255)
                        ->visibleOn('create'),
                    Select::make('kategori')
                        ->label('Kategori')
                        ->helperText('Bebas ketik — mis. SKP, SK Kenaikan Pangkat, SK Jabatan, Foto, Ijazah.')
                        ->options(fn (): array => collect(app(PegawaiArsipService::class)->kategoriSuggestions())
                            ->mapWithKeys(fn (string $k): array => [$k => $k])
                            ->all())
                        ->searchable()
                        ->createOptionUsing(fn (string $value): string => $value)
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('profil.nama')
                    ->label('Pegawai')
                    ->description(fn (PegawaiArsip $record): ?string => $record->nip)
                    ->searchable(['nip'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable(),
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
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('nip')
                    ->label('Pegawai')
                    ->options(fn (): array => PegawaiProfil::query()
                        ->orderBy('nama')
                        ->get(['nip', 'nama'])
                        ->mapWithKeys(fn (PegawaiProfil $p): array => [$p->nip => $p->nama])
                        ->all())
                    ->searchable(),
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(fn (): array => collect(app(PegawaiArsipService::class)->kategoriSuggestions())
                        ->mapWithKeys(fn (string $k): array => [$k => $k])
                        ->all()),
            ])
            ->recordActions([
                Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (PegawaiArsip $record): string => $record->openUrl())
                    ->openUrlInNewTab(),
                // Hapus lewat PegawaiArsipService supaya berkas Drive ikut
                // terhapus — jangan pakai DeleteAction bawaan (cuma hapus baris DB).
                DeleteAction::make()
                    ->using(fn (PegawaiArsip $record) => app(PegawaiArsipService::class)->delete($record)),
            ])
            ->emptyStateHeading('Belum ada arsip pegawai')
            ->emptyStateDescription('Tambah manual — tidak ada impor otomatis untuk arsip berkas fisik.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawaiArsip::route('/'),
            'create' => Pages\CreatePegawaiArsip::route('/create'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nip', 'judul', 'kategori'];
    }

    /**
     * Unggah batch berkas untuk SATU pegawai (dipanggil dari
     * CreatePegawaiArsip dan ArsipRelationManager — satu-satunya tempat
     * logic batch upload arsip pegawai ditulis, supaya perilaku sama di
     * kedua tempat). Kegagalan satu berkas tidak membatalkan berkas lain.
     *
     * @return array{ids: list<int>, errors: list<string>}
     */
    public static function uploadBatch(string $nip, array $files, ?string $judul, ?string $kategori): array
    {
        $service = app(PegawaiArsipService::class);
        $createdIds = [];
        $errors = [];
        $multiple = count($files) > 1;
        $user = Auth::user();

        foreach ($files as $file) {
            $fileJudul = ($multiple || blank($judul)) ? null : $judul;

            $meta = [
                'path' => $file->getRealPath(),
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)),
                'size' => (int) $file->getSize(),
            ];

            try {
                $createdIds[] = $service->upload($nip, $meta, $user, $fileJudul, $kategori)->id;
            } catch (\Throwable $e) {
                $name = $file->getClientOriginalName();
                $errors[] = "{$name}: ".self::ringkasError($e);
                Log::error('[arsip-kepegawaian] batch upload gagal untuk '.$name.': '.$e->getMessage());
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
                ->title(count($createdIds).' berkas berhasil diunggah')
                ->success()
                ->send();
        } elseif ($createdIds !== []) {
            Notification::make()
                ->title(count($createdIds).' berkas berhasil, '.count($errors).' gagal')
                ->warning()
                ->send();
        }

        return ['ids' => $createdIds, 'errors' => $errors];
    }

    /** Sama seperti App\Filament\Arsip\Resources\DocumentResource::ringkasError(). */
    private static function ringkasError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        $decoded = json_decode($msg, true);
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            $g = $decoded['error'];
            $ringkas = trim(($g['code'] ?? '').' '.$g['message']);

            if (str_contains($ringkas, 'File not found: .')) {
                return 'Folder induk Google Drive belum diatur (ARSIP_DRIVE_FOLDER_ID kosong di .env). '
                    .'Minta admin isi ID folder induk Drive.';
            }
            if (str_contains($ringkas, 'insufficientPermissions') || ($g['code'] ?? 0) === 403) {
                return 'Akun Drive tidak punya akses ke folder induk. Cek konfigurasi ARSIP_DRIVE_* di .env.';
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
}
