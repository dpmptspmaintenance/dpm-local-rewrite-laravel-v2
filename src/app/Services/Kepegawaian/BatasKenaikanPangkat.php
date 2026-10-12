<?php

namespace App\Services\Kepegawaian;

/**
 * Batas maksimal kenaikan pangkat berdasarkan pendidikan terakhir — satu
 * sumber kebenaran untuk halaman Rekap KGB & KP dan widget alert beranda.
 *
 * Tabel acuan (jenjang pendidikan → golongan awal CPNS → batas maks jalur
 * pelaksana/non-JF → batas maks jalur fungsional/eselon):
 *
 *   SD/Sederajat                I/a  → II/a    → II/a
 *   SMP/SLTP                    I/c  → II/c    → II/c
 *   SMA/SMK/D-I                 II/a → III/b   → III/b (JF Terampil Pemula)
 *   D-II                        II/b → III/b   → III/b
 *   D-III                       II/c → III/c   → III/d (JF Penyelia)
 *   S-1/D-IV                    III/a→ III/d   → IV/c–IV/e (JF Ahli Madya/Utama atau Eselon)
 *   S-2/Dokter/Apoteker         III/b→ IV/a    → IV/c–IV/e
 *   S-3/Doktor                  III/c→ IV/b    → IV/e (Pembina Utama)
 *
 * Jalur karier ditentukan dari jenis jabatan pada baris riwayat jabatan
 * TERAKHIR (pegawai_riwayat_jabatan.jenis_jabatan):
 *   - Struktural          → Eselon      (plafon kolom 4)
 *   - Fungsional Tertentu → Fungsional  (plafon kolom 4)
 *   - Fungsional Umum dll → Pelaksana   (plafon kolom 3)
 */
class BatasKenaikanPangkat
{
    /**
     * @var array<string, array{label: string, awal: string, maks_non_jf: string, maks_jf: string, maks_jf_label: string}>
     */
    public const ATURAN = [
        'SD' => ['label' => 'SD/Sederajat', 'awal' => 'I/a', 'maks_non_jf' => 'II/a', 'maks_jf' => 'II/a', 'maks_jf_label' => 'II/a'],
        'SMP' => ['label' => 'SMP/SLTP', 'awal' => 'I/c', 'maks_non_jf' => 'II/c', 'maks_jf' => 'II/c', 'maks_jf_label' => 'II/c'],
        'SMA_D1' => ['label' => 'SMA/SMK/D-I', 'awal' => 'II/a', 'maks_non_jf' => 'III/b', 'maks_jf' => 'III/b', 'maks_jf_label' => 'III/b (JF Terampil Pemula)'],
        'D2' => ['label' => 'D-II', 'awal' => 'II/b', 'maks_non_jf' => 'III/b', 'maks_jf' => 'III/b', 'maks_jf_label' => 'III/b'],
        'D3' => ['label' => 'D-III', 'awal' => 'II/c', 'maks_non_jf' => 'III/c', 'maks_jf' => 'III/d', 'maks_jf_label' => 'III/d (JF Penyelia)'],
        'S1_D4' => ['label' => 'S-1/D-IV', 'awal' => 'III/a', 'maks_non_jf' => 'III/d', 'maks_jf' => 'IV/e', 'maks_jf_label' => 'IV/c–IV/e (JF Ahli Madya/Utama atau Eselon)'],
        'S2' => ['label' => 'S-2/Dokter/Apoteker', 'awal' => 'III/b', 'maks_non_jf' => 'IV/a', 'maks_jf' => 'IV/e', 'maks_jf_label' => 'IV/c–IV/e (JF Ahli Madya/Utama atau Eselon)'],
        'S3' => ['label' => 'S-3/Doktor', 'awal' => 'III/c', 'maks_non_jf' => 'IV/b', 'maks_jf' => 'IV/e', 'maks_jf_label' => 'IV/e (Pembina Utama)'],
    ];

    /** Jalur karier. */
    public const JALUR_STRUKTURAL = 'struktural';
    public const JALUR_FUNGSIONAL = 'fungsional';
    public const JALUR_PELAKSANA = 'pelaksana';

