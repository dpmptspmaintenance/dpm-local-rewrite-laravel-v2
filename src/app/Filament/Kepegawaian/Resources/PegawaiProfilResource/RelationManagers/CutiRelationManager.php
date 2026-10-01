<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use App\Models\Kepegawaian\Cuti;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CutiRelationManager extends RelationManager
{
    protected static string $relationship = 'cuti';

    protected static ?string $title = 'Riwayat Cuti';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-calendar-days';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_surat')
            ->columns([
                TextColumn::make('tanggal_mulai_diajukan')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('tanggal_selesai_diajukan')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jumlah_hari')
                    ->label('Hari')
                    // ->state(), bukan ->formatStateUsing(): kolom ini tak
                    // punya kolom asli di DB, dan TextColumn melewati
                    // formatStateUsing sepenuhnya bila state mentah dari
                    // record blank/null (lihat catatan sama di CutiTahunanRekap).
                    ->state(fn (Cuti $record): int => $record->jumlahHari())
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (int $state): string => $state.' hari')
                    ->alignEnd(),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Cuti Tahunan' => 'info',
                        'Cuti Sakit' => 'warning',
                        'Cuti Melahirkan' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('no_surat')
                    ->label('No. Surat')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('keperluan')
                    ->label('Keperluan')
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->defaultSort('tanggal_mulai_diajukan', 'desc')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => $this->getOwnerRecord()->cuti()
                        ->selectRaw('DISTINCT YEAR(tanggal_mulai_diajukan) as y')
                        ->orderByDesc('y')
                        ->pluck('y', 'y')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $year) => $q->whereYear('tanggal_mulai_diajukan', $year))),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada riwayat cuti');
    }
}
