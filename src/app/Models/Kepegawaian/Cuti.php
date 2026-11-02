<?php

namespace App\Models\Kepegawaian;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Cuti extends Model
{
    protected $connection = 'kepegawaian';

    protected $table = 'cuti';

    /** Jatah cuti tahunan PNS per tahun, dalam hari kerja. */
    public const KUOTA_TAHUNAN = 12;

    /** Sisa cuti yang boleh dibawa ke tahun berikutnya (bila sisa positif). */
    public const MAX_BAWAAN = 6;

    /** Batas atas saldo (sisa) cuti yang bisa ditampilkan/dimiliki. */
    public const MAX_SISA = 24;

    /**
     * Penanda impor cuti terakhir (tanggal Y-m-d). Ditulis setiap impor
     * sukses — dipakai gerbang "wajib impor hari ini" (mis. Generator Surat
     * Cuti). Cache bisa hilang saat optimize:clear, jadi
     * sudahImporHariIni() juga menerima bukti sisi data (ada baris yang
     * created_at-nya hari ini).
     */
    public const IMPORT_HARIAN_CACHE_KEY = 'cuti:impor-harian-terakhir';

    public static function tandaiImporHariIni(): void
    {
        Cache::put(self::IMPORT_HARIAN_CACHE_KEY, now()->toDateString(), now()->endOfDay());
    }

