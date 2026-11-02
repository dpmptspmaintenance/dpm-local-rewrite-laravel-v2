<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\Cuti;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\Kepegawaian\SuratCutiRiwayat;

/**
 * Generator nomor surat cuti — READ-ONLY terhadap tabel `cuti`, tidak
 * mengubah jatah/sisa/status. Tugasnya:
 * 1. Parse tempelan teks rekap pengajuan (nomor urut nama + rincian "- ...").
 * 2. Memberi nomor surat sekuensial format "{seq}/{kode}/{ROMAN}/{tahun}"
 *    (bulan+tahun diambil dari tanggal pengajuan tiap rincian).
 *
 * `susun()` adalah SATU-SATUNYA tempat aturan hasil generator ditulis, dipakai
 * halaman Generator Surat Cuti maupun API (extension) supaya hasilnya identik.
 *
 * Contoh masukan:
 *   1. SANTI TRI WAHYUNI
 *      - Cuti Sakit 2 hari (29 - 30 September 2026) pengajuan 05-10-2026 15:49:19
 *   2. RISTANTI EKA WIDAYATI, A.Md. Cuti Karena Alasan Penting 1 hari
 *      (05 Oktober 2026) pengajuan 04-10-2026 23:04:21
 */
class SuratCutiGeneratorService
{
    public const DEFAULT_KODE = '800';

    /** Maksimal rincian per satu kiriman (API). */
    public const MAKS_RINCIAN = 200;

    /** Jenis cuti yang dikenali sebagai awal rincian (untuk memisahkan nama). */
    public const JENIS = [
        'Cuti Tahunan',
        'Cuti Sakit',
        'Cuti Melahirkan',
        'Cuti Karena Alasan Penting',
        'Cuti Besar',
    ];

