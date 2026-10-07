<?php

namespace App\Models\Kepegawaian;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class PegawaiProfil extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'pegawai_profil';

    protected $primaryKey = 'nip';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tmt_bup_but' => 'date',
    ];

    // No form in this app mass-assigns pegawai_profil directly (the Pegawai
    // module is read-only; only PegawaiImportService writes here) — guarding
    // 'nip' bought no real protection and broke updateOrCreate()'s create
    // path, since fill() silently drops guarded keys even from trusted code.
    protected $guarded = [];

    /**
     * Retirement status bucket for the monitoring page, keyed off tmt_bup_but:
     * past → 'lampau', ≤1 year → 'warning', 1–2 years → 'siaga', beyond → 'aktif'.
     */
    public function statusPensiun(): string
    {
        if (! $this->tmt_bup_but) {
            return 'unknown';
        }

        if ($this->tmt_bup_but->isPast()) {
            return 'lampau';
        }

        if ($this->tmt_bup_but->lte(now()->addYear())) {
            return 'warning';
        }

        if ($this->tmt_bup_but->lte(now()->addYears(2))) {
            return 'siaga';
        }

        return 'aktif';
    }

    public function anak(): HasMany
    {
        return $this->hasMany(PegawaiAnak::class, 'nip', 'nip');
    }

    public function kompetensi(): HasMany
    {
        return $this->hasMany(PegawaiKompetensi::class, 'nip', 'nip');
    }

    public function masuk(): HasOne
    {
        return $this->hasOne(PegawaiMasuk::class, 'nip', 'nip');
    }

    public function cuti(): HasMany
    {
        return $this->hasMany(Cuti::class, 'nip', 'nip');
    }

    public function kuota(): HasMany
    {
        return $this->hasMany(CutiKuotaTahunan::class, 'nip', 'nip');
    }

    public function penghargaan(): HasMany
    {
        return $this->hasMany(PegawaiPenghargaan::class, 'nip', 'nip');
    }

    public function arsip(): HasMany
    {
        return $this->hasMany(PegawaiArsip::class, 'nip', 'nip');
    }

    public function drhSatyaLancana(): HasMany
    {
        return $this->hasMany(DrhSatyaLancana::class, 'nip', 'nip');
    }

    public function riwayatCpns(): HasOne
    {
        return $this->hasOne(PegawaiRiwayatCpns::class, 'nip', 'nip');
    }

    public function riwayatJabatan(): HasMany
    {
        return $this->hasMany(PegawaiRiwayatJabatan::class, 'nip', 'nip')->orderBy('no_urut');
    }

    public function riwayatPangkat(): HasMany
    {
        return $this->hasMany(PegawaiRiwayatPangkat::class, 'nip', 'nip')->orderBy('no_urut');
    }

    /**
     * Baris DUK (Daftar Urut Kepangkatan) pegawai ini dari batch impor
     * TERBARU — dipakai untuk menampilkan "DUK Terakhir" di detail pegawai.
     * Null kalau pegawai tak ada di batch DUK terbaru.
     */
    public function dukTerakhir(): ?Duk
    {
        $batchId = DukImpor::query()->orderByDesc('diimpor_pada')->orderByDesc('id')->value('id');
        if (! $batchId) {
            return null;
        }

        return Duk::query()
            ->where('duk_impor_id', $batchId)
            ->where('nip', $this->nip)
            ->orderBy('urutan_duk')
            ->first();
    }

    /**
     * Set pencocokan NIP pengguna aktif (users.is_aktif = 1), di-cache per
     * request. Kolom is_aktif hanya ada di data_local.users, bukan di sini,
     * jadi keaktifan pegawai ditentukan lewat pencocokan NIP: cocok persis,
     * kalau gagal cocok 15 digit pertama (NIP di users kadang tanpa 3 digit
     * sufiks).
     *
     * @return array{exact: array<string, true>, prefix15: array<string, true>}
     */
    private static function aktifUsersNip(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $exact = [];
        $prefix15 = [];

        foreach (\App\Models\User::query()
            ->where('is_aktif', 1)
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->pluck('nip') as $nip) {
            $digit = preg_replace('/\D+/', '', (string) $nip);
            if ($digit === '') {
                continue;
            }
            $exact[$digit] = true;
            if (strlen($digit) >= 15) {
                $prefix15[substr($digit, 0, 15)] = true;
            }
        }

        return $cache = ['exact' => $exact, 'prefix15' => $prefix15];
    }

    /** Apakah NIP ini milik pengguna aktif (is_aktif = 1)? */
    public static function isAktif(string $nip): bool
    {
        $digit = preg_replace('/\D+/', '', (string) $nip);
        if ($digit === '') {
            return false;
        }

        $sets = self::aktifUsersNip();

        return isset($sets['exact'][$digit])
            || (strlen($digit) >= 15 && isset($sets['prefix15'][substr($digit, 0, 15)]));
    }

    /** @return string[] NIP pegawai_profil yang aktif (is_aktif = 1). */
    public static function nipAktif(): array
    {
        return self::query()
            ->pluck('nip')
            ->filter(fn ($nip) => self::isAktif((string) $nip))
            ->values()
            ->all();
    }

    /**
     * Status pegawai yang dianggap PNS untuk keperluan penghargaan masa
     * kerja (Satyalancana Karya Satya — PP 25/1994 — hanya berlaku untuk
     * PNS, tidak untuk PPPK).
     */
    public const STATUS_PNS = 'PEGAWAI NEGERI SIPIL';

    /** Ambang batas tahun masa kerja untuk penghargaan, urut menurun. */
    public const AMBANG_PENGHARGAAN = [30, 20, 10];

    /**
     * Tahun TMT pengangkatan pertama, dibaca dari digit ke-9..12 NIP 18
     * digit (format NIP ASN: 8 digit tanggal lahir + 6 digit YYYYMM TMT
     * pengangkatan + 1 digit gender + 3 digit nomor urut). Null bila NIP
     * bukan 18 digit atau segmen itu bukan angka.
     */
    public static function tahunTmtDariNip(string $nip): ?int
    {
        $digit = preg_replace('/\D+/', '', $nip);

        if ($digit === null || strlen($digit) !== 18) {
            return null;
        }

        $tahun = (int) substr($digit, 8, 4);

        return ($tahun >= 1970 && $tahun <= (int) now()->year) ? $tahun : null;
    }

    /** Masa kerja dalam tahun penuh (tahun berjalan − tahun TMT), null bila NIP tak valid. */
    public static function masaKerjaTahun(string $nip): ?int
    {
        $tmt = self::tahunTmtDariNip($nip);

        return $tmt === null ? null : (int) now()->year - $tmt;
    }

    /**
     * Bucket penghargaan masa kerja (30/20/10), tidak bertumpuk — pegawai
     * dengan masa kerja 34 tahun hanya masuk bucket 30, bukan 30 dan 20.
     * Null bila masa kerja < 10 tahun atau NIP tak valid.
     */
    public static function bucketPenghargaan(string $nip): ?int
    {
        $masa = self::masaKerjaTahun($nip);

        if ($masa === null) {
            return null;
        }

        foreach (self::AMBANG_PENGHARGAAN as $ambang) {
            if ($masa >= $ambang) {
                return $ambang;
            }
        }

        return null;
    }

    /**
     * Daftar pegawai kandidat penghargaan masa kerja: PNS aktif (is_aktif =
     * 1) dengan masa kerja ≥ 10 tahun (bucket 10/20/30, tidak bertumpuk).
     * Satu baris per pegawai, diurutkan masa kerja menurun.
     *
     * @return Collection<int, array{nip: string, nama: string, jabatan: ?string, golongan: ?string, tmt_tahun: int, masa_kerja: int, bucket: int}>
     */
    public static function kandidatPenghargaan(): Collection
    {
        return self::query()
            ->where('status_pegawai', self::STATUS_PNS)
            ->get(['nip', 'nama', 'jabatan', 'golongan'])
            ->filter(fn (self $p) => self::isAktif($p->nip))
            ->map(function (self $p) {
                $bucket = self::bucketPenghargaan($p->nip);

                if ($bucket === null) {
                    return null;
                }

                return [
                    'nip' => $p->nip,
                    'nama' => $p->nama,
                    'jabatan' => $p->jabatan,
                    'golongan' => $p->golongan,
                    'tmt_tahun' => self::tahunTmtDariNip($p->nip),
                    'masa_kerja' => self::masaKerjaTahun($p->nip),
                    'bucket' => $bucket,
                ];
            })
            ->filter()
            ->sortByDesc('masa_kerja')
            ->values();
    }

    /**
     * Jendela "pengusulan terakhir" — berapa tahun ke belakang DRH Satya
     * Lancana dianggap relevan saat menampilkan riwayat pengusulan terakhir.
     * (Dipakai murni untuk info; keputusan boleh-tidak-nya tetap dari
     * status DRH terbaru + tingkat yang perlu diusulkan.)
     */
    public const JENDELA_PENGUSULAN_TAHUN = 3;

    /**
     * Rekap penghargaan masa kerja berbentuk checklist per tingkat SLKS
     * (10/20/30 tahun), plus tingkat mana yang WAJIB diusulkan berikutnya
     * sesuai syarat: "PNS yang belum pernah menerima SLKS hanya dapat
     * diusulkan menerima SLKS secara urut dari tingkat terendah" — jadi
     * pegawai bermasa kerja 23 tahun tanpa SLKS sama sekali harus diusulkan
     * 10 tahun dulu, bukan langsung 20 tahun.
     *
     * Kalau pegawai SUDAH tercatat punya tingkat yang lebih tinggi (mis. 20
     * tahun) tapi tingkat di bawahnya (10 tahun) tak tercatat, tingkat yang
     * lebih rendah itu dianggap SUDAH terpenuhi juga — asumsinya cuma tak
     * diisi di SIMPATIK/SISDM, bukan berarti urutan pengusulan sungguhan
     * dilanggar. Jadi perlu_diusulkan lompat ke tingkat berikutnya DI ATAS
     * tingkat tertinggi yang tercatat (mis. punya 20 saja → next 30, bukan
     * balik ke 10 yang cuma kosong administratif).
     *
     * "Dimiliki" dibaca dari pegawai_penghargaan (baik hasil impor SISDM
     * maupun ditambah manual — lihat PegawaiPenghargaan::SUMBER_MANUAL,
     * fitur ini ada persis karena SISDM/SIMPATIK kadang tak diisi staf)
     * lewat PegawaiPenghargaan::tier() — cek jenis_penghargaan DULU, fallback
     * ke nama_penghargaan (staf SISDM kadang asal isi salah satu field, mis.
     * jenis="TANDA PENGHARGAAN LAINNYA" generic tapi nama_penghargaan jelas
     * menyebut "...20 TH" — baris begini tak boleh hilang dari rekap).
     * Kolom checklist has_10/has_20/has_30 tetap menampilkan data ASLI apa
     * adanya (bukan hasil asumsi) — cascade di atas cuma dipakai untuk
     * menentukan perlu_diusulkan, supaya data mentah tak disamarkan.
     *
     * @return Collection<int, array{nip: string, nama: string, jabatan: ?string, golongan: ?string, masa_kerja: int, masa_kerja_duk: ?string, has_10: bool, has_20: bool, has_30: bool, perlu_diusulkan: ?int, jumlah_penghargaan: int, drh_terakhir_status: ?string, drh_terakhir_tahun: ?int, drh_dalam_3_tahun: bool, bisa_diusulkan: bool, alasan_bisa_diusulkan: string}>
     */
    public static function rekapPenghargaan(): Collection
    {
        $nipList = self::query()
            ->where('status_pegawai', self::STATUS_PNS)
            ->pluck('nip')
            ->filter(fn ($nip) => self::isAktif((string) $nip))
            ->values();

        if ($nipList->isEmpty()) {
            return collect();
        }

        $profil = self::query()
            ->whereIn('nip', $nipList)
            ->get(['nip', 'nama', 'jabatan', 'golongan'])
            ->keyBy('nip');

        // Seluruh baris pegawai_penghargaan per pegawai — dipakai dua hal:
        // tingkat SLKS yang tercatat (tierByNip, di bawah) DAN jumlah total
        // penghargaan apa pun (jumlahByNip) — yang kedua sengaja mencakup
        // SEMUA jenis penghargaan, bukan cuma SLKS (mis. Satyalencana
        // Wirakarya, penghargaan dinas, dll — tetap dihitung).
        $semuaByNip = PegawaiPenghargaan::query()
            ->whereIn('nip', $nipList)
            ->get(['nip', 'jenis_penghargaan', 'nama_penghargaan'])
            ->groupBy('nip');

        $jumlahByNip = $semuaByNip->map(fn (Collection $rows): int => $rows->count());

        // DRH Satya Lancana terbaru per pegawai (status draft/diusulkan/
        // ditolak/sukses) — untuk kolom "Pengusulan Terakhir" & keputusan
        // "bisa diajukan kembali". Ambil baris paling baru (created_at desc).
        $drhByNip = DrhSatyaLancana::query()
            ->whereIn('nip', $nipList)
            ->orderByDesc('created_at')
            ->get(['nip', 'status', 'created_at'])
            ->groupBy('nip')
            ->map(fn (Collection $rows) => $rows->first());

        // Masa kerja dari DUK terakhir (perhitungan resmi kepegawaian, bukan
        // turunan NIP) — satu baris per NIP dari batch DUK TERBARU. Dipakai
        // kolom "Masa Kerja DUK" sebagai pembanding kolom "Masa Kerja" (NIP).
        $dukBatchId = DukImpor::query()->orderByDesc('diimpor_pada')->orderByDesc('id')->value('id');
        $dukByNip = Duk::query()
            ->where('duk_impor_id', $dukBatchId ?? 0)
            ->whereIn('nip', $nipList)
            ->orderBy('urutan_duk')
            ->get(['nip', 'masa_kerja_tahun', 'masa_kerja_bulan'])
            ->groupBy('nip')
            ->map(fn (Collection $rows) => $rows->first());

        // Tingkat yang sudah pernah dimiliki per pegawai — union impor +
        // manual, sumbernya tak dibedakan di sini (keduanya sama sah).
        // tier() baca jenis_penghargaan DAN nama_penghargaan (fallback) —
        // staf SISDM kadang asal isi salah satu field, jadi keduanya dicek
        // supaya baris tak hilang dari rekap gara-gara satu field kosong/salah.
        $tierByNip = $semuaByNip
            ->map(fn (Collection $rows): array => $rows
                ->map(fn (PegawaiPenghargaan $r) => $r->tier())
                ->filter()
                ->unique()
                ->values()
                ->all());

        return $nipList->map(function (string $nip) use ($profil, $tierByNip, $jumlahByNip, $drhByNip, $dukByNip) {
            $masa = self::masaKerjaTahun($nip);

            if ($masa === null || $masa < 10) {
                return null;
            }

            $p = $profil->get($nip);
            $dimiliki = $tierByNip->get($nip, []);

            $has10 = in_array(10, $dimiliki, true);
            $has20 = in_array(20, $dimiliki, true);
            $has30 = in_array(30, $dimiliki, true);

            // Tingkat tertinggi yang tercatat — tingkat ini DAN semua di
            // bawahnya dianggap terpenuhi (cascade), sesuai keputusan: tak
            // diisi di SIMPATIK bukan berarti belum diusulkan.
            $tertinggiDimiliki = match (true) {
                $has30 => 30,
                $has20 => 20,
                $has10 => 10,
                default => null,
            };

            $eligible = [10 => $masa >= 10, 20 => $masa >= 20, 30 => $masa >= 30];

            // Tingkat terendah yang masa kerjanya sudah cukup TAPI masih di
            // atas tingkat tertinggi yang tercatat — tak boleh lompat lebih
            // dari satu langkah dari yang sudah terpenuhi (asli/cascade).
            $perluDiusulkan = null;
            foreach ([10, 20, 30] as $t) {
                if ($eligible[$t] && $t > ($tertinggiDimiliki ?? 0)) {
                    $perluDiusulkan = $t;
                    break;
                }
            }

            // DRH Satya Lancana terbaru untuk pegawai ini (bila ada).
            $drh = $drhByNip->get($nip);
            $drhTahun = $drh?->created_at?->year;
            $drhDalamJendela = $drhTahun !== null
                && $drhTahun >= ((int) now()->year - self::JENDELA_PENGUSULAN_TAHUN);

            // Masa kerja DUK terakhir (dari dokumen) — teks "28 tahun 12 bulan".
            $duk = $dukByNip->get($nip);
            $dukTeks = $duk
                ? (($duk->masa_kerja_tahun ?? 0).' tahun '.($duk->masa_kerja_bulan ?? 0).' bulan')
                : null;

            // Boleh diajukan lagi?
            //  - Tak ada tingkat yang perlu diusulkan → selesai.
            //  - DRH terbaru berstatus "sukses" → tingkat itu sudah diproses
            //    (blokir cuma untuk tingkat yang sama; begitu SK-nya tercatat
            //    di pegawai_penghargaan, perlu_diusulkan otomatis naik ke
            //    tingkat berikutnya dan baris ini jadi bisa diajukan lagi).
            //  - Non-sukses (draft/diusulkan/ditolak) → BEBAS ajukan ulang
            //    (mis. 2024 diusulkan belum sukses, 2026 boleh ajukan lagi).
            $bisaDiusulkan = false;
            $alasanBisaDiusulkan = 'Lengkap';

            if ($perluDiusulkan !== null) {
                if ($drh && $drh->status === DrhSatyaLancana::STATUS_SUKSES) {
                    $alasanBisaDiusulkan = 'Sudah sukses ('.$drhTahun.')';
                } else {
                    $bisaDiusulkan = true;
                    $alasanBisaDiusulkan = 'Bisa diajukan';
                }
            }

            return [
                'nip' => $nip,
                'nama' => $p?->nama ?? $nip,
                'jabatan' => $p?->jabatan,
                'golongan' => $p?->golongan,
                'masa_kerja' => $masa,
                'masa_kerja_duk' => $dukTeks,
                'has_10' => $has10,
                'has_20' => $has20,
                'has_30' => $has30,
                'perlu_diusulkan' => $perluDiusulkan,
                'jumlah_penghargaan' => $jumlahByNip->get($nip, 0),
                'drh_terakhir_status' => $drh?->status,
                'drh_terakhir_tahun' => $drhTahun,
                'drh_dalam_3_tahun' => $drhDalamJendela,
                'bisa_diusulkan' => $bisaDiusulkan,
                'alasan_bisa_diusulkan' => $alasanBisaDiusulkan,
            ];
        })
            ->filter()
            ->sortByDesc('masa_kerja')
            ->values();
    }
}
