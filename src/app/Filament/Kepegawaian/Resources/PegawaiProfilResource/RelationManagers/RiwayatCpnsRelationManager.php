<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Riwayat CPNS — satu baris per pegawai (relasi hasOne), ditampilkan sebagai
 * tab tersendiri seperti riwayat lain. Tab read-only (data dari impor SISDM).
 */
class RiwayatCpnsRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatCpns';

    protected static ?string $title = 'Riwayat CPNS';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-academic-cap';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('golongan')->label('Golongan')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('jabatan')->label('Jabatan')->placeholder('—')->wrap(),
                TextColumn::make('nomor_sk')->label('Nomor SK')->placeholder('—')->wrap(),
                TextColumn::make('tanggal_sk')->label('Tanggal SK')->date('d M Y')->placeholder('—'),
                TextColumn::make('tmt_sk')->label('TMT')->date('d M Y')->placeholder('—'),
                TextColumn::make('gaji')->label('Gaji')->placeholder('—')->toggleable(),
                TextColumn::make('masa_kerja_tahun')->label('MK (Th)')->placeholder('—')->toggleable(),
                TextColumn::make('masa_kerja_bulan')->label('MK (Bl)')->placeholder('—')->toggleable(),
                TextColumn::make('unit_kerja')->label('Unit Kerja')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('file_url')
                    ->label('Berkas')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat Berkas' : '—')
                    ->url(fn ($record): ?string => $record->file_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada riwayat CPNS');
    }
}
