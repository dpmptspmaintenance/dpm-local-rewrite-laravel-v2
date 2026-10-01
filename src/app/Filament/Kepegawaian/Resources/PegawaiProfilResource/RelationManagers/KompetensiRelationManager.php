<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use App\Models\Kepegawaian\PegawaiKompetensi;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KompetensiRelationManager extends RelationManager
{
    protected static string $relationship = 'kompetensi';

    protected static ?string $title = 'Riwayat Kompetensi';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-academic-cap';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_kompetensi')
            ->columns([
                TextColumn::make('nama_kompetensi')
                    ->label('Nama Kompetensi')
                    ->wrap()
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('jenis')->label('Jenis')->badge()->color('gray')->placeholder('—'),
                ToggleColumn::make('sesuai_jabatan')
                    ->label('Sesuai Jabatan')
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip(fn (bool $state): string => $state
                        ? 'Sesuai kebutuhan jabatan — klik untuk tandai tidak sesuai'
                        : 'Tidak sesuai jabatan — klik untuk tandai sesuai'),
                TextColumn::make('penyelenggara')->label('Penyelenggara')->wrap()->placeholder('—'),
                TextColumn::make('jumlah_jam')->label('JP')->alignEnd()->placeholder('—'),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y')->sortable()->placeholder('—'),
                TextColumn::make('nomor_sertifikat')->label('No. Sertifikat')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_sertifikat')->label('Tgl. Sertifikat')->date('d M Y')->sortable()->placeholder('—')->toggleable(),
            ])
            ->defaultSort('tanggal_sertifikat', 'desc')
            ->filters([
                // Opsi tahun dari kompetensi milik pegawai INI saja (bukan
                // seluruh tabel) — sama pola dengan filter tahun di
                // CutiRelationManager. TANGGAL_ACUAN dipakai (bukan cuma
                // tanggal_sertifikat) supaya "tahun" di sini berarti sama
                // dengan Kalender/Peta/Matriks/Evaluasi Kesesuaian Diklat.
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => $this->getOwnerRecord()->kompetensi()
                        ->selectRaw('DISTINCT YEAR('.PegawaiKompetensi::TANGGAL_ACUAN.') as y')
                        ->orderByDesc('y')
                        ->pluck('y', 'y')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $year) => $q->whereRaw('YEAR('.PegawaiKompetensi::TANGGAL_ACUAN.') = ?', [$year]),
                        )),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada riwayat kompetensi');
    }
}
