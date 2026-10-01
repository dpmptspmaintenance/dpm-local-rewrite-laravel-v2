<?php

namespace App\Filament\Kepegawaian\Concerns;

use App\Models\Kepegawaian\HariLibur;
use App\Models\Kepegawaian\PegawaiKompetensi;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/**
 * Filter tahun bersama untuk halaman yang membaca pegawai_kompetensi: kalender,
 * peta kebutuhan, dan matriks diklat. Satu definisi "tahun" dipakai ketiganya
 * supaya tak pernah beda soal baris mana yang masuk tahun mana.
 *
 * Tanggal acuan = tanggal_mulai, jatuh ke tanggal_selesai, lalu
 * tanggal_sertifikat (dua kolom pertama bisa null di data sumber).
 */
trait SelectsKompetensiTahun
{
    public const TANGGAL_ACUAN = 'COALESCE(tanggal_mulai, tanggal_selesai, tanggal_sertifikat)';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tahun' => $this->tahunDefault(),
        ]);
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

    protected function tahunDefault(): int
    {
        $options = $this->tahunOptions();

        return in_array((int) now()->year, $options, true)
            ? (int) now()->year
            : (int) (array_key_first($options) ?? now()->year);
    }

    protected function selectedTahun(): int
    {
        return (int) ($this->data['tahun'] ?? $this->tahunDefault());
    }

    /**
     * Tahun yang punya data (kompetensi ∪ hari libur), dibersihkan dari nilai
     * junk (mis. 1026/2092) dengan clamp 2000 .. tahun berjalan + 1.
     *
     * @return array<int, int> tahun => tahun, urut menurun
     */
    protected function tahunOptions(): array
    {
        $years = PegawaiKompetensi::query()
            ->whereRaw(self::TANGGAL_ACUAN.' IS NOT NULL')
            ->selectRaw('DISTINCT YEAR('.self::TANGGAL_ACUAN.') as y')
            ->pluck('y')
            ->merge(HariLibur::query()->selectRaw('DISTINCT YEAR(tanggal) as y')->pluck('y'))
            ->map(fn ($y) => (int) $y)
            ->filter(fn (int $y) => $y >= 2000 && $y <= now()->year + 1)
            ->unique()
            ->sortDesc();

        return $years->mapWithKeys(fn (int $y) => [$y => $y])->all();
    }
}
