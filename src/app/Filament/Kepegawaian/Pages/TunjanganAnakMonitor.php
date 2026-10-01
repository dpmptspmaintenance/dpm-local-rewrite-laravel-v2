<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\PegawaiAnak;
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
 * KP4 child-allowance monitoring. The SQL fragments mirror
 * PegawaiAnak::statusTunjangan()/batasUsiaTunjangan() so the aggregate counts
 * and the per-row badge can't disagree.
 */
class TunjanganAnakMonitor extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /** Usia anak dalam tahun (integer). */
    private const USIA_SQL = 'TIMESTAMPDIFF(YEAR, tanggal_lahir_anak, CURDATE())';

    /** Batas usia hak tunjangan: 25 bila kuliah, else 21. */
    private const BATAS_SQL = "CASE WHEN tingkat_pendidikan_anak IN ('Diploma III', 'Diploma IV', 'S-1', 'S-2', 'S-3') THEN 25 ELSE 21 END";

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Tunjangan Anak';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Monitor Tunjangan Anak';

    protected string $view = 'filament.kepegawaian.pages.tunjangan-anak-monitor';

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $base = PegawaiAnak::query()->whereNotNull('tanggal_lahir_anak');

        return [
            'total' => (clone $base)->count(),
            'aktif' => (clone $base)->whereRaw(self::USIA_SQL.' < '.self::BATAS_SQL.' - 1')->count(),
            'warning' => (clone $base)
                ->whereRaw(self::USIA_SQL.' >= '.self::BATAS_SQL.' - 1')
                ->whereRaw(self::USIA_SQL.' < '.self::BATAS_SQL)
                ->count(),
            'habis' => (clone $base)->whereRaw(self::USIA_SQL.' >= '.self::BATAS_SQL)->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PegawaiAnak::query()
                    ->whereNotNull('tanggal_lahir_anak')
                    ->with('profil')
            )
            ->columns([
                TextColumn::make('nama_anak')
                    ->label('Nama Anak')
                    ->weight('medium')
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where(function (Builder $sub) use ($search) {
                            $sub->where('nama_anak', 'like', "%{$search}%")
                                ->orWhere('pegawai_anak.nip', 'like', "%{$search}%")
                                ->orWhereHas('profil', fn (Builder $p) => $p->where('nama', 'like', "%{$search}%"));
                        })),
                TextColumn::make('profil.nama')
                    ->label('Pegawai')
                    ->description(fn (PegawaiAnak $record): ?string => $record->nip)
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('tanggal_lahir_anak')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('usia')
                    ->label('Usia')
                    ->state(fn (PegawaiAnak $record): string => $record->usiaTahun() !== null ? $record->usiaTahun().' th' : '—')
                    ->alignEnd(),
                TextColumn::make('batas')
                    ->label('Batas')
                    ->state(fn (PegawaiAnak $record): string => $record->batasUsiaTunjangan().' th')
                    ->description(fn (PegawaiAnak $record): string => $record->isKuliah() ? 'kuliah' : 'umum')
                    ->alignEnd(),
                TextColumn::make('tingkat_pendidikan_anak')
                    ->label('Pendidikan')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('tunjangan_anak')
                    ->label('Tunjangan')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status_tunjangan')
                    ->label('Status')
                    ->badge()
                    ->state(fn (PegawaiAnak $record): string => $record->statusTunjangan())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aktif' => 'Aktif',
                        'warning' => '< 1 Tahun',
                        'habis' => 'Habis',
                        default => '—',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'warning' => 'warning',
                        'habis' => 'danger',
                        default => 'gray',
                    }),
            ])
            // Yang paling dekat batas usia tampil duluan.
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw(self::BATAS_SQL.' - '.self::USIA_SQL.' ASC')
                ->orderBy('nama_anak'))
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Hak')
                    ->options([
                        'aktif' => 'Aktif',
                        'warning' => '< 1 Tahun',
                        'habis' => 'Habis',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'aktif' => $query->whereRaw(self::USIA_SQL.' < '.self::BATAS_SQL.' - 1'),
                            'warning' => $query->whereRaw(self::USIA_SQL.' >= '.self::BATAS_SQL.' - 1')
                                ->whereRaw(self::USIA_SQL.' < '.self::BATAS_SQL),
                            'habis' => $query->whereRaw(self::USIA_SQL.' >= '.self::BATAS_SQL),
                            default => $query,
                        };
                    }),
                SelectFilter::make('tunjangan_anak')
                    ->label('Tunjangan')
                    ->options(fn (): array => PegawaiAnak::query()
                        ->whereNotNull('tunjangan_anak')
                        ->where('tunjangan_anak', '!=', '')
                        ->distinct()
                        ->orderBy('tunjangan_anak')
                        ->pluck('tunjangan_anak', 'tunjangan_anak')
                        ->all()),
            ])
            ->recordUrl(fn (PegawaiAnak $record): ?string => $record->profil
                ? PegawaiProfilResource::getUrl('view', ['record' => $record->nip])
                : null)
            ->emptyStateHeading('Tidak ada data anak');
    }
}