    public static function sudahImporHariIni(): bool
    {
        if (Cache::get(self::IMPORT_HARIAN_CACHE_KEY) === now()->toDateString()) {
            return true;
        }

        $terakhir = self::query()->max('created_at');

        return $terakhir !== null && Carbon::parse($terakhir)->isToday();
    }

    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai_diajukan' => 'date',
            'tanggal_selesai_diajukan' => 'date',
            'diedit_manual' => 'boolean',
        ];
    }

    /**
     * cuti.nip is a plain column, not a declared FK — the referenced
     * pegawai_profil row may not exist (dump has stray/placeholder NIPs).
     */
    public function profil(): BelongsTo
    {
        return $this->belongsTo(PegawaiProfil::class, 'nip', 'nip');
    }

    public static function jenisOptions(): array
    {
        return [
            'Cuti Tahunan',
            'Cuti Sakit',
            'Cuti Melahirkan',
            'Cuti Karena Alasan Penting',
        ];
    }

    /**
     * Hitung durasi_hari dari pasangan tanggal, lalu tulis ke DB — dipakai
     * form create/edit supaya kolom Durasi otomatis terisi begitu tanggal
     * valid diisi, dan admin tinggal mengubahnya bila perlu.
     *
     * Aturan: hasil hitungan positif MENANG atas isian form (ditulis ulang).
     * Hasil 0/negatif (tanggal sama atau terbalik) atau tanggal kosong →
     * isian form dibiarkan apa adanya.
     */
    public static function hitungDurasiDariTanggal(array $data): array
    {
        if (empty($data['tanggal_mulai_diajukan']) || empty($data['tanggal_selesai_diajukan'])) {
            return $data;
        }

        $mulai = Carbon::parse($data['tanggal_mulai_diajukan']);
        $selesai = Carbon::parse($data['tanggal_selesai_diajukan']);
        $hitungan = $mulai->diffInDays($selesai) + 1;

        if ($hitungan > 0) {
            $data['durasi_hari'] = $hitungan;
        }

        return $data;
    }

    /**
     * Jumlah hari satu baris cuti (rentang inklusif): sama persis dengan
     * HARI_SQL yang dipakai rekapTahunan() di level SQL, versi PHP untuk satu
     * record.
     *
     * Hasil hitungan tanggal hanya dipakai bila positif. Bila hitungannya 0
     * atau negatif (tanggal sama, tanggal terbalik — data sumber, mis. id
     * 2694 — atau tanggal kosong), dipakai durasi_hari kolom DB: admin dapat
     * mengoreksi durasi baris begitu lewat form edit (kolom Durasi), dan
     * koreksinya dihormati di mana pun jumlah hari tampil.
     */
    public function jumlahHari(): int
    {
        $dariTanggal = 0;

        if ($this->tanggal_mulai_diajukan && $this->tanggal_selesai_diajukan) {
            $dariTanggal = $this->tanggal_mulai_diajukan->diffInDays($this->tanggal_selesai_diajukan) + 1;
        }

        return $dariTanggal > 0 ? $dariTanggal : (int) $this->durasi_hari;
    }

    /**
     * Satu baris cuti memakai jumlah hari pada DATEDIFF(tanggal_selesai,
     * tanggal_mulai) + 1 (rentang inklusif), tapi HANYA bila hasilnya
     * positif. Bila 0 atau negatif (tanggal sama, tanggal terbalik — data
     * sumber, mis. id 2694 — atau tanggal NULL), pakai kolom durasi_hari yang
     * bisa dikoreksi manual lewat form edit (CutiResource).
     *
     * Tanpa klem ini satu baris tanggal terbalik bisa bikin SUM per pegawai
     * jadi negatif besar dan mengotori seluruh rekap tahun itu (pernah
     * kejadian: "Terpakai 2026" tampil -89 utk satu pegawai — klem lama
     * GREATEST(..., 0) menghentikan negatifnya tapi menjatuhkan baris itu ke
     * 0, mengabaikan durasi_hari=2 yang sudah dikoreksi admin). Baris asli
     * di tabel cuti TIDAK diubah — klem ini cuma di titik agregasi rekap.
     */
    private const HARI_SQL = <<<'SQL'
        CASE
            WHEN tanggal_mulai_diajukan IS NOT NULL
                AND tanggal_selesai_diajukan IS NOT NULL
                AND DATEDIFF(tanggal_selesai_diajukan, tanggal_mulai_diajukan) + 1 > 0
            THEN DATEDIFF(tanggal_selesai_diajukan, tanggal_mulai_diajukan) + 1
            ELSE COALESCE(durasi_hari, 0)
        END
        SQL;

    /**
     * status_pegawai yang membuat "Cuti Karena Alasan Penting" ikut memotong
     * jatah cuti tahunan. PNS TIDAK dipotong oleh jenis ini (aturan
     * kepegawaian: cuti alasan penting PNS terpisah dari cuti tahunan);
     * PPPK dan PPPK paruh waktu DIPOTONG.
     */
    private const STATUS_ALASAN_PENTING_MEMOTONG = [
        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA',
        'PEGAWAI PEMERINTAH DENGAN PERJANJIAN KERJA PARUH WAKTU',
    ];

    /**
     * Apakah jenis cuti ini memotong jatah tahunan untuk status pegawai
     * tersebut. Aturan sama seperti rekap: "Cuti Tahunan" selalu memotong;
     * "Cuti Karena Alasan Penting" hanya untuk PPPK/PPPK paruh waktu;
     * Sakit/Melahirkan/Besar tidak memotong jatah tahunan siapa pun.
     */
    public static function memotongJatah(string $jenis, ?string $statusPegawai): bool
    {
        if (strcasecmp(trim($jenis), 'Cuti Tahunan') === 0) {
            return true;
        }

        return strcasecmp(trim($jenis), 'Cuti Karena Alasan Penting') === 0
            && in_array($statusPegawai, self::STATUS_ALASAN_PENTING_MEMOTONG, true);
    }

    /**
     * Rekap cuti tahunan per pegawai, satu pasang kolom Terpakai/Sisa per tahun
     * PLUS satu kolom Total.
     *
     * "Terpakai" = "Cuti Tahunan" untuk semua orang, PLUS "Cuti Karena Alasan
     * Penting" khusus PPPK/PPPK paruh waktu (lihat
     * STATUS_ALASAN_PENTING_MEMOTONG) — PNS tidak dipotong oleh jenis itu.
     *
     * "Sisa" tahun berjalan = jatah[y] − terpakai[y], DIBAYAR DULU bila tahun
     * sebelumnya minus (hutang): minus tahun lalu langsung memotong tahun
     * ini, dan tahun yang minus tampil 0 (bukan negatif) — kecuali tahun
     * TERAKHIR yang tampil apa adanya (hutang berjalan, belum ada tahun
     * depannya di jendela). Tampil tahun selain terakhir DIBATASI maksimal 6
     * (sama seperti batas kontribusinya ke Total). Surplus TIDAK dibawa
     * (tetap per tahun). Rantai dihitung dari tahun paling awal yang ada
     * datanya supaya hutang lama tetap terbawa walau jendela tampil bergeser.
     *
     *   sisa[y] = jatah[y] − terpakai[y] + min(sisa[y−1], 0)
     *   tampil[y] = sisa[y] bila y tahun terakhir,
     *              else min(max(sisa[y], 0), 6)
     *
     * Kolom Total-lah yang menggabungkan semua tahun yang ditampilkan:
     * tahun-tahun SELAIN yang terakhir kontribusinya dibatasi MAX_BAWAAN
     * (surplus dipotong, hutang/sisa negatif tetap lewat penuh karena
     * min(negatif, N) = negatif itu sendiri); tahun terakhir kontribusi penuh
     * apa adanya. Hasil akhirnya dibatasi MAX_SISA.
     *
     *   total = Σ min(tampil[y], MAX_BAWAAN) untuk y bukan tahun terakhir
     *         + tampil[tahun terakhir]
     *   total = min(total, MAX_SISA)
     *
     * Contoh terverifikasi (3 tahun 2024/2025/2026, jatah 12/tahun, pakai
     * 10/1/3 hari): tampil 2/6/9, total = 2 + 6 + 9 = 17.
     *
     * Contoh hutang (2025 −3, 2026 mandiri 8): tampil 2025 = 0,
     * 2026 = 8 − 3 = 5.
     *
     * @param  int[]  $tahun  tahun yang ditampilkan (urutan bebas, diurutkan di dalam)
     * @param  array<string, array<int, int>>  $tambahanPakai  pemakaian hipotetis
     *     tambahan per NIP per tahun (nip => [tahun => hari]) — mis. dari
     *     Generator Surat Cuti agar sisa tampil sudah dikurangi pengajuan
     *     yang baru dinomori (belum masuk DB). Aturan potong (jenis apa
     *     untuk status apa) ditentukan pemanggil via memotongJatah().
     * @return Collection<int, array<string, mixed>> satu baris per pegawai
     */
    public static function rekapTahunan(array $tahun, array $tambahanPakai = []): Collection
    {
        $tahunTampil = array_map('intval', $tahun);
        sort($tahunTampil);
        $tahunTerakhir = $tahunTampil[array_key_last($tahunTampil)];

        // Populasi rekap = SEMUA pegawai aktif (users.is_aktif = 1, via
        // PegawaiProfil::nipAktif()), bukan cuma yang kebetulan sudah punya
        // baris cuti tahunan. Pegawai tanpa cuti tetap tampil dengan
        // Terpakai=0 / Sisa=jatah penuh — itu memang benar, bukan hilang.
        //
        // Efek sampingnya: NIP nyasar di tabel cuti (typo/tak pernah cocok ke
        // profil pegawai manapun, bahkan di prefix 14 digit sekalipun — beda
        // dari kasus korupsi Excel yang cuma menghancurkan sufiks) otomatis
        // tak ikut muncul, karena populasi sekarang ditentukan dari daftar
        // pegawai, bukan dari isi tabel cuti.
        $nipList = PegawaiProfil::nipAktif();

        if ($nipList === []) {
            return collect();
        }

        // Terpakai per (nip, jenis, tahun) — jenis TIDAK di-SUM bareng di SQL,
        // karena "Cuti Karena Alasan Penting" cuma ikut memotong utk sebagian
        // status_pegawai (lihat STATUS_ALASAN_PENTING_MEMOTONG); jenis mana
        // yang dijumlah ditentukan per-nip di loop bawah setelah tahu status.
        // SELURUH tahun diambil (bukan cuma yang ditampilkan): rantai hutang
        // butuh tahun-tahun sebelum jendela tampil. Hanya baris yang mulai
        // DAN selesai di tahun takwim yang sama (baris lintas tahun
        // diabaikan). Match nip persis: prefix-fuzzy tak menambah hasil
        // (dicek — prefix 14/15/16 digit menghasilkan set NIP tercocok yang
        // sama persis dengan exact match untuk data saat ini).
        $terpakai = self::query()
            ->whereIn('jenis', ['Cuti Tahunan', 'Cuti Karena Alasan Penting'])
            ->whereIn('nip', $nipList)
            ->whereRaw('YEAR(tanggal_mulai_diajukan) = YEAR(tanggal_selesai_diajukan)')
            ->selectRaw('nip')
            ->selectRaw('jenis')
            ->selectRaw('YEAR(tanggal_mulai_diajukan) as tahun')
            ->selectRaw('SUM('.self::HARI_SQL.') as hari')
            ->groupBy('nip', 'jenis', 'tahun')
            ->get()
            ->groupBy('nip');

        // Nama + status_pegawai dari pegawai_profil — nama sumber yang selalu
        // ada utk seluruh populasi (kolom cuti.nama bisa kosong, dan tak ada
        // sama sekali utk pegawai yang belum pernah mengajukan cuti); status
        // menentukan jenis mana yang ikut memotong (lihat di atas).
        $profil = PegawaiProfil::query()
            ->whereIn('nip', $nipList)
            ->get(['nip', 'nama', 'status_pegawai'])
            ->keyBy('nip');

        $kuota = CutiKuotaTahunan::query()
            ->whereIn('nip', $nipList)
            ->get()
            ->groupBy('nip');

        $statusJabatanExact = [];
        $statusJabatanPrefix15 = [];

        foreach (User::query()
            ->where('is_aktif', 1)
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->get(['nip', 'status_jabatan']) as $user) {
            $digit = preg_replace('/\D+/', '', (string) $user->nip);
            if ($digit === '') {
                continue;
            }
            $statusJabatanExact[$digit] = $user->status_jabatan;
            if (strlen($digit) >= 15) {
                $statusJabatanPrefix15[substr($digit, 0, 15)] = $user->status_jabatan;
            }
        }

        $baris = [];

        foreach ($nipList as $nip) {
            $statusPegawai = $profil->get($nip)?->status_pegawai;
            $jenisIkut = in_array($statusPegawai, self::STATUS_ALASAN_PENTING_MEMOTONG, true)
                ? ['Cuti Tahunan', 'Cuti Karena Alasan Penting']
                : ['Cuti Tahunan'];

            $hariPerTahun = $terpakai->get($nip, collect())
                ->filter(fn ($row) => in_array($row->jenis, $jenisIkut, true))
                ->groupBy('tahun')
                ->map(fn (Collection $rows) => $rows->sum('hari'));

            // Tambahan hipotetis (belum tercatat di DB) — digabung sebelum
            // rantai hutang supaya aturannya ikut berlaku penuh.
            foreach ($tambahanPakai[$nip] ?? [] as $thnTambahan => $hariTambahan) {
                $thnTambahan = (int) $thnTambahan;
                $hariPerTahun[$thnTambahan] = (int) ($hariPerTahun->get($thnTambahan) ?? 0) + (int) $hariTambahan;
            }

            $kuotaPerTahun = $kuota->get($nip, collect())->pluck('kuota_hari', 'tahun');

            $digitNip = preg_replace('/\D+/', '', (string) $nip);
            $statusJabatan = $statusJabatanExact[$digitNip]
                ?? (strlen($digitNip) >= 15 ? ($statusJabatanPrefix15[substr($digitNip, 0, 15)] ?? null) : null);

            $record = [
                'nip' => $nip,
                'nama' => $profil->get($nip)?->nama ?? $nip,
                'status_jabatan' => $statusJabatan,
            ];

            // Rantai hutang dari tahun paling awal yang ada datanya
            // (terpakai/kuota) sampai tahun terakhir tampil — tahun tanpa
            // data mandiri penuh (jatah − 0) sehingga tak mengubah rantai.
            $tahunAdaData = $hariPerTahun->keys()->merge($kuotaPerTahun->keys())->map(fn ($y) => (int) $y);
            $awalRantai = min(array_merge($tahunTampil, $tahunAdaData->all() ?: $tahunTampil));

            $rantai = [];
            $bawaan = 0; // sisa tahun sebelumnya (negatif = hutang dibawa)

            for ($y = $awalRantai; $y <= $tahunTerakhir; $y++) {
                $jatah = (int) ($kuotaPerTahun->get($y) ?? self::KUOTA_TAHUNAN);
                $pakai = (int) ($hariPerTahun->get($y) ?? 0);
                $mentah = $jatah - $pakai + min($bawaan, 0);
                $rantai[$y] = ['jatah' => $jatah, 'pakai' => $pakai, 'mentah' => $mentah];
                $bawaan = $mentah;
            }

            $totalGabungan = 0;

            foreach ($tahunTampil as $y) {
                $r = $rantai[$y];
                // Tahun yang minus langsung 0 dan hutangnya memotong tahun
                // depan; tahun terakhir tampil apa adanya (hutang berjalan
                // yang belum ada tahun depannya di jendela). Tahun selain
                // terakhir tampil maksimal 6 (batas bawaan ke Total).
                $mentah = $y === $tahunTerakhir ? $r['mentah'] : max(0, $r['mentah']);
                $sisa = $y === $tahunTerakhir ? $mentah : min($mentah, self::MAX_BAWAAN);

                $record['jatah_'.$y] = $r['jatah'];
                $record['total_hari_'.$y] = $r['pakai'];
                $record['sisa_hari_'.$y] = $sisa;

                $totalGabungan += ($y === $tahunTerakhir) ? $sisa : min($sisa, self::MAX_BAWAAN);
            }

            $record['total_sisa'] = min($totalGabungan, self::MAX_SISA);

            $baris[] = $record;
        }

        return collect($baris);
    }
}
