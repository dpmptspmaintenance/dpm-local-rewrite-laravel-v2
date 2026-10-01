<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class PegawaiKompetensi extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_kompetensi';

    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_sertifikat' => 'date',
            'sesuai_jabatan' => 'boolean',
        ];
    }

    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }

    /**
     * Shared filter logic for the kompetensi index page and its Excel
     * export, so the two never drift apart on what "filtered" means.
     *
     * @param  array{q?: string, tahun?: int|string, jenis?: string}  $filters
     */
    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        if (! blank($filters['q'] ?? null)) {
            $search = $filters['q'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                    ->orWhere('nama_kompetensi', 'like', "%{$search}%")
                    ->orWhere('penyelenggara', 'like', "%{$search}%")
                    ->orWhere('nomor_sertifikat', 'like', "%{$search}%")
                    ->orWhereHas('profil', fn (Builder $p) => $p->where('nama', 'like', "%{$search}%"));
            });
        }

        if (! blank($filters['tahun'] ?? null)) {
            $query->whereYear('tanggal_sertifikat', (int) $filters['tahun']);
        }

        if (! blank($filters['jenis'] ?? null)) {
            $query->where('jenis', $filters['jenis']);
        }

        return $query;
    }

    /**
     * Tanggal acuan tahun: mulai → selesai → sertifikat (dua yang pertama bisa
     * null di data sumber). Dipakai kalender, peta kebutuhan, dan matriks diklat
     * supaya ketiganya menghitung "tahun" dengan cara sama.
     */
    public const TANGGAL_ACUAN = 'COALESCE(tanggal_mulai, tanggal_selesai, tanggal_sertifikat)';

    /**
     * Rekap kompetensi per jabatan untuk satu tahun: berapa pegawai di jabatan
     * itu, berapa pegawai yang punya minimal satu kompetensi, berapa event, dan
     * total JP. Baris dihitung per (jabatan) supaya "peta kebutuhan" bisa
     * menandai jabatan yang pesertanya masih sedikit.
     *
     * Jabatan NULL/kosong dikelompokkan jadi satu baris "(Tanpa jabatan)" agar
     * tidak hilang dari rekap.
     */
    public function scopeRekapJabatan(Builder $query, int $tahun): Builder
    {
        $acuan = self::TANGGAL_ACUAN;

        return $query
            ->join('pegawai_profil', 'pegawai_profil.nip', '=', 'pegawai_kompetensi.nip')
            ->whereRaw("YEAR({$acuan}) = ?", [$tahun])
            // MIN(id) memberi key rekord stabil untuk tabel Filament — hasil
            // agregat tak punya id sendiri, dan tanpa ini Filament gagal
            // membangun record key (return null). Jabatan sudah unik per baris.
            ->selectRaw('MIN(pegawai_kompetensi.id) as id')
            ->selectRaw("COALESCE(NULLIF(TRIM(pegawai_profil.jabatan), ''), '(Tanpa jabatan)') as jabatan")
            ->selectRaw('COUNT(DISTINCT pegawai_kompetensi.nip) as jumlah_pegawai_berkompetensi')
            ->selectRaw('COUNT(*) as jumlah_event')
            ->selectRaw('COALESCE(SUM(pegawai_kompetensi.jumlah_jam), 0) as total_jam')
            ->selectRaw('COALESCE(AVG(pegawai_kompetensi.jumlah_jam), 0) as rata_jam')
            ->groupBy('jabatan')
            ->orderByDesc('jumlah_pegawai_berkompetensi');
    }

    /**
     * Rekap matriks diklat: jumlah event per (jabatan, jenis) untuk satu tahun.
     * Baris mentah untuk dipivot jadi kolom per jenis di halaman Matriks
     * Kebutuhan Diklat. Jenis NULL dianggap '(Tanpa jenis)'.
     */
    public function scopeRekapMatriksDiklat(Builder $query, int $tahun): Builder
    {
        $acuan = self::TANGGAL_ACUAN;

        return $query
            ->join('pegawai_profil', 'pegawai_profil.nip', '=', 'pegawai_kompetensi.nip')
            ->whereRaw("YEAR({$acuan}) = ?", [$tahun])
            ->selectRaw("COALESCE(NULLIF(TRIM(pegawai_profil.jabatan), ''), '(Tanpa jabatan)') as jabatan")
            ->selectRaw("COALESCE(NULLIF(TRIM(pegawai_kompetensi.jenis), ''), '(Tanpa jenis)') as jenis")
            ->selectRaw('COUNT(*) as jumlah_event')
            ->selectRaw('COALESCE(SUM(pegawai_kompetensi.jumlah_jam), 0) as total_jam')
            ->groupBy('jabatan', 'jenis');
    }

    /**
     * Jatah JP minimal/tahun per status_pegawai — dasar hukum: PNS minimal 20
     * JP/tahun (PP 11/2017 jo. PP 17/2020); PPPK & PPPK paruh waktu hak s.d.
     * 24 JP/tahun (Perka LAN No. 15/2020 — bagi PPPK ini batas atas/hak, bukan
     * lantai wajib seperti PNS, tapi dipakai sebagai acuan "capaian" yang sama
     * supaya kedua kelompok bisa dibandingkan satu tabel).
     */
    public const JATAH_JP = [
        'PEGAWAI NEGERI SIPIL' => 20,
        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA' => 24,
        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA PARUH WAKTU' => 24,
    ];

    /** Jatah dipakai bila status_pegawai tak dikenali/kosong. */
    public const JATAH_JP_DEFAULT = 20;

    /**
     * Evaluasi kesesuaian jumlah & jenis diklat per pegawai untuk satu tahun:
     * realisasi JP vs jatah (per status_pegawai), keragaman jenis diklat
     * yang didapat (COUNT DISTINCT jenis, "(Tanpa jenis)" dihitung sebagai
     * satu jenis tersendiri), dan judul-judul kompetensi yang sudah ditandai
     * manual "Sesuai Jabatan" (kolom sesuai_jabatan, default TRUE sampai
     * ditinjau — lihat migration-nya). Populasi = SEMUA pegawai aktif
     * (PegawaiProfil::nipAktif()), bukan cuma yang kebetulan sudah punya
     * kompetensi — pegawai tanpa kompetensi tampil dengan realisasi 0
     * (jelas "Kurang"), bukan hilang dari daftar.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function evaluasiKesesuaian(int $tahun): Collection
    {
        $nipList = PegawaiProfil::nipAktif();

        if ($nipList === []) {
            return collect();
        }

        $profil = PegawaiProfil::query()
            ->whereIn('nip', $nipList)
            ->get(['nip', 'nama', 'jabatan', 'status_pegawai'])
            ->keyBy('nip');

        $agregat = self::query()
            ->whereIn('nip', $nipList)
            ->whereRaw('YEAR('.self::TANGGAL_ACUAN.') = ?', [$tahun])
            ->selectRaw('nip')
            ->selectRaw('COUNT(*) as jumlah_event')
            ->selectRaw("COUNT(DISTINCT COALESCE(NULLIF(TRIM(jenis), ''), '(Tanpa jenis)')) as jumlah_jenis")
            ->selectRaw('COALESCE(SUM(jumlah_jam), 0) as total_jam')
            // Ditandai manual per baris (lihat migration sesuai_jabatan) —
            // default TRUE sampai ditinjau, jadi angka ini optimistis
            // (menganggap sesuai) untuk kompetensi yang belum ditinjau siapa pun.
            ->selectRaw('SUM(sesuai_jabatan) as jumlah_sesuai_jabatan')
            ->groupBy('nip')
            ->get()
            ->keyBy('nip');

        // Judul kompetensi yang ditandai sesuai_jabatan — dipakai halaman
        // Evaluasi Kesesuaian Diklat untuk menampilkan daftar judul langsung
        // (bukan cuma angka "N dari M").
        $judulSesuai = self::query()
            ->whereIn('nip', $nipList)
            ->whereRaw('YEAR('.self::TANGGAL_ACUAN.') = ?', [$tahun])
            ->where('sesuai_jabatan', true)
            ->orderBy('nama_kompetensi')
            ->get(['nip', 'nama_kompetensi'])
            ->groupBy('nip')
            ->map(fn (Collection $rows): array => $rows->pluck('nama_kompetensi')->filter()->values()->all());

        return collect($nipList)->map(function (string $nip) use ($profil, $agregat, $judulSesuai) {
            $p = $profil->get($nip);
            $a = $agregat->get($nip);

            $status = $p?->status_pegawai;
            $jatah = self::JATAH_JP[$status] ?? self::JATAH_JP_DEFAULT;
            $realisasi = (int) ($a->total_jam ?? 0);
            $jumlahEvent = (int) ($a->jumlah_event ?? 0);
            $jumlahSesuai = (int) ($a->jumlah_sesuai_jabatan ?? 0);

            return [
                'nip' => $nip,
                'nama' => $p?->nama ?? $nip,
                'jabatan' => $p?->jabatan ?: '(Tanpa jabatan)',
                'status_pegawai' => $status ?: '(Tanpa status)',
                'jatah_jp' => $jatah,
                'realisasi_jp' => $realisasi,
                'persen' => $jatah > 0 ? (int) round($realisasi / $jatah * 100) : 0,
                'jumlah_event' => $jumlahEvent,
                'jumlah_jenis' => (int) ($a->jumlah_jenis ?? 0),
                'jumlah_sesuai_jabatan' => $jumlahSesuai,
                // Field terpisah (bukan digabung jadi teks "N dari M") supaya
                // tiap kolom bisa di-sort/dihitung sendiri di Excel — dan
                // persennya sekalian, dihitung sekali di sini biar konsisten
                // di mana pun dipakai (halaman, export sheet1, dst).
                'persen_sesuai_jabatan' => $jumlahEvent > 0 ? (int) round($jumlahSesuai / $jumlahEvent * 100) : 0,
                'judul_sesuai_jabatan' => $judulSesuai->get($nip, []),
                'terpenuhi' => $realisasi >= $jatah,
            ];
        })->values();
    }
}
