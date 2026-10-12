<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RiwayatKgbRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatKgb';

    protected static ?string $title = 'Riwayat KGB';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-banknotes';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor_sk_kgb')
            ->columns([
                TextColumn::make('no_urut')->label('No.')->alignEnd()->sortable(),
                TextColumn::make('golongan_kgb')
                    ->label('Golongan')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('masa_kerja_kgb')->label('Masa Kerja')->placeholder('—')->wrap(),
                TextColumn::make('gaji_lama')
                    ->label('Gaji Lama')
                    ->formatStateUsing(fn (?string $state): string => self::rupiah($state))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('gaji_baru')
                    ->label('Gaji Baru')
                    ->formatStateUsing(fn (?string $state): string => self::rupiah($state))
                    ->placeholder('—'),
                TextColumn::make('nomor_sk_kgb')->label('Nomor SK')->placeholder('—')->wrap(),
                TextColumn::make('tanggal_sk_kgb')->label('Tanggal SK')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('tmt_kgb')->label('TMT')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('unit_kerja_kgb')->label('Unit Kerja')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('verifikasi')->label('Verifikasi')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('file_kgb_url')
                    ->label('Berkas')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat Berkas' : '—')
                    ->url(fn ($record): ?string => $record->file_kgb_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('no_urut')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada riwayat KGB');
    }

    /** Format digit nominal ("5022500") jadi tampilan rupiah ("Rp. 5.022.500"). */
    private static function rupiah(?string $state): string
    {
        if ($state === null || ! is_numeric($state)) {
            return '—';
        }

        return 'Rp. '.number_format((float) $state, 0, ',', '.');
    }
}
