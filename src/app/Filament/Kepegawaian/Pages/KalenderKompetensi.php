<?php

namespace App\Filament\Kepegawaian\Pages;

use App\Filament\Kepegawaian\Concerns\SelectsKompetensiTahun;
use App\Models\Kepegawaian\HariLibur;
use App\Models\Kepegawaian\PegawaiKompetensi;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Kalender tahunan pengembangan kompetensi — 12 bulan dalam satu halaman,
 * digambar sebagai kalender sungguhan: tiap event berupa bar yang membentang
 * dari tanggal mulai ke tanggal selesai, bukan chip per hari.
 *
 * Bar diletakkan dengan lane packing (greedy first-fit) per pekan, lalu
 * dirender sebagai elemen absolute berpersentase 7 kolom. Tanpa JS — semua
 * posisi dihitung di PHP supaya hasilnya sama dengan yang di-export/dicetak.
 *
 * Warna per jenis dipetakan lewat konstanta hex (bukan Tailwind) supaya tak
 * bergantung safelist asset Filament yang di-minify.
 */
class KalenderKompetensi extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use SelectsKompetensiTahun;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kalender Kompetensi';

    protected static string | \UnitEnum | null $navigationGroup = 'Kompetensi';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Kalender Pengembangan Kompetensi Tahunan';

    protected string $view = 'filament.kepegawaian.pages.kalender-kompetensi';

    /** @var array<string, string> Warna bar per jenis (hex, inline-style). */
    public const JENIS_COLORS = [
        'Seminar' => '#2563eb',
        'Workshop/Lokakarya' => '#d97706',
        'Kompetensi' => '#7c3aed',
        'Kursus' => '#0891b2',
        'Penataran' => '#e11d48',
        'Sertifikat Uji Kompetensi (khusus Fungsional)' => '#059669',
    ];

    public const DEFAULT_COLOR = '#6b7280';

    /** Tinggi bar event (px) — dipakai view untuk hitung tinggi baris pekan. */
    public const BAR_HEIGHT = 16;

    /**
     * Batas lane per pekan. Pekan tersibuk (Mei 2026) punya 20 bar yang saling
     * tumpang tindih; menampilkan semuanya bikin baris pekan setinggi ~350px.
     * Sisanya diringkas jadi satu baris "+N event lain" yang tetap bisa
     * diintip lewat tooltip.
     */
    public const MAX_LANES = 5;

    /** @var array<int, string> Nama bulan Indonesia (1..12). */
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Event kompetensi yang menyentuh satu tahun, sudah dinormalkan ke rentang
     * mulai→selesai (tanggal mulai/selesai bisa null — jatuh ke sertifikat).
     *
     * Satu diklat tercatat sekali per peserta (mis. "Festival ASN" 43 baris
     * dengan rentang identik), jadi baris digabung per nama+rentang+jenis dan
     * disimpan berapa peserta. Tanpa ini satu diklat menumpuk puluhan bar
     * identik dan lane-nya meledak jadi tak terbaca.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getEvents(): Collection
    {
        return PegawaiKompetensi::query()
            ->with('profil')
            ->whereRaw('YEAR(COALESCE(tanggal_mulai, tanggal_selesai, tanggal_sertifikat)) = ?', [$this->selectedTahun()])
            ->get()
            ->map(function (PegawaiKompetensi $k): array {
                $start = CarbonImmutable::instance($k->tanggal_mulai ?? $k->tanggal_selesai ?? $k->tanggal_sertifikat);
                $end = CarbonImmutable::instance($k->tanggal_selesai ?? $k->tanggal_mulai ?? $k->tanggal_sertifikat);

                // Rentang terbalik (data sumber) — balik agar bar tetap masuk akal.
                if ($end->lt($start)) {
                    [$start, $end] = [$end, $start];
                }

                return [
                    'start' => $start,
                    'end' => $end,
                    'nama' => $k->profil?->nama,
                    'jenis' => $k->jenis,
                    'nama_kompetensi' => $k->nama_kompetensi,
                    'penyelenggara' => $k->penyelenggara,
                    'jumlah_jam' => $k->jumlah_jam,
                    'color' => self::JENIS_COLORS[$k->jenis ?? ''] ?? self::DEFAULT_COLOR,
                ];
            })
            ->groupBy(fn (array $e) => implode('|', [
                mb_strtolower(trim((string) $e['nama_kompetensi'])),
                $e['start']->format('Y-m-d'),
                $e['end']->format('Y-m-d'),
                (string) $e['jenis'],
            ]))
            ->map(function (Collection $group): array {
                $first = $group->first();

                $first['peserta'] = $group->count();
                $first['peserta_nama'] = $group->pluck('nama')->filter()->unique()->take(8)->values()->all();
                $first['total_jam'] = $group->sum(fn (array $e) => (int) $e['jumlah_jam']);

                return $first;
            })
            ->values();
    }

    /** @return Collection<int, HariLibur> */
    public function getLibur(): Collection
    {
        return HariLibur::query()
            ->whereYear('tanggal', $this->selectedTahun())
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * Struktur 12 bulan siap-render. Tiap bulan punya daftar pekan; tiap pekan
     * punya 7 sel tanggal (termasuk tanggal bulan sebelah, ditandai
     * `in_month`) dan daftar bar yang sudah dapat lane.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildYear(): array
    {
        $tahun = $this->selectedTahun();
        $today = CarbonImmutable::today();

        $events = $this->getEvents();

        $liburByDate = $this->getLibur()
            ->mapWithKeys(fn (HariLibur $l) => [$l->tanggal->format('Y-m-d') => $l->keterangan])
            ->all();

        // Rentang grid setahun (Senin pekan pertama .. Minggu pekan terakhir),
        // supaya bar yang melewati batas bulan ikut tampil utuh di pekan itu.
        $gridStart = CarbonImmutable::create($tahun, 1, 1)->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = CarbonImmutable::create($tahun, 12, 31)->endOfWeek(CarbonImmutable::SUNDAY);

        $events = $events
            ->filter(fn (array $e) => $e['end']->gte($gridStart) && $e['start']->lte($gridEnd))
            ->values();

        $weeks = $this->buildWeekIndex($gridStart, $gridEnd, $events, $liburByDate, $today);

        // Bagi pekan ke bulan berdasarkan tanggal hari pertama bulan itu.
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $first = CarbonImmutable::create($tahun, $m, 1);
            $last = $first->endOfMonth();

            $monthWeeks = array_values(array_filter(
                $weeks,
                fn (array $w) => $w['start']->lte($last) && $w['end']->gte($first),
            ));

            $months[] = [
                'label' => self::NAMA_BULAN[$m].' '.$tahun,
                'weeks' => $monthWeeks,
                'jumlah_event' => $monthWeeks === [] ? 0 : $events
                    ->filter(fn (array $e) => $e['end']->gte($first) && $e['start']->lte($last))
                    ->count(),
            ];
        }

        return $months;
    }

    /**
     * Bikin daftar pekan lengkap dengan lane packing.
     *
     * @param  Collection<int, array<string, mixed>>  $events
     * @param  array<string, string>  $liburByDate
     * @return array<int, array<string, mixed>>
     */
    private function buildWeekIndex(
        CarbonImmutable $gridStart,
        CarbonImmutable $gridEnd,
        Collection $events,
        array $liburByDate,
        CarbonImmutable $today,
    ): array {
        $weeks = [];

        for ($weekStart = $gridStart; $weekStart->lte($gridEnd); $weekStart = $weekStart->addWeek()) {
            $weekEnd = $weekStart->addDays(6);

            $cells = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->addDays($i);
                $cells[] = [
                    'day' => $date->day,
                    'is_today' => $date->isSameDay($today),
                    'is_weekend' => $i >= 5,
                    'libur' => $liburByDate[$date->format('Y-m-d')] ?? null,
                ];
            }

            // Bar yang menyentuh pekan ini, terpanjang dulu supaya lane-nya
            // diisi dari blok besar ke kecil (hasil lebih rapi).
            $weekBars = $events
                ->filter(fn (array $e) => $e['end']->gte($weekStart) && $e['start']->lte($weekEnd))
                ->sortByDesc(fn (array $e) => $e['start']->diffInDays($e['end']))
                ->values();

            /** @var array<int, array<int, true>> $laneUsage lane => kolom (0..6) terpakai */
            $laneUsage = [];
            $bars = [];
            $overflow = [];

            foreach ($weekBars as $event) {
                $clippedStart = $event['start']->lt($weekStart) ? $weekStart : $event['start'];
                $clippedEnd = $event['end']->gt($weekEnd) ? $weekEnd : $event['end'];

                // diffInDays bertanda di Carbon 3 — hitung dari weekStart agar positif.
                $colStart = (int) $weekStart->diffInDays($clippedStart);
                $colEnd = (int) $weekStart->diffInDays($clippedEnd);

                $lane = $this->firstFreeLane($laneUsage, $colStart, $colEnd);
                if ($lane >= self::MAX_LANES) {
                    $overflow[] = $this->eventTooltip($event);

                    continue;
                }

                for ($c = $colStart; $c <= $colEnd; $c++) {
                    $laneUsage[$lane][$c] = true;
                }

                $bars[] = [
                    'lane' => $lane,
                    'col_start' => $colStart,
                    'span' => $colEnd - $colStart + 1,
                    'color' => $event['color'],
                    'label' => Str::limit((string) ($event['nama_kompetensi'] ?? '—'), 40),
                    'continues_left' => $event['start']->lt($weekStart),
                    'continues_right' => $event['end']->gt($weekEnd),
                    'tooltip' => $this->eventTooltip($event),
                    'jenis' => $event['jenis'],
                    'peserta' => $event['peserta'] ?? 1,
                ];
            }

            $weeks[] = [
                'start' => $weekStart,
                'end' => $weekEnd,
                'cells' => $cells,
                'bars' => $bars,
                'lanes' => $laneUsage === [] ? 0 : max(array_keys($laneUsage)) + 1,
                'overflow' => $overflow,
            ];
        }

        return $weeks;
    }

    /**
     * Lane kosong pertama untuk rentang kolom $colStart..$colEnd.
     *
     * @param  array<int, array<int, true>>  $laneUsage
     */
    private function firstFreeLane(array $laneUsage, int $colStart, int $colEnd): int
    {
        for ($lane = 0; ; $lane++) {
            $used = $laneUsage[$lane] ?? [];

            if ($used === []) {
                return $lane;
            }

            for ($c = $colStart; $c <= $colEnd; $c++) {
                if (isset($used[$c])) {
                    continue 2;
                }
            }

            return $lane;
        }
    }

    /** @param array<string, mixed> $event */
    private function eventTooltip(array $event): string
    {
        $peserta = (int) ($event['peserta'] ?? 1);
        $pesertaText = $peserta > 1
            ? $peserta.' peserta'
            : ((string) ($event['nama'] ?? 'Tanpa nama'));

        return implode(' | ', array_filter([
            (string) ($event['nama_kompetensi'] ?? '—'),
            $event['jenis'] ?? 'Tanpa jenis',
            $pesertaText,
            $event['penyelenggara'] ?? 'Tanpa penyelenggara',
            $event['start']->format('d/m/Y').' s/d '.$event['end']->format('d/m/Y'),
            ((int) $event['jumlah_jam']).' JP/peserta',
        ]));
    }

    /** @return array<int, string> Header Senin..Minggu. */
    public function dayHeaders(): array
    {
        return ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    }
}