    /** Peta angka romawi golongan PNS ke basis. */
    private const ROMAWI = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4];

    /**
     * Normalisasi string pendidikan bebas menjadi kunci jenjang ATURAN.
     * Null bila tak dikenali.
     */
    public static function levelDariPendidikan(?string $pendidikan): ?string
    {
        $u = strtoupper(trim((string) $pendidikan));
        if ($u === '') {
            return null;
        }

        return match (true) {
            (bool) preg_match('/^S-?\s?3\b|DOKTOR|STRATA\s?3/', $u) => 'S3',
            (bool) preg_match('/^S-?\s?2\b|MAGISTER|DOKTER|APOTEKER/', $u) => 'S2',
            (bool) preg_match('/^S-?\s?1\b|SARJANA/', $u) => 'S1_D4',
            (bool) preg_match('/^D-?\s?(IV|4)\b/', $u) => 'S1_D4',
            (bool) preg_match('/^D-?\s?(III|3)\b/', $u) => 'D3',
            (bool) preg_match('/^D-?\s?(II|2)\b/', $u) => 'D2',
            (bool) preg_match('/^D-?\s?(I|1)\b/', $u) => 'SMA_D1',
            (bool) preg_match('/SLTA|SMA|SMU|SMK|SEKOLAH MENENGAH/', $u) => 'SMA_D1',
            (bool) preg_match('/SLTP|SMP/', $u) => 'SMP',
            (bool) preg_match('/^SD\b|SEKOLAH DASAR/', $u) => 'SD',
            default => null,
        };
    }

    /**
     * Urutan numerik golongan (monoton menaik) untuk perbandingan.
     * I/a=0, I/b=1, …, IV/e=19. Null bila format tak dikenali.
     */
    public static function urutan(?string $golongan): ?int
    {
        $g = strtoupper(str_replace(' ', '', trim((string) $golongan)));
        if (! preg_match('#^(I|II|III|IV)/([A-E])$#', $g, $m)) {
            return null;
        }

        return (self::ROMAWI[$m[1]] - 1) * 5 + (ord($m[2]) - ord('A'));
    }

    /** Kata kunci nama jabatan yang menandakan fungsional. */
    private const KATA_FUNGSIONAL = ['AHLI', 'TERAMPIL', 'PENGATUR'];

    /**
     * Jalur karier dari jenis jabatan + nama jabatan (baris riwayat jabatan
     * TERAKHIR):
     *  - jenis 'Struktural'                      → eselon
     *  - nama jabatan mengandung ahli/terampil/
     *    pengatur                                → fungsional
     *  - selain itu                              → pelaksana
     *
     * @return 'struktural'|'fungsional'|'pelaksana'
     */
    public static function jalurDariJabatan(?string $jenisJabatan, ?string $jabatan = null): string
    {
        if (str_contains(strtoupper(trim((string) $jenisJabatan)), 'STRUKTURAL')) {
            return self::JALUR_STRUKTURAL;
        }

        $j = strtoupper(trim((string) $jabatan));
        foreach (self::KATA_FUNGSIONAL as $kata) {
            if (str_contains($j, $kata)) {
                return self::JALUR_FUNGSIONAL;
            }
        }

        return self::JALUR_PELAKSANA;
    }

    /** Label singkat jalur untuk badge/tooltip. */
    public static function labelJalur(string $jalur): string
    {
        return match ($jalur) {
            self::JALUR_STRUKTURAL => 'Eselon',
            self::JALUR_FUNGSIONAL => 'Fungsional',
            default => 'Pelaksana',
        };
    }

    /**
     * Evaluasi batas KP untuk satu pegawai.
     *
     * Jalur eselon (struktural) dan fungsional memakai plafon kolom 4 yang
     * lebih tinggi; pelaksana/non-JF memakai plafon kolom 3.
     *
     * @param  'struktural'|'fungsional'|'pelaksana'  $jalur
     * @return array{
     *     level: ?string,
     *     label: ?string,
     *     awal: ?string,
     *     jalur: string,
     *     jalur_label: string,
     *     maks: ?string,
     *     maks_label: ?string,
     *     jalur_tinggi: bool,
     *     urutan_sekarang: ?int,
     *     urutan_maks: ?int,
     *     status: 'aman'|'maks'|'lewat'|'unknown'
     * }
     */
    public static function evaluasi(?string $pendidikan, ?string $golongan, string $jalur = self::JALUR_PELAKSANA): array
    {
        $level = self::levelDariPendidikan($pendidikan);
        // Eselon & fungsional memakai plafon lebih tinggi (kolom 4).
        $jalurTinggi = in_array($jalur, [self::JALUR_STRUKTURAL, self::JALUR_FUNGSIONAL], true);

        $hasil = [
            'level' => $level,
            'label' => $level ? self::ATURAN[$level]['label'] : null,
            'awal' => $level ? self::ATURAN[$level]['awal'] : null,
            'jalur' => $jalur,
            'jalur_label' => self::labelJalur($jalur),
            'maks' => null,
            'maks_label' => null,
            'jalur_tinggi' => $jalurTinggi,
            'urutan_sekarang' => self::urutan($golongan),
            'urutan_maks' => null,
            'status' => 'unknown',
        ];

        if ($level === null) {
            return $hasil;
        }

        $aturan = self::ATURAN[$level];
        $hasil['maks'] = $jalurTinggi ? $aturan['maks_jf'] : $aturan['maks_non_jf'];
        $hasil['maks_label'] = $jalurTinggi ? $aturan['maks_jf_label'] : $aturan['maks_non_jf'];
        $hasil['urutan_maks'] = self::urutan($hasil['maks']);

        $now = self::urutan($golongan);
        if ($now === null || $hasil['urutan_maks'] === null) {
            return $hasil;
        }

        $hasil['status'] = match (true) {
            $now > $hasil['urutan_maks'] => 'lewat',
            $now === $hasil['urutan_maks'] => 'maks',
            default => 'aman',
        };

        return $hasil;
    }

    /** Apakah pegawai sudah di/lewat batas maksimal KP? */
    public static function sudahMaksimal(?string $pendidikan, ?string $golongan, string $jalur = self::JALUR_PELAKSANA): bool
    {
        return in_array(self::evaluasi($pendidikan, $golongan, $jalur)['status'], ['maks', 'lewat'], true);
    }
}
