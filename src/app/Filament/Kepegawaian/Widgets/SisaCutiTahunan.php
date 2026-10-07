<?php

namespace App\Filament\Kepegawaian\Widgets;

use App\Models\Kepegawaian\Cuti;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Tabel sisa cuti 3 tahun terakhir untuk satu pegawai. Dipakai di tab
 * Riwayat Cuti (relation manager) detail pegawai, disisipkan lewat
 * Livewire::make() di content(). Angka memakai Cuti::rekapTahunan() yang
 * sama dengan halaman Rekap Cuti Tahunan.
 */
class SisaCutiTahunan extends TableWidget
{
    public ?string $nip = null;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        $akhir = (int) now()->year;
        $tahun = [$akhir - 2, $akhir - 1, $akhir];

        $record = $this->nip
            ? Cuti::rekapTahunan($tahun)->firstWhere('nip', $this->nip)
            : null;

        $rows = collect();
        if (is_array($record)) {
            foreach ($tahun as $i => $y) {
                $sisaTahun = (int) ($record['sisa_hari_'.$y] ?? 0);
                $terakhir = $i === array_key_last($tahun);
                $rows->push([
                    'tahun' => (string) $y,
                    'jatah' => (int) ($record['jatah_'.$y] ?? 0),
                    'terpakai' => (int) ($record['total_hari_'.$y] ?? 0),
                    'sisa' => $sisaTahun,
                    'bawa' => $terakhir ? $sisaTahun : min($sisaTahun, Cuti::MAX_BAWAAN),
                    'is_total' => false,
                ]);
            }

            // Baris TOTAL di bawah: total sisa cuti yang bisa dipakai berikutnya.
            $rows->push([
                'tahun' => 'TOTAL SISA',
                'jatah' => '',
                'terpakai' => '',
                'sisa' => '',
                'bawa' => (int) ($record['total_sisa'] ?? 0),
                'is_total' => true,
            ]);
        }

        return $table
            ->heading('Sisa Cuti 3 Tahun Terakhir')
            ->description('Carry/bawaan maks '.Cuti::MAX_BAWAAN.' hari, sisa total maks '.Cuti::MAX_SISA.' hari')
            ->records(fn () => $rows)
            ->paginated(false)
            ->columns([
                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->weight(fn ($state): string => $state === 'TOTAL SISA' ? 'bold' : 'medium')
                    ->color(fn ($state): string => $state === 'TOTAL SISA' ? 'primary' : 'gray'),
                TextColumn::make('jatah')
                    ->label('Jatah')
                    ->alignEnd(),
                TextColumn::make('terpakai')
                    ->label('Terpakai')
                    ->alignEnd(),
                TextColumn::make('sisa')
                    ->label('Sisa Tahun')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state === '' || $state === null) {
                            return '';
                        }
                        $n = (int) $state;

                        return $n < 0 ? 'hutang '.abs($n).' hari' : $n.' hari';
                    })
                    ->color(fn ($state): string => $state === '' || $state === null ? 'gray' : ((int) $state < 0 ? 'danger' : ((int) $state === 0 ? 'warning' : 'success')))
                    ->alignEnd(),
                TextColumn::make('bawa')
                    ->label('Dibawa ke Berikutnya')
                    ->weight(fn ($record): string => ($record['is_total'] ?? false) ? 'bold' : 'normal')
                    ->formatStateUsing(function ($state): string {
                        if ($state === '' || $state === null) {
                            return '';
                        }
                        $n = (int) $state;

                        return $n < 0 ? 'hutang '.abs($n).' hari' : $n.' hari';
                    })
                    ->color(fn ($record): string => ($record['is_total'] ?? false) ? 'primary' : 'gray')
                    ->alignEnd(),
            ])
            ->emptyStateHeading('Data rekap tidak tersedia');
    }
}