    private const ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /** Nama bulan Indonesia (dan Inggris sebagai cadangan) → angka. */
    private const BULAN = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        'january' => 1, 'february' => 2, 'march' => 3, 'may' => 5,
        'june' => 6, 'july' => 7, 'august' => 8,
        'october' => 10, 'december' => 12,
    ];

    /** Cache tanggal libur (Y-m-d => true) dari tabel hari_libur. */
    private ?array $liburCache = null;

    /**
     * Parse tempelan menjadi daftar orang + rincian cutinya. Baris yang tak
     * dikenali dikumpulkan terpisah (tidak diberi nomor).
     *
     * @return array{orang: list<array{nama: string, rinci: list<array{jenis: string, hari: int, rentang: string, pengajuan: string, bulan: int, tahun: int, tahun_cuti: ?int, mentah: string}>}>, tak_dikenali: list<string>}
     */
    public function parse(string $text): array
    {
        $orang = [];
        $takDikenali = [];
        $kini = null;

        foreach (preg_split('/\R/u', $text) ?: [] as $baris) {
            $baris = trim($baris);

            if ($baris === '') {
                continue;
            }

            // Baris nama: "1. NAMA ..." (boleh ada rincian menempel di belakang).
            if (preg_match('/^\d+\s*[.)]\s*(.+)$/u', $baris, $m)) {
                $sisa = trim($m[1]);
                $item = $this->pisahNamaRincian($sisa);

                $orang[] = ['nama' => $item[0], 'rinci' => []];
                $kini = count($orang) - 1;

                if ($item[1] !== null) {
                    $this->tambahRincian($orang[$kini]['rinci'], $takDikenali, $item[1]);
                }

                continue;
            }

            // Baris rincian: "- ..." / "* ..." / "• ...".
            if (preg_match('/^[-*•]\s*(.+)$/u', $baris, $m)) {
                if ($kini === null) {
                    $orang[] = ['nama' => '(Tanpa nama)', 'rinci' => []];
                    $kini = count($orang) - 1;
                }

                $this->tambahRincian($orang[$kini]['rinci'], $takDikenali, trim($m[1]));

                continue;
            }

            // Baris lanjutan (bungkusan baris rincian yang panjang): tempel ke
            // rincian terakhir bila ada, kalau tidak anggap nama baru.
            if ($kini !== null && $orang[$kini]['rinci'] !== []) {
                $akhir = count($orang[$kini]['rinci']) - 1;
                $gabung = $orang[$kini]['rinci'][$akhir]['mentah'].' '.$baris;
                $coba = $this->parseRincian($gabung);

                if ($coba !== null) {
                    $orang[$kini]['rinci'][$akhir] = $coba;
                } else {
                    $takDikenali[] = $baris;
                }

                continue;
            }

            $orang[] = ['nama' => $baris, 'rinci' => []];
            $kini = count($orang) - 1;
        }

        return ['orang' => array_values($orang), 'tak_dikenali' => $takDikenali];
    }

    /**
     * Pisahkan "NAMA [rincian menempel]" — rincian selalu diawali kata
     * "Cuti <Jenis>", nama tak pernah mengandung kata itu.
     *
     * @return array{0: string, 1: ?string} [nama, teks rincian atau null]
     */
    private function pisahNamaRincian(string $teks): array
    {
        $alternatif = implode('|', array_map(preg_quote(...), self::JENIS));

        if (preg_match('/^(.*?)\b('.$alternatif.')\b(.*)$/us', $teks, $m)) {
            $nama = trim($m[1]);

            if ($nama !== '') {
                return [$nama, trim($m[2].$m[3])];
            }
        }

        return [$teks, null];
    }

    /**
     * @param  list<array{jenis: string, hari: int, rentang: string, pengajuan: string, bulan: int, tahun: int, mentah: string}>  $tampung
     * @param  list<string>  $takDikenali
     */
    private function tambahRincian(array &$tampung, array &$takDikenali, string $teks): void
    {
        $rinci = $this->parseRincian($teks);

        if ($rinci === null) {
            $takDikenali[] = $teks;

            return;
        }

        $tampung[] = $rinci;
    }

    /**
     * "Cuti Sakit 2 hari (29 - 30 September 2026) pengajuan 05-10-2026 15:49:19"
     * Null bila pola tak cocok.
     *
     * Angka "N hari" yang tertulis DIABAIKAN — durasi dihitung ulang sebagai
     * hari kerja dari rentang tanggal (lewati Sabtu/Minggu + hari_libur).
     * Hanya dua pola rentang yang didukung: "13 - 17 November 2026" dan
     * "05 Oktober 2026" (satu hari). Di luar itu → null (baris ditolak).
     *
     * tahun_cuti = tahun cutinya (dari rentang). Null bila rentang memuat
     * 0/2+ tahun berbeda (mis. lintas tahun) — meniru rekap yang mengabaikan
     * baris lintas tahun.
     *
     * @return array{jenis: string, hari: int, rentang: string, pengajuan: string, bulan: int, tahun: int, tahun_cuti: ?int, mentah: string}|null
     */
    public function parseRincian(string $teks): ?array
    {
        if (! preg_match('/^(Cuti\s+.+?)\s+(\d+)\s*hari\s*\(([^)]+)\)\s*pengajuan\s+(.+?)\s*$/us', trim($teks), $m)) {
            return null;
        }

        [$bulan, $tahun] = $this->bulanTahunPengajuan(trim($m[4]));

        preg_match_all('/(\d{4})/', $m[3], $tm);
        $tahunUnik = array_values(array_unique($tm[1]));

        [$mulai, $selesai] = $this->rentangTanggal(trim($m[3]));

        if ($mulai === null) {
            return null;
        }

        $selesai ??= $mulai;

        $hari = $this->hariKerja($mulai, $selesai);

        if ($hari <= 0) {
            return null;
        }

        return [
            'jenis' => trim($m[1]),
            'hari' => $hari,
            'rentang' => trim($m[3]),
            'pengajuan' => trim($m[4]),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'tahun_cuti' => $mulai->year === $selesai->year ? $mulai->year : null,
            'mentah' => trim($teks),
        ];
    }

    /**
     * Parse rentang tanggal cuti. Dua pola:
     * - "13 - 17 November 2026" (atau "13-17 November 2026")
     * - "05 Oktober 2026" (satu hari)
     *
     * @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon} [mulai, selesai/null utk 1 hari]
     */
    private function rentangTanggal(string $rentang): array
    {
        $rentang = trim(preg_replace('/\s+/u', ' ', $rentang) ?? $rentang);

        if (preg_match('/^(\d{1,2})\s*[–—-]\s*(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/u', $rentang, $m)) {
            $bulan = self::BULAN[mb_strtolower($m[3])] ?? null;
            $tahun = (int) $m[4];

            if ($bulan === null || $tahun < 2000 || $tahun > 2100) {
                return [null, null];
            }

            try {
                $mulai = \Carbon\Carbon::create($tahun, $bulan, (int) $m[1])->startOfDay();
                $selesai = \Carbon\Carbon::create($tahun, $bulan, (int) $m[2])->startOfDay();
            } catch (\Throwable) {
                return [null, null];
            }

            if ($selesai->lt($mulai)) {
                return [null, null];
            }

            return [$mulai, $selesai];
        }

        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/u', $rentang, $m)) {
            $bulan = self::BULAN[mb_strtolower($m[2])] ?? null;
            $tahun = (int) $m[3];

            if ($bulan === null || $tahun < 2000 || $tahun > 2100) {
                return [null, null];
            }

            try {
                $satu = \Carbon\Carbon::create($tahun, $bulan, (int) $m[1])->startOfDay();
            } catch (\Throwable) {
                return [null, null];
            }

            return [$satu, null];
        }

        return [null, null];
    }

    /**
     * Hitung hari kerja: tiap hari kalender dalam [mulai, selesai] kecuali
     * Sabtu (6), Minggu (0), dan tanggal di tabel hari_libur.
     */
    public function hariKerja(\Carbon\Carbon $mulai, \Carbon\Carbon $selesai): int
    {
        $libur = $this->tanggalLibur();
        $hitung = 0;
        $hari = $mulai->copy()->startOfDay();
        $akhir = $selesai->copy()->startOfDay();

        while (! $hari->gt($akhir)) {
            $dow = (int) $hari->format('w');

            if ($dow !== 0 && $dow !== 6 && ! isset($libur[$hari->format('Y-m-d')])) {
                $hitung++;
            }

            $hari->addDay();
        }

        return $hitung;
    }

    /** @return array<string, true> tanggal libur Y-m-d (cache per request). */
    private function tanggalLibur(): array
    {
        if ($this->liburCache !== null) {
            return $this->liburCache;
        }

        try {
            $daftar = \App\Models\Kepegawaian\HariLibur::query()->pluck('tanggal');
        } catch (\Throwable) {
            return $this->liburCache = [];
        }

        $peta = [];

        foreach ($daftar as $t) {
            try {
                $peta[\Carbon\Carbon::parse($t)->format('Y-m-d')] = true;
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->liburCache = $peta;
    }

    /**
     * Ambil bulan+tahun dari "05-10-2026 15:49:19". Gagal parse → bulan dan
     * tahun berjalan (nomor tetap bisa dibuat).
     *
     * @return array{0: int, 1: int}
     */
    public function bulanTahunPengajuan(string $pengajuan): array
    {
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})/', $pengajuan, $m)) {
            $bulan = (int) $m[2];
            $tahun = (int) $m[3];

            if ($bulan >= 1 && $bulan <= 12 && $tahun >= 2000 && $tahun <= 2100) {
                return [$bulan, $tahun];
            }
        }

        return [(int) now()->month, (int) now()->year];
    }

    public function formatNomor(int $seq, string $kode, int $bulan, int $tahun): string
    {
        $kode = trim($kode, " \t\n\r\0\x0B/");

        return $seq.'/'.$kode.'/'.(self::ROMAWI[$bulan] ?? $bulan).'/'.$tahun;
    }

    /**
     * SUSUN hasil generator: deteksi duplikat, hitung potongan sisa (aturan
     * PNS/PPPK sama seperti rekap), beri nomor sekuensial. Dipakai halaman
     * Generator Surat Cuti DAN API — jangan duplikasi aturan di tempat lain.
     *
     * Nomor diberikan BERDASARKAN TANGGAL PENGAJUAN TERDAHULU: nomor
     * terkecil jatuh ke pengajuan paling lama. Baris tetap ditampilkan
     * sesuai urutan input (no = urutan tempel), hanya alokasi nomornya
     * yang mengikuti kronologi pengajuan.
     *
     * @param  list<array{nama: string, rinci: list<array<string, mixed>>}>  $orang
     * @param  list<string>  $takDikenali
     * @param  list<int>  $tahun  tahun kolom sisa (urutan tampil, mis. [2026,2025,2024])
     * @param  array<string, true>  $kunciRiwayat  kunci duplikat yang sudah ada di riwayat (cek silang)
     * @return array{
     *     hasil: list<array<string, mixed>>,
     *     duplikat: list<array{nama: string, pengajuan: string, jenis: string, mentah: string}>,
     *     tak_dikenali: list<string>,
     *     nomor_berikutnya: int
     * }
     */
    public function susun(array $orang, array $takDikenali, string $kode, int $nomorAwal, array $tahun, array $kunciRiwayat = []): array
    {
        $tahun = array_values(array_map('intval', $tahun));

        // Duplikat (nama + tgl pengajuan sama) tetap dibawa dengan flag —
        // tidak diberi nomor, tapi masuk riwayat. Kunci dinormalisasi
        // (spasi berlebih + kapital diabaikan).
        $lihat = [];
        $daftarDuplikat = [];

        foreach ($orang as $oi => $o) {
            foreach ($o['rinci'] as $ri => $r) {
                $kunci = $this->kunciDuplikat($o['nama'], $r['pengajuan']);
                $dupe = isset($lihat[$kunci]) || isset($kunciRiwayat[$kunci]);

                if ($dupe) {
                    $daftarDuplikat[] = [
                        'nama' => $o['nama'],
                        'pengajuan' => $r['pengajuan'],
                        'jenis' => $r['jenis'],
                        'mentah' => $r['mentah'],
                    ];
                } else {
                    $lihat[$kunci] = true;
                }

                $orang[$oi]['rinci'][$ri]['duplikat'] = $dupe;
            }
        }

        // Sisa per pegawai dihitung SEKALI untuk ketiga tahun (read-only,
        // dari data cuti yang sudah ada — tidak diubah), SUDAH dikurangi
        // pengajuan batch ini (aturan potong PNS/PPPK sama seperti rekap).
        $tambahan = [];

        foreach ($orang as $o) {
            $profil = $this->profilUntukNama($o['nama']);

            if ($profil === null) {
                continue;
            }

            $nip = (string) $profil->nip;

            foreach ($o['rinci'] as $r) {
                if (($r['tahun_cuti'] ?? null) === null) {
                    continue;
                }

                // Duplikat tidak memotong lagi (sudah dihitung kemunculan pertama).
                if (! empty($r['duplikat'])) {
                    continue;
                }

                if (! Cuti::memotongJatah((string) $r['jenis'], $profil->status_pegawai)) {
                    continue;
                }

                $tambahan[$nip][(int) $r['tahun_cuti']] = ($tambahan[$nip][(int) $r['tahun_cuti']] ?? 0) + (int) $r['hari'];
            }
        }

        $rekap = Cuti::rekapTahunan($tahun, $tambahan)->keyBy('nip');

        // Nomor berdasarkan tanggal pengajuan terdahulu: pipihkan semua
        // rincian (no = urutan tempel), urutkan kronologis (stabil —
        // pengajuan sama mempertahankan urutan tempel), lalu tampilkan +
        // beri nomor berurutan dari nomor awal. Jadi baris tampil kronologis
        // dengan no 1 = pengajuan terlama = nomor terkecil.
        $pipih = [];
        $seq = max(1, $nomorAwal);

        foreach ($orang as $o) {
            $profil = $this->profilUntukNama($o['nama']);
            $barisRekap = $profil !== null ? $rekap->get((string) $profil->nip) : null;

            $sisa = [];

            foreach ($tahun as $y) {
                $sisa[$y] = $barisRekap !== null && array_key_exists('sisa_hari_'.$y, $barisRekap)
                    ? (int) $barisRekap['sisa_hari_'.$y]
                    : '—';
            }

            foreach ($o['rinci'] as $r) {
                $pipih[] = [
                    'nama' => $o['nama'],
                    'nip' => $profil?->nip,
                    'pengajuan' => $r['mentah'],
                    'pengajuan_at' => $r['pengajuan'],
                    'pengajuan_waktu' => $this->waktuPengajuan((string) ($r['pengajuan'] ?? '')),
                    'urutan' => count($pipih),
                    'bulan' => (int) $r['bulan'],
                    'tahun' => (int) $r['tahun'],
                    'hari' => (int) $r['hari'],
                    'rentang' => $r['rentang'],
                    'jenis' => $r['jenis'],
                    'duplikat' => ! empty($r['duplikat']),
                    'sisa' => $sisa,
                ];
            }
        }

        usort($pipih, fn (array $a, array $b): int =>
            $a['pengajuan_waktu'] <=> $b['pengajuan_waktu'] ?: $a['urutan'] <=> $b['urutan']);

        $hasil = [];
        $no = 0;

        foreach ($pipih as $p) {
            $no++;
            $nomor = null;

            if (! $p['duplikat']) {
                $nomor = $this->formatNomor($seq, $kode, $p['bulan'], $p['tahun']);
                $seq++;
            }

            $hasil[] = [
                'no' => $no,
                'nama' => $p['nama'],
                'nip' => $p['nip'],
                'pengajuan' => $p['pengajuan'],
                'pengajuan_at' => $p['pengajuan_at'],
                'nomor' => $nomor,
                'bulan' => $p['bulan'],
                'tahun' => $p['tahun'],
                'hari' => $p['hari'],
                'rentang' => $p['rentang'],
                'jenis' => $p['jenis'],
                'duplikat' => $p['duplikat'],
                'sisa' => $p['sisa'],
            ];
        }

        return [
            'hasil' => $hasil,
            'duplikat' => $daftarDuplikat,
            'tak_dikenali' => array_values($takDikenali),
            'nomor_berikutnya' => $seq,
        ];
    }

    /** Kunci deteksi duplikat: nama (kapital/spasi dinormalisasi) + pengajuan. */
    public function kunciDuplikat(string $nama, string $pengajuan): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($nama)) ?? trim($nama))
            .'|'.preg_replace('/\s+/u', ' ', trim($pengajuan));
    }

    /**
     * Timestamp pengajuan untuk pengurutan kronologis nomor. Format normal
     * "05-10-2026 15:49:19" (atau tanpa jam). Gagal parse → jauh di belakang
     * supaya baris rusak dapat nomor terakhir, bukan pertama.
     */
    private function waktuPengajuan(string $pengajuan): int
    {
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?/', trim($pengajuan), $m)) {
            try {
                return \Carbon\Carbon::create(
                    (int) $m[3], (int) $m[2], (int) $m[1],
                    (int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0),
                )->timestamp;
            } catch (\Throwable) {
                // jatuh ke fallback di bawah
            }
        }

        try {
            return \Carbon\Carbon::parse($pengajuan)->timestamp;
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }

    /** Profil pegawai berdasarkan nama (exact dulu, lalu case-insensitive). */
    public function profilUntukNama(string $nama): ?PegawaiProfil
    {
        $nama = trim($nama);

        if ($nama === '') {
            return null;
        }

        $profil = PegawaiProfil::query()
            ->where('nama', $nama)
            ->first(['nip', 'nama', 'status_pegawai']);

        return $profil ?? PegawaiProfil::query()
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->first(['nip', 'nama', 'status_pegawai']);
    }

    /**
     * Nomor awal default = sekuens terbesar pada seri (kode + bulan romawi
     * TERBARU) + 1. Seri berjalan lintas bulan dalam setahun (mis. IX
     * lanjut ke X), jadi acuannya bulan terbaru yang sudah ada — bukan
     * maks global (ada outlier entri manual seperti 9653/VIII atau nomor
     * cacat tanpa kode yang harus diabaikan).
     *
     * Sumber: cuti.no_surat DAN riwayat_generator_cuti (nomor non-duplikat),
     * supaya nomor yang dibuat lewat generator tapi belum masuk impor cuti
     * tetap dihitung — panggilan API beruntun tidak dapat nomor kembar.
     */
    public function nextSequence(string $kode = self::DEFAULT_KODE): int
    {
        $nomor = $this->nomorDariTabel('cuti', 'no_surat', $kode);
        $nomorRiwayat = $this->nomorDariTabel('riwayat_generator_cuti', 'nomor_surat', $kode);

        return max($nomor, $nomorRiwayat) + 1;
    }

    /**
     * Sekuens terbesar pada bulan terbaru untuk kode tertentu di satu tabel.
     */
    private function nomorDariTabel(string $tabel, string $kolom, string $kode): int
    {
        $kode = trim($kode, " \t\n\r\0\x0B/");
        $bulanKeAngka = array_flip(self::ROMAWI);
        $terbaru = null; // [tahun, bulan]
        $maks = 0;

        try {
            if (! \Illuminate\Support\Facades\Schema::connection('kepegawaian')->hasTable($tabel)) {
                return 0;
            }

            $query = \Illuminate\Support\Facades\DB::connection('kepegawaian')->table($tabel)
                ->whereNotNull($kolom)
                ->select($kolom);

            if ($tabel === 'riwayat_generator_cuti') {
                $query->where('is_duplikat', false);
            }

            $nilai = $query->pluck($kolom);
        } catch (\Throwable) {
            return 0;
        }

        foreach ($nilai as $satu) {
            if (! preg_match('{^(\d+)/'.preg_quote($kode, '{').'/([IVX]+)/(\d{4})$}', trim((string) $satu), $m)) {
                continue;
            }

            $bulan = $bulanKeAngka[$m[2]] ?? 0;
            $tahun = (int) $m[3];
            $seq = (int) $m[1];

            if ($bulan < 1 || $bulan > 12) {
                continue;
            }

            if ($terbaru === null || [$tahun, $bulan] > $terbaru) {
                $terbaru = [$tahun, $bulan];
                $maks = $seq;
            } elseif ([$tahun, $bulan] === $terbaru) {
                $maks = max($maks, $seq);
            }
        }

        return $maks;
    }

    /**
     * Normalisasi input terstruktur (hasil scrape extension) menjadi bentuk
     * "orang + rinci" yang sama dengan hasil parse teks.
     *
     * Angka "hari" dari extension DIABAIKAN — durasi selalu dihitung ulang
     * sebagai hari kerja dari tanggal_mulai..tanggal_selesai (lewati
     * Sabtu/Minggu + hari_libur), sama seperti input teks.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{orang: list<array{nama: string, rinci: list<array<string, mixed>>}>, tak_dikenali: list<string>, errors: list<string>}
     */
    public function dariRows(array $rows): array
    {
        $orang = [];
        $errors = [];

        foreach ($rows as $i => $row) {
            $nama = trim((string) ($row['nama'] ?? ''));
            $jenis = trim((string) ($row['jenis'] ?? ''));

            $mulai = $this->tanggal($row['tanggal_mulai'] ?? null);
            $selesai = $this->tanggal($row['tanggal_selesai'] ?? $row['tanggal_mulai'] ?? null);
            $pengajuan = trim((string) ($row['pengajuan_at'] ?? $row['pengajuan'] ?? ''));

            if ($nama === '' || $jenis === '' || $mulai === null || $pengajuan === '') {
                $errors[] = 'Baris '.($i + 1).': nama, jenis, tanggal_mulai, dan pengajuan_at wajib diisi.';

                continue;
            }

            $selesai ??= $mulai;

            if ($selesai->lt($mulai)) {
                $errors[] = 'Baris '.($i + 1).': tanggal_selesai lebih awal dari tanggal_mulai.';

                continue;
            }

            $hari = $this->hariKerja($mulai->copy()->startOfDay(), $selesai->copy()->startOfDay());

            if ($hari <= 0) {
                $errors[] = 'Baris '.($i + 1).': rentang tidak memuat hari kerja (Sabtu/Minggu/libur semua).';

                continue;
            }

            // Pengajuan dari data terstruktur bisa berupa "2026-10-05 15:49:19";
            // samakan ke bentuk tampilan "05-10-2026 15:49:19" supaya kunci
            // duplikat & tampilan identik dengan input teks.
            $pengajuan = $this->formatPengajuan($pengajuan);

            [$bulan, $tahun] = $this->bulanTahunPengajuan($pengajuan);
            $rentang = trim((string) ($row['rentang'] ?? ''));

            if ($rentang === '') {
                $rentang = $mulai->format('d').($mulai->isSameDay($selesai) ? '' : ' - '.$selesai->format('d'))
                    .' '.$selesai->locale('id')->translatedFormat('F Y');
            }

            $rinci = [
                'jenis' => $jenis,
                'hari' => $hari,
                'rentang' => $rentang,
                'pengajuan' => $pengajuan,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'tahun_cuti' => $mulai->year === $selesai->year ? $mulai->year : null,
                'mentah' => $jenis.' '.$hari.' hari ('.$rentang.') pengajuan '.$pengajuan,
            ];

            $index = $this->cariOrang($orang, $nama);

            if ($index === null) {
                $orang[] = ['nama' => $nama, 'rinci' => [$rinci]];
            } else {
                $orang[$index]['rinci'][] = $rinci;
            }
        }

        return ['orang' => $orang, 'tak_dikenali' => [], 'errors' => $errors];
    }

    /**
     * @param  list<array{nama: string, rinci: list<array<string, mixed>>}>  $orang
     */
    private function cariOrang(array $orang, string $nama): ?int
    {
        foreach ($orang as $i => $o) {
            if (mb_strtolower(trim($o['nama'])) === mb_strtolower($nama)) {
                return $i;
            }
        }

        return null;
    }

    private function tanggal(mixed $value): ?\Carbon\Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** "2026-10-05 15:49:19" / "2026-10-05T15:49:19" → "05-10-2026 15:49:19". */
    private function formatPengajuan(string $pengajuan): string
    {
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})(.*)$/', $pengajuan)) {
            return $pengajuan;
        }

        $coba = $this->tanggal($pengajuan);

        if ($coba === null) {
            return $pengajuan;
        }

        return $coba->format('d-m-Y').($coba->format('H:i:s') !== '00:00:00' ? ' '.$coba->format('H:i:s') : '');
    }

    /**
     * Arsipkan hasil generator sebagai satu batch di riwayat_generator_cuti.
     * Murni arsip — tidak dipakai perhitungan cuti. Dipakai halaman (manual)
     * dan API (extension) supaya perilaku simpan identik.
     *
     * @param  list<array<string, mixed>>  $hasil
     * @return string|null kode batch, atau null bila tabel riwayat belum ada
     */
    public function simpanRiwayatBatch(
        array $hasil,
        string $sumber = SuratCutiRiwayat::SUMBER_MANUAL,
        ?string $sumberLabel = null,
        ?string $teksMentah = null,
        ?\App\Models\User $user = null,
    ): ?string {
        if ($hasil === [] || ! $this->tabelRiwayatAda()) {
            return null;
        }

        $batch = now()->format('Ymd-His').'-'.($user?->getKey() ?? 0);

        \Illuminate\Support\Facades\DB::connection('kepegawaian')->transaction(function () use ($hasil, $batch, $sumber, $sumberLabel, $teksMentah, $user): void {
            foreach ($hasil as $h) {
                SuratCutiRiwayat::create([
                    'batch' => $batch,
                    'sumber' => $sumber,
                    'sumber_label' => $sumberLabel,
                    'nama' => $h['nama'],
                    'jenis' => $h['jenis'] ?? '—',
                    'hari' => $h['hari'] ?? 0,
                    'rentang' => $h['rentang'] ?? null,
                    // Kolom pengajuan varchar(50): isi datetime pendeknya,
                    // bukan teks mentah (bisa > 50 char).
                    'pengajuan' => substr((string) ($h['pengajuan_at'] ?? $h['pengajuan']), 0, 50) ?: null,
                    'nomor_surat' => $h['nomor'] ?? 'DUPLIKAT',
                    'is_duplikat' => ! empty($h['duplikat']),
                    'sisa' => $h['sisa'],
                    'teks_mentah' => $teksMentah,
                    'created_by' => $user?->getKey(),
                    'created_by_nama' => $user?->nama,
                ]);
            }
        });

        return $batch;
    }

    /**
     * Tabel riwayat ada di DB? (cek fresh, aman sebelum migrasi dijalankan)
     */
    public function tabelRiwayatAda(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::connection('kepegawaian')
                ->hasTable('riwayat_generator_cuti');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Daftar tahun kolom sisa: tahun berjalan + 2 tahun sebelumnya, urut
     * menurun (2026, 2025, 2024).
     *
     * @return list<int>
     */
    public function tahunTampil(?int $tahunBerjalan = null): array
    {
        $kini = $tahunBerjalan ?? (int) now()->year;

        return [$kini, $kini - 1, $kini - 2];
    }
}
