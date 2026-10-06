<?php

namespace App\Filament\Kepegawaian\Resources\PegawaiProfilResource\RelationManagers;

use App\Filament\Kepegawaian\Widgets\SisaCutiTahunan;
use App\Models\Kepegawaian\Cuti;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CutiRelationManager extends RelationManager
{
    protected static string $relationship = 'cuti';

    protected static ?string $title = 'Riwayat Cuti';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-calendar-days';

    /**
     * Sisipkan tabel ringkasan "Sisa Cuti 3 Tahun Terakhir" di atas tabel
     * riwayat cuti. Perhitungan memakai Cuti::rekapTahunan() (sama dengan
     * halaman Rekap Cuti Tahunan) supaya angkanya konsisten.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Livewire::make(SisaCutiTahunan::class, fn (): array => [
                'nip' => $this->getOwnerRecord()->nip,
            ]),
            RenderHook::make(\Filament\View\PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(\Filament\View\PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER),
        ]);
    }

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
            ->headerActions([
                Action::make('copyCuti')
                    ->label('Copy Cuti')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->action(function (): void {
                        $record = $this->getOwnerRecord();

                        // Ikut filter yang sedang aktif di tabel (mis. tahun),
                        // urut sama dengan tampilan (terbaru dulu).
                        $rows = $this->getFilteredTableQuery()
                            ->reorder()
                            ->orderByDesc('tanggal_mulai_diajukan')
                            ->get();

                        if ($rows->isEmpty()) {
                            Notification::make()
                                ->title('Tidak ada data cuti untuk disalin')
                                ->warning()
                                ->send();

                            return;
                        }

                        $lines = [];
                        $lines[] = 'RIWAYAT CUTI — '.($record->nama ?? $record->nip);
                        $lines[] = '';
                        foreach ($rows as $i => $c) {
                            $mulai = $c->tanggal_mulai_diajukan ? \Illuminate\Support\Carbon::parse($c->tanggal_mulai_diajukan)->format('d-m-Y') : '—';
                            $selesai = $c->tanggal_selesai_diajukan ? \Illuminate\Support\Carbon::parse($c->tanggal_selesai_diajukan)->format('d-m-Y') : '—';
                            $lines[] = ($i + 1).'. '.($c->jenis ?? '-')
                                ." | {$mulai} s.d. {$selesai}"
                                .' ('.$c->jumlahHari().' hari)'
                                .($c->no_surat ? ' | No. Surat: '.$c->no_surat : '')
                                .($c->keperluan ? ' | '.$c->keperluan : '');
                        }
                        $text = implode("\n", $lines);

                        $this->js('navigator.clipboard.writeText('.json_encode($text).')');

                        Notification::make()
                            ->title('Riwayat cuti disalin ('.$rows->count().' baris)')
                            ->success()
                            ->send();
                    }),
            ])
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
                SelectFilter::make('jenis')
                    ->label('Jenis Cuti')
                    ->options(fn (): array => $this->getOwnerRecord()->cuti()
                        ->whereNotNull('jenis')
                        ->where('jenis', '!=', '')
                        ->distinct()
                        ->orderBy('jenis')
                        ->pluck('jenis', 'jenis')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $jenis) => $q->where('jenis', $jenis))),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada riwayat cuti');
    }
}
