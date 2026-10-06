<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RiwayatPangkatRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatPangkat';

    protected static ?string $title = 'Riwayat Pangkat';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-arrow-trending-up';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pangkat')
            ->columns([
                TextColumn::make('no_urut')->label('No.')->alignEnd()->sortable(),
                TextColumn::make('pangkat')
                    ->label('Pangkat')
                    ->description(fn ($record): ?string => $record->golongan)
                    ->wrap()
                    ->weight('medium')
                    ->placeholder('—'),
                TextColumn::make('jenis_kenaikan_pangkat')->label('Jenis Kenaikan')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('nomor_sk_pangkat')->label('Nomor SK')->placeholder('—')->wrap(),
                TextColumn::make('tanggal_sk_pangkat')->label('Tanggal SK')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('tmt_sk_pangkat')->label('TMT')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('masa_kerja_tahun')->label('MK (Th)')->placeholder('—')->toggleable(),
                TextColumn::make('masa_kerja_bulan')->label('MK (Bl)')->placeholder('—')->toggleable(),
                TextColumn::make('verifikasi')->label('Verifikasi')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('file_pangkat_url')
                    ->label('Berkas')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat Berkas' : '—')
                    ->url(fn ($record): ?string => $record->file_pangkat_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('no_urut')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada riwayat pangkat');
    }
}
