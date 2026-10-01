<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Retirement monitoring. Bucket boundaries mirror
 * PegawaiProfil::statusPensiun() exactly — 'lampau' past, 'warning' ≤1 year,
 * 'siaga' 1–2 years, 'aktif' beyond.
 */
class PensiunMonitor extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Monitor Pensiun';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Monitor Pensiun';

    protected string $view = 'filament.kepegawaian.pages.pensiun-monitor';

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $base = PegawaiProfil::query()->whereNotNull('tmt_bup_but');

        return [
            'total' => (clone $base)->count(),
            'warning' => (clone $base)->where('tmt_bup_but', '>', now())->where('tmt_bup_but', '<=', now()->addYear())->count(),
            'siaga' => (clone $base)->where('tmt_bup_but', '>', now()->addYear())->where('tmt_bup_but', '<=', now()->addYears(2))->count(),
            'lampau' => (clone $base)->where('tmt_bup_but', '<=', now())->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PegawaiProfil::query()->whereNotNull('tmt_bup_but'))
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->description(fn (PegawaiProfil $record): ?string => $record->nip)
                    ->searchable(['nama', 'nip', 'jabatan'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('jabatan')->label('Jabatan')->wrap()->placeholder('—'),
                TextColumn::make('golongan')->label('Gol.')->badge()->color('gray')->placeholder('—')->toggleable(),
                TextColumn::make('bup_but')->label('BUP')->placeholder('—')->toggleable(),
                TextColumn::make('tmt_bup_but')
                    ->label('TMT Pensiun')
                    ->date('d M Y')
                    ->description(fn (PegawaiProfil $record): ?string => $record->tmt_bup_but?->diffForHumans())
                    ->sortable(),
                TextColumn::make('status_pensiun')
                    ->label('Status')
                    ->badge()
                    ->state(fn (PegawaiProfil $record): string => $record->statusPensiun())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lampau' => 'Lampau',
                        'warning' => '< 1 Tahun',
                        'siaga' => '1–2 Tahun',
                        'aktif' => 'Aktif',
                        default => '—',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lampau' => 'danger',
                        'warning' => 'warning',
                        'siaga' => 'info',
                        'aktif' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('tmt_bup_but')
            ->filters([
                SelectFilter::make('tahun')
                    ->label('Tahun Pensiun')
                    ->options(fn (): array => PegawaiProfil::query()
                        ->whereNotNull('tmt_bup_but')
                        ->selectRaw('DISTINCT YEAR(tmt_bup_but) as y')
                        ->orderBy('y')
                        ->pluck('y', 'y')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $tahun) => $q->whereYear('tmt_bup_but', $tahun))),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'warning' => '< 1 Tahun',
                        'siaga' => '1–2 Tahun',
                        'lampau' => 'Lampau',
                        'aktif' => 'Aktif',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'warning' => $query->where('tmt_bup_but', '>', now())->where('tmt_bup_but', '<=', now()->addYear()),
                            'siaga' => $query->where('tmt_bup_but', '>', now()->addYear())->where('tmt_bup_but', '<=', now()->addYears(2)),
                            'lampau' => $query->where('tmt_bup_but', '<=', now()),
                            'aktif' => $query->where('tmt_bup_but', '>', now()->addYears(2)),
                            default => $query,
                        };
                    }),
            ])
            ->recordUrl(fn (PegawaiProfil $record): string => PegawaiProfilResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Tidak ada data pensiun');
    }
}
