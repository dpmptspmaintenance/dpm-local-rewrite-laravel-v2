<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\Kepegawaian\PegawaiRiwayatKgb;
use App\Models\Kepegawaian\PegawaiRiwayatPangkat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

/**
 * Rekap KGB (Kenaikan Gaji Berkala) & Kenaikan Pangkat per tahun, dikelompokkan
 * per bulan. Hanya untuk PNS (status_pegawai = PEGAWAI NEGERI SIPIL) — PPPK
 * tidak punya KGB maupun kenaikan pangkat. Satu kartu per bulan hanya muncul
 * bila bulan itu punya minimal satu baris KGB atau KP yang sedang ditampilkan —
 * bulan kosong sepenuhnya tidak digambar.
 *
 * Tanggal acuan: tmt_kgb (pegawai_riwayat_kgb) untuk KGB dan tmt_sk_pangkat
 * (pegawai_riwayat_pangkat) untuk KP — keduanya kolom date hasil impor SISDM.
 */
class RekapKgbKenaikanPangkat extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = 'Rekap KGB & Kenaikan Pangkat';

    protected static string | \UnitEnum | null $navigationGroup = 'Pegawai';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Rekap KGB & Kenaikan Pangkat';

    protected string $view = 'filament.kepegawaian.pages.rekap-kgb-kenaikan-pangkat';

    public ?array $data = [];

    /** @var array<int, string> Nama bulan Indonesia (1..12). */
    public const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function mount(): void
    {
        $this->form->fill([
            'tahun' => $this->selectedTahun(),
            'tampil_kgb' => true,
            'tampil_kp' => true,
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
                Toggle::make('tampil_kgb')
                    ->label('Tampilkan KGB')
                    ->default(true)
                    ->live(),
                Toggle::make('tampil_kp')
                    ->label('Tampilkan Kenaikan Pangkat')
                    ->default(true)
                    ->live(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    /** Apakah blok KGB sedang ditampilkan? */
    protected function tampilKgb(): bool
    {
        return (bool) ($this->data['tampil_kgb'] ?? true);
    }

    /** Apakah blok Kenaikan Pangkat sedang ditampilkan? */
    protected function tampilKp(): bool
    {
        return (bool) ($this->data['tampil_kp'] ?? true);
    }

    /**
     * NIP seluruh PNS aktif — batasan modul: KGB & kenaikan pangkat hanya
     * berlaku untuk PNS, jadi semua query di halaman ini disaring ke daftar
     * NIP ini.
     *
     * @return Collection<int, string>
     */
    protected function nipsPns(): Collection
    {
        return PegawaiProfil::query()
            ->where('status_pegawai', PegawaiProfil::STATUS_PNS)
            ->pluck('nip');
    }

    protected function selectedTahun(): int
    {
        $options = $this->tahunOptions();
        $dipilih = (int) ($this->data['tahun'] ?? 0);

        if (isset($options[$dipilih])) {
            return $dipilih;
        }

        // Default: tahun berjalan bila punya data, selain itu tahun terbaru.
        return isset($options[(int) now()->year])
            ? (int) now()->year
            : (int) (array_key_first($options) ?? now()->year);
    }

    /**
     * Tahun yang punya data: KGB riwayat, KP riwayat, ATAU jadwal KGB
     * berikutnya (pegawai_profil.kgb_selanjutnya yang belum jadi baris
     * riwayat). Nilai junk (mis. 2045) dibuang dengan clamp 2000 ..
     * tahun berjalan + 3, urut menurun.
     *
     * @return array<int, int> tahun => tahun
     */
    protected function tahunOptions(): array
    {
        $nips = $this->nipsPns();

        $years = PegawaiRiwayatKgb::query()
            ->whereIn('nip', $nips)
            ->whereNotNull('tmt_kgb')
            ->selectRaw('DISTINCT YEAR(tmt_kgb) as y')
            ->pluck('y')
            ->merge(PegawaiRiwayatPangkat::query()
                ->whereIn('nip', $nips)
                ->whereNotNull('tmt_sk_pangkat')
                ->selectRaw('DISTINCT YEAR(tmt_sk_pangkat) as y')
                ->pluck('y'))
            ->merge(PegawaiProfil::query()
                ->whereIn('nip', $nips)
                ->whereNotNull('kgb_selanjutnya')
                ->selectRaw('DISTINCT YEAR(kgb_selanjutnya) as y')
                ->pluck('y'))
            ->map(fn ($y) => (int) $y)
            ->filter(fn (int $y) => $y >= 2000 && $y <= now()->year + 3)
            ->unique()
            ->sortDesc();

        return $years->mapWithKeys(fn (int $y) => [$y => $y])->all();
    }

    /**
     * Kartu per bulan untuk tahun terpilih — bulan tanpa KGB, KP, maupun jadwal
     * KGB berikutnya yang sedang ditampilkan dilewati sehingga kartu kosong tak
     * pernah muncul. Toggle "tampil_kgb"/"tampil_kp" menyaring sekaligus.
     *
     * Baris riwayat dideduplikasi dulu: sumber SISDM kadang memuat dua baris
     * identik (NIP + TMT + golongan + nomor SK sama, beda no_urut/masa kerja) —
     * yang dipakai baris dengan tanggal_sk_kgb paling baru.
     *
     * @return array<int, array{
     *     nomor: int,
     *     label: string,
     *     lewat: bool,
     *     kgb: Collection<int, PegawaiRiwayatKgb>,
     *     kgb_jadwal: Collection<int, PegawaiProfil>,
     *     kp: Collection<int, PegawaiRiwayatPangkat>,
     * }>
     */
    public function buildMonths(): array
    {
        $tahun = $this->selectedTahun();
        $nips = $this->nipsPns();

        // Riwayat KGB (sudah bersih dari duplikat), dikelompokkan per bulan.
        $kgb = $this->tampilKgb()
            ? PegawaiRiwayatKgb::query()
                ->with('profil')
                ->whereIn('nip', $nips)
                ->whereYear('tmt_kgb', $tahun)
                ->orderBy('tmt_kgb')
                ->get()
                ->groupBy(fn (PegawaiRiwayatKgb $r): string => implode('|', [
                    $r->nip,
                    $r->tmt_kgb?->format('Y-m-d'),
                    (string) $r->golongan_kgb,
                    (string) $r->nomor_sk_kgb,
                ]))
                ->map(fn (Collection $g) => $g->sortByDesc(fn (PegawaiRiwayatKgb $r): int => $r->tanggal_sk_kgb?->timestamp ?? 0)->first())
                ->groupBy(fn (PegawaiRiwayatKgb $r): int => (int) $r->tmt_kgb->month)
            : collect();

        // Jadwal KGB berikutnya dari profil — belum ada SK, apare di blok KGB
        // bulan terkait. Baris yang NIP+TMT-nya sudah ada di riwayat dibuang
        // supaya tak tampil dobel.
        $jadwal = $this->tampilKgb()
            ? PegawaiProfil::query()
                ->whereIn('nip', $nips)
                ->whereYear('kgb_selanjutnya', $tahun)
                ->orderBy('kgb_selanjutnya')
                ->get()
                ->reject(function (PegawaiProfil $p) use ($kgb): bool {
                    $bulan = (int) $p->kgb_selanjutnya->month;

                    return $kgb->get($bulan, collect())->contains(fn (PegawaiRiwayatKgb $r): bool => $r->nip === $p->nip);
                })
                ->groupBy(fn (PegawaiProfil $p): int => (int) $p->kgb_selanjutnya->month)
            : collect();

        $kp = $this->tampilKp()
            ? PegawaiRiwayatPangkat::query()
                ->with('profil')
                ->whereIn('nip', $nips)
                ->whereYear('tmt_sk_pangkat', $tahun)
                ->orderBy('tmt_sk_pangkat')
                ->get()
                ->groupBy(fn (PegawaiRiwayatPangkat $r): string => implode('|', [
                    $r->nip,
                    $r->tmt_sk_pangkat?->format('Y-m-d'),
                    (string) $r->golongan,
                    (string) $r->nomor_sk_pangkat,
                ]))
                ->map(fn (Collection $g) => $g->sortByDesc(fn (PegawaiRiwayatPangkat $r): int => $r->tanggal_sk_pangkat?->timestamp ?? 0)->first())
                ->groupBy(fn (PegawaiRiwayatPangkat $r): int => (int) $r->tmt_sk_pangkat->month)
            : collect();

        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $kgbBulan = $kgb->get($m, collect());
            $jadwalBulan = $jadwal->get($m, collect());
            $kpBulan = $kp->get($m, collect());

            if ($kgbBulan->isEmpty() && $jadwalBulan->isEmpty() && $kpBulan->isEmpty()) {
                continue;
            }

            $months[] = [
                'nomor' => $m,
                'label' => self::NAMA_BULAN[$m].' '.$tahun,
                'lewat' => $this->bulanLewat($m, $tahun),
                'kgb' => $kgbBulan->values(),
                'kgb_jadwal' => $jadwalBulan->values(),
                'kp' => $kpBulan->values(),
            ];
        }

        // Bulan yang belum terlewati (berjalan/mendatang) di atas, bulan yang
        // sudah lewat ditaruh di bawah — urutan tetap kronologis di tiap grup.
        usort($months, fn (array $a, array $b): int => ($a['lewat'] <=> $b['lewat']) ?: ($a['nomor'] <=> $b['nomor']));

        return $months;
    }

    /**
     * Apakah bulan ini sudah terlewati relatif sekarang? Bulan berjalan
     * dihitung BELUM lewat (masih diproses), bukan lewat.
     */
    private function bulanLewat(int $bulan, int $tahun): bool
    {
        $sekarang = now();

        if ($tahun !== (int) $sekarang->year) {
            return $tahun < (int) $sekarang->year;
        }

        return $bulan < (int) $sekarang->month;
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $months = $this->buildMonths();

        return [
            // Hitung baris yang benar-benar tampil (sudah dedup + jadwal
            // berikutnya) supaya angka kartu konsisten dengan isi bulan.
            'kgb' => collect($months)->sum(fn (array $m): int => $m['kgb']->count() + $m['kgb_jadwal']->count()),
            'kp' => collect($months)->sum(fn (array $m): int => $m['kp']->count()),
            'bulan' => count($months),
        ];
    }
}
