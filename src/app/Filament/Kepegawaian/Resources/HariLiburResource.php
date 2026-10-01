<?php

namespace App\Filament\Kepegawaian\Resources;

use App\Filament\Kepegawaian\Resources\HariLiburResource\Pages;
use App\Models\Kepegawaian\HariLibur;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HariLiburResource extends Resource
{
    protected static ?string $model = HariLibur::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Hari Libur';

    protected static string | \UnitEnum | null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    protected static ?string $slug = 'hari-libur';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->native(false)
                ->required()
                // tanggal has a UNIQUE index in the dump's schema.
                ->unique(ignoreRecord: true),
            TextInput::make('keterangan')
                ->label('Keterangan')
                ->maxLength(255)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn (HariLibur $record): string => $record->tanggal?->translatedFormat('l') ?? '')
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => HariLibur::query()
                        ->selectRaw('DISTINCT YEAR(tanggal) as y')
                        ->orderByDesc('y')
                        ->pluck('y', 'y')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $year) => $q->whereYear('tanggal', $year))),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada hari libur');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHariLibur::route('/'),
        ];
    }
}
