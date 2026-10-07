<?php

namespace App\Filament\Arsip\Resources;

use App\Filament\Arsip\Resources\OwnershipResource\Pages;
use App\Models\Ownership;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Kelola master Ownership / Hak Akses Arsip Digital.
 * Akses terbatas untuk admin/superadmin (lihat canViewAny).
 * Admin dapat menambah, mengubah, dan menghapus unit ownership bebas (misal: Kepegawaian, Keuangan, IT),
 * serta menentukan user anggota yang berhak mengakses arsip di bawah ownership tersebut.
 */
class OwnershipResource extends Resource
{
    protected static ?string $model = Ownership::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Ownership';

    protected static string | \UnitEnum | null $navigationGroup = 'Arsip';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Ownership';

    protected static ?string $pluralModelLabel = 'Ownership';

    protected static ?string $slug = 'ownership';

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->isArsipAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Ownership / Unit')
                ->placeholder('Contoh: Kepegawaian, Keuangan, IT')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(160)
                ->unique(ignoreRecord: true),
            Textarea::make('description')
                ->label('Keterangan')
                ->rows(3)
                ->maxLength(500)
                ->placeholder('Deskripsi atau catatan hak akses unit ini…'),
            Select::make('users')
                ->label('Daftar Pengguna (User Anggota)')
                ->relationship('users', 'nama')
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText('Hanya pengguna yang dipilih di sini (serta Admin/Superadmin) yang dapat mengakses arsip ber-ownership ini.')
                ->placeholder('Pilih user anggota…'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Ownership')->searchable()->sortable()->weight('medium'),
                TextColumn::make('slug')->label('Slug')->toggleable(),
                TextColumn::make('description')->label('Keterangan')->limit(40)->placeholder('—')->toggleable(),
                TextColumn::make('users_count')->label('Jumlah Anggota')->counts('users')->badge()->color('info'),
                TextColumn::make('documents_count')->label('Jumlah Dokumen')->counts('documents')->badge()->color('gray'),
                TextColumn::make('users.nama')->label('Anggota')->listWithLineBreaks()->limitList(3)->expandableLimitedList()->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data Ownership');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOwnerships::route('/'),
        ];
    }
}
