<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Resources\PegawaiProfilResource;
use App\Models\Kepegawaian\Cuti;
use App\Models\Kepegawaian\CutiKuotaTahunan;
use App\Models\Kepegawaian\PegawaiProfil;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Input kuota cuti tahunan: daftar semua pegawai (default hanya yang aktif),
 * kuota tahun terpilih diedit langsung di tabel (per baris) atau sekaligus
 * lewat bulk action untuk beberapa pegawai. Tanpa baris di cuti_kuota_tahunan
 * untuk (nip, tahun), jatahnya dianggap Cuti::KUOTA_TAHUNAN — tabel ini murni
 * override, kosong itu wajar untuk sebagian besar pegawai.
 *
 * Kasus yang jadi alasan fitur ini: PPPK/CPNS yang mulai 2025 dapat 0 hari di
 * 2025, dan cuti yang diambil tahun itu jadi hutang yang mengurangi jatah 2026
 * (lihat Cuti::rekapTahunan()).
 */
class KuotaCutiTahunan extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Kuota Cuti Tahunan';

    protected static string | \UnitEnum | null $navigationGroup = 'Cuti';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Kuota Cuti Tahunan';

    protected string $view = 'filament.kepegawaian.pages.kuota-cuti-tahunan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['tahun' => now()->year]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => $this->tahunOptions())
                    ->required()
                    ->live(),
            ])
            ->statePath('data');
    }

    /** @return array<int, int> Tahun berjalan ± 2, menurun. */
    private function tahunOptions(): array
    {
        $sekarang = (int) now()->year;

        return collect(range($sekarang + 2, $sekarang - 2))
            ->mapWithKeys(fn (int $y) => [$y => $y])
            ->all();
    }

    private function selectedTahun(): int
    {
        return (int) ($this->data['tahun'] ?? now()->year);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PegawaiProfil::query()
                ->leftJoin('cuti_kuota_tahunan', function ($join) {
                    $join->on('cuti_kuota_tahunan.nip', '=', 'pegawai_profil.nip')
                        ->where('cuti_kuota_tahunan.tahun', $this->selectedTahun());
                })
                ->select('pegawai_profil.*')
                ->selectRaw('COALESCE(cuti_kuota_tahunan.kuota_hari, ?) as kuota_hari', [Cuti::KUOTA_TAHUNAN])
                ->selectRaw('cuti_kuota_tahunan.keterangan as keterangan_kuota')
                ->selectRaw('(cuti_kuota_tahunan.id IS NOT NULL) as ada_override'))
            ->columns([
                TextColumn::make('nama')
                    ->label('Pegawai')
                    ->description(fn (PegawaiProfil $record): ?string => $record->nip)
                    ->searchable(['pegawai_profil.nama', 'pegawai_profil.nip'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status_pegawai')
                    ->label('Status')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextInputColumn::make('kuota_hari')
                    ->label(fn (): string => 'Kuota '.$this->selectedTahun())
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->updateStateUsing(function (PegawaiProfil $record, $state): int {
                        $kuota = max(0, (int) $state);

                        CutiKuotaTahunan::updateOrCreate(
                            ['nip' => $record->nip, 'tahun' => $this->selectedTahun()],
                            ['kuota_hari' => $kuota],
                        );

                        return $kuota;
                    }),
                TextInputColumn::make('keterangan_kuota')
                    ->label('Keterangan')
                    ->placeholder('mis. PPPK masuk Mei 2025')
                    ->updateStateUsing(function (PegawaiProfil $record, $state): ?string {
                        $keterangan = trim((string) $state) === '' ? null : trim((string) $state);

                        CutiKuotaTahunan::updateOrCreate(
                            ['nip' => $record->nip, 'tahun' => $this->selectedTahun()],
                            ['keterangan' => $keterangan],
                        );

                        return $keterangan;
                    })
                    ->toggleable(),
                TextColumn::make('ada_override')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ((int) $state === 1) ? 'Override' : 'Default '.Cuti::KUOTA_TAHUNAN.' hari')
                    ->color(fn ($state): string => ((int) $state === 1) ? 'warning' : 'gray')
                    ->toggleable(),
            ])
            ->defaultSort('pegawai_profil.nama')
            ->paginated([10, 25, 50, 100, 'all'])
            ->filters([
                // Blank (default, belum disentuh user) sama seperti "true":
                // hanya pegawai aktif — konsisten dengan Cuti::rekapTahunan().
                TernaryFilter::make('aktif')
                    ->label('Status Pengguna')
                    ->placeholder('Hanya aktif (bawaan)')
                    ->trueLabel('Hanya aktif')
                    ->falseLabel('Semua (termasuk nonaktif)')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereIn('pegawai_profil.nip', PegawaiProfil::nipAktif()),
                        false: fn (Builder $query): Builder => $query,
                        blank: fn (Builder $query): Builder => $query->whereIn('pegawai_profil.nip', PegawaiProfil::nipAktif()),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('setKuota')
                        ->label('Atur Kuota Terpilih')
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            TextInput::make('kuota_hari')
                                ->label(fn (): string => 'Kuota '.$this->selectedTahun().' (hari)')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                            TextInput::make('keterangan')
                                ->label('Keterangan')
                                ->maxLength(255)
                                ->helperText('Kosongkan untuk tidak mengubah keterangan yang sudah ada.'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $tahun = $this->selectedTahun();
                            $kuota = max(0, (int) $data['kuota_hari']);
                            $keterangan = trim((string) ($data['keterangan'] ?? ''));

                            foreach ($records as $record) {
                                $payload = ['kuota_hari' => $kuota];
                                if ($keterangan !== '') {
                                    $payload['keterangan'] = $keterangan;
                                }

                                CutiKuotaTahunan::updateOrCreate(
                                    ['nip' => $record->nip, 'tahun' => $tahun],
                                    $payload,
                                );
                            }

                            Notification::make()
                                ->title('Kuota '.$tahun.' diperbarui untuk '.$records->count().' pegawai')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->recordUrl(fn (PegawaiProfil $record): string => PegawaiProfilResource::getUrl('view', ['record' => $record->nip]))
            ->emptyStateHeading('Tidak ada pegawai');
    }
}
