<?php

namespace App\Filament\Arsip\Resources;

use App\Filament\Arsip\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Kelola master tag. Terbatas admin — staf biasa cuma menambah tag lewat
 * TagsInput autocomplete di form dokumen (App\Models\Tag::resolve()), tidak
 * membuka halaman ini.
 */
class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'Tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Arsip';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Tag';

    protected static ?string $pluralModelLabel = 'Tag';

    protected static ?string $slug = 'tag';

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->isArsipAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Tag')
                ->required()
                ->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(110)
                ->unique(ignoreRecord: true)
                ->helperText('Dipakai untuk mencocokkan tag secara case-insensitive.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Tag')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->toggleable(),
                TextColumn::make('documents_count')->label('Jumlah Dokumen')->counts('documents'),
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
            ->emptyStateHeading('Belum ada tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTags::route('/'),
        ];
    }
}
