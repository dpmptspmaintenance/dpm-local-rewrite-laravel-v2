<?php

namespace App\Filament\Kepegawaian\Resources\RekapAbsenResource\RelationManagers;

use App\Models\Kepegawaian\RekapAbsenHarian;
use App\Services\Kepegawaian\RekapAbsenService;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rincian presensi harian pada halaman detail rekap. Satu-satunya aksi
 * tulis di modul ini: hapus baris harian (mis. unggahan keliru). Ringkasan
 * header langsung dihitung ulang oleh RekapAbsenService::hitungUlangRingkasan().
 */
class HarianRelationManager extends RelationManager
{
    protected static string $relationship = 'harian';

    protected static ?string $title = 'Absensi Harian';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('hari')
                    ->label('Hari')
                    ->placeholder('—'),
                TextColumn::make('jam_hadir')
                    ->label('Jam Hadir')
                    ->dateTime('H:i:s')
                    ->placeholder('—'),
                TextColumn::make('hadir_selisih')
                    ->label('+/-')
                    ->placeholder('—'),
                TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->dateTime('H:i:s')
                    ->placeholder('—'),
                TextColumn::make('pulang_selisih')
                    ->label('+/-')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'warning' : 'gray')
                    ->placeholder('—'),
                TextColumn::make('lampiran')
                    ->label('Lampiran')
                    ->state(fn (RekapAbsenHarian $record): ?string => $record->tautanLampiran())
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Lihat' : '—')
                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('rekapAbsen.dataDukung'))
            ->defaultSort('urutan')
            ->recordActions([
                DeleteAction::make()
                    ->label('Hapus')
                    ->authorize(true)
                    ->requiresConfirmation()
                    ->modalHeading('Hapus baris presensi?')
                    ->modalDescription('Baris ini dihapus dari rekap. Ringkasan hadir/tidak hadir/konfirmasi dihitung ulang otomatis.')
                    ->after(function (): void {
                        app(RekapAbsenService::class)->hitungUlangRingkasan($this->getOwnerRecord());

                        $this->dispatch('rekap-harian-dihapus');
                    }),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada rincian harian');
    }
}
