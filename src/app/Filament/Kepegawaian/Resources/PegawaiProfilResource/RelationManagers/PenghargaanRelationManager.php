<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenghargaanRelationManager extends RelationManager
{
    protected static string $relationship = 'penghargaan';

    protected static ?string $title = 'Riwayat Penghargaan';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-trophy';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_penghargaan')
            ->columns([
                TextColumn::make('no_urut')->label('No.')->alignEnd(),
                TextColumn::make('nama_penghargaan')
                    ->label('Nama Penghargaan')
                    ->description(fn ($record): ?string => $record->jenis_penghargaan)
                    ->wrap()
                    ->weight('medium')
                    ->placeholder('—'),
                TextColumn::make('asal_perolehan_penghargaan')
                    ->label('Asal Perolehan')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('nomor_sk_penghargaan')
                    ->label('Nomor SK')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('tanggal_sk_penghargaan')
                    ->label('Tanggal SK')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('peringkat_penghargaan')
                    ->label('Peringkat')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_penghargaan_url')
                    ->label('Berkas')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat Berkas' : '—')
                    ->url(fn ($record): ?string => $record->file_penghargaan_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('tanggal_sk_penghargaan')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada data penghargaan');
    }
}
