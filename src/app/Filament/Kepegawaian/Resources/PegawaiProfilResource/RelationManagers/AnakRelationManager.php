<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnakRelationManager extends RelationManager
{
    protected static string $relationship = 'anak';

    protected static ?string $title = 'Data Anak';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-user-group';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_anak')
            ->columns([
                TextColumn::make('no_urut')->label('No.')->alignEnd(),
                TextColumn::make('nama_anak')->label('Nama')->wrap()->weight('medium'),
                TextColumn::make('gender_anak')->label('L/P')->placeholder('—'),
                TextColumn::make('tanggal_lahir_anak')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->description(fn ($record): ?string => $record->tempat_lahir_anak)
                    ->placeholder('—'),
                TextColumn::make('usia_anak')->label('Usia')->placeholder('—'),
                TextColumn::make('tingkat_pendidikan_anak')->label('Pendidikan')->placeholder('—')->wrap(),
                TextColumn::make('tunjangan_anak')
                    ->label('Tunjangan')
                    ->badge()
                    ->color(fn (?string $state): string => $state && str_contains(strtolower($state), 'ya') ? 'success' : 'gray')
                    ->placeholder('—'),
                TextColumn::make('hubungan_keluarga_anak')->label('Hubungan')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('no_urut')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada data anak');
    }
}
