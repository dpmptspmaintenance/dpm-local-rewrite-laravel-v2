<?php

namespace App\Filament\Tool\Resources;

use App\Filament\Tool\Resources\CertificateFontResource\Pages;
use App\Models\Certificate\CertificateFont;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Master Font untuk Generator Sertifikat. Font yang aktif muncul di dropdown
 * properti field dan didaftarkan ke Dompdf lewat CertificateRenderer.
 *
 * Font `is_system` (DejaVu bundle Dompdf + core font AFM Helvetica/Times/
 * Courier) tidak butuh berkas unggahan; font lain wajib punya TTF Regular
 * agar bisa dirender — kalau tidak, sistem jatuh ke DejaVu Sans.
 */
class CertificateFontResource extends Resource
{
    protected static ?string $model = CertificateFont::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationLabel = 'Master Font Sertifikat';

    protected static string | \UnitEnum | null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Font';

    protected static ?string $pluralModelLabel = 'Master Font Sertifikat';

    protected static ?string $slug = 'master-font-sertifikat';

    public static function canViewAny(): bool
    {
        return self::isAdmin();
    }

    public static function canCreate(): bool
    {
        return self::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return self::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return self::isAdmin() && ! (bool) $record->is_system;
    }

    private static function isAdmin(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return (int) $user->role === 1 || (bool) $user->is_admin_kepegawaian;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Font')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Tampilan')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Mis. "Liberation Sans". Muncul di dropdown properti field.'),
                    TextInput::make('css_family')
                        ->label('Nama Keluarga Font (CSS)')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->helperText('Harus sama dengan nama internal TTF, mis. "Liberation Sans". Jangan pakai tanda kutip.'),
                    Select::make('category')
                        ->label('Kategori')
                        ->options(CertificateFont::CATEGORIES)
                        ->default('sans')
                        ->required()
                        ->native(false),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Nonaktif = tidak muncul di dropdown, tapi template lama tetap bisa dirender (fallback DejaVu Sans bila tidak terbaca).'),
                    TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->default(0)
                        ->helperText('Angka lebih kecil tampil lebih dulu.'),
                    TextInput::make('line_height_factor')
                        ->label('Faktor Jarak Baris Dompdf')
                        ->numeric()
                        ->step('0.001')
                        ->default(1.264)
                        ->helperText('Bawaan 1.264. Ubah hanya bila teks 2+ baris tidak sejajar dengan pratinjau setelah font baru diunggah.'),
                ])
                ->columns(2),
            Section::make('Berkas Font (TTF)')
                ->description('Wajib untuk font non-sistem. Unggah varian Bold/Italic/Bold Italic bila tersedia — tanpa itu Dompdf memakai varian Regular untuk semua gaya. Font sistem (DejaVu/Helvetica/Times/Courier) memakai TTF bundel aplikasi sehingga selalu ter-embed di PDF.')
                ->visible(fn (?CertificateFont $record): bool => $record === null || ! $record->is_system)
                ->schema([
                    self::fontUpload('regular_path', 'Regular', true),
                    self::fontUpload('bold_path', 'Bold', false),
                    self::fontUpload('italic_path', 'Italic', false),
                    self::fontUpload('bold_italic_path', 'Bold Italic', false),
                ])
                ->columns(2),
        ]);
    }

    private static function fontUpload(string $column, string $label, bool $required): FileUpload
    {
        $upload = FileUpload::make($column)
            ->label($label)
            ->acceptedFileTypes(['font/ttf', 'font/sfnt', 'application/x-font-ttf', 'application/octet-stream'])
            ->maxSize(4096)
            ->disk(CertificateFont::DISK)
            ->directory(CertificateFont::DIRECTORY)
            ->helperText('Berkas .ttf, maks. 4 MB.');

        return $required ? $upload->required() : $upload;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CertificateFont $record): string => $record->css_family),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CertificateFont::CATEGORIES[$state] ?? (string) $state),
                TextColumn::make('variants')
                    ->label('Varian')
                    ->state(fn (CertificateFont $record): string => $record->variantSummary()),
                IconColumn::make('is_system')
                    ->label('Sistem')
                    ->boolean(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('used_fields')
                    ->label('Dipakai')
                    ->state(fn (CertificateFont $record): int => $record->fields()->count()),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (CertificateFont $record): bool => ! $record->is_system && self::isAdmin())
                    ->successNotificationTitle('Font dihapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => self::isAdmin()),
                ]),
            ])
            ->emptyStateHeading('Belum ada font')
            ->emptyStateDescription('Tambahkan font TTF agar bisa dipakai di generator sertifikat.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCertificateFonts::route('/'),
        ];
    }
}
