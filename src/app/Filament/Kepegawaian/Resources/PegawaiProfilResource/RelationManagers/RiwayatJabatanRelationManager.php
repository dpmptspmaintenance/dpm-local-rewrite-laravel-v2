<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RiwayatJabatanRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatJabatan';

    protected static ?string $title = 'Riwayat Jabatan';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-briefcase';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('jabatan_baru')
            ->columns([
                TextColumn::make('no_urut')->label('No.')->alignEnd()->sortable(),
                TextColumn::make('jabatan_baru')
                    ->label('Jabatan')
                    ->description(fn ($record): ?string => $record->jenis_jabatan)
                    ->wrap()
                    ->weight('medium')
                    ->placeholder('—'),
                TextColumn::make('nomor_sk_jabatan')->label('Nomor SK')->placeholder('—')->wrap(),
                TextColumn::make('tanggal_sk_jabatan')->label('Tanggal SK')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('tmt_sk_jabatan')->label('TMT')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('opd')->label('OPD')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('unit_kerja')->label('Unit Kerja')->placeholder('—')->wrap()->toggleable(),
                TextColumn::make('verifikasi')->label('Verifikasi')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('file_jabatan_url')
                    ->label('Berkas')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Lihat Berkas' : '—')
                    ->url(fn ($record): ?string => $record->file_jabatan_url)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('no_urut')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada riwayat jabatan');
    }
}
