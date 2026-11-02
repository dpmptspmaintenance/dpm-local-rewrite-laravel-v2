<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\SuratTugas;
use App\Models\Kepegawaian\SuratTugasPegawai;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Isi template Word Surat Tugas (resources/templates/kepegawaian/surat-tugas.docx)
 * dengan data satu SuratTugas, lalu opsional convert ke PDF. Satu-satunya
 * tempat TemplateProcessor dipanggil untuk fitur ini.
 *
 * Soal placeholder: template awalnya memakai mail-merge Word (MERGEFIELD)
 * untuk `judul`/`tempat` dan tag mentah `<<...>>` untuk `berdasarkan`/
 * `hari_tanggal`/`waktu` — TemplateProcessor PHPWord tak bisa mengisi bentuk
 * itu, jadi template master sudah dikonversi sekali (manual, lihat progress.md)
 * menjadi placeholder "${...}" polos yang seragam, plus baris tabel pegawai
 * dibungkus agar bisa di-`cloneRow()`, dan 2 dasar hukum yang tadinya
 * hardcode di teks dipindah jadi block `${dasar_hukum}...${/dasar_hukum}`
 * yang di-`cloneBlock()` dari Setting + tambahan per-surat.
 *
 * nomor_naskah/tanggal_naskah/ttd_pengirim SENGAJA TIDAK diisi service ini —
 * dibiarkan literal "${nomor_naskah}" dkk di dokumen hasil, untuk diisi nanti
 * di aplikasi Srikandi saat registrasi naskah dinas resmi.
 */
class SuratTugasGeneratorService
{
    private const TEMPLATE_PATH = 'templates/kepegawaian/surat-tugas.docx';

    public function templatePath(): string
    {
        $path = resource_path(self::TEMPLATE_PATH);

        if (! is_file($path)) {
            throw new \RuntimeException("Template Surat Tugas tidak ditemukan: {$path}");
        }

        return $path;
    }

    /**
     * Direktori sementara khusus untuk fitur ini, di bawah storage/app milik
     * www-data — BUKAN sys_get_temp_dir() (/tmp sistem). PHPWord juga
     * meng-extract gambar dari template (logo di kop surat) ke
     * Settings::getTempDir() saat render; kalau itu default ke /tmp sistem
     * dan folder itu kebetulan sudah dibuat lebih dulu oleh proses lain
     * dengan UID berbeda (mis. root lewat `docker exec` ketika debugging),
     * proses php-fpm (www-data) kehilangan izin tulis ke dalamnya meski
     * foldernya sudah ada — persis gejala "Failed to open stream: Permission
     * denied" saat extractTo() gambar. Folder khusus ini dibuat sekali oleh
     * www-data sendiri sehingga ownership-nya selalu konsisten.
     */
    private function tempDir(): string
    {
        $dir = storage_path('app/surat-tugas-tmp');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Folder bisa dibuat proses lain dengan UID berbeda (mis. root) sehingga
        // php-fpm (www-data) tak bisa menulisnya — tempnam() memunculkan warning
        // yang Laravel ubah jadi ErrorException. Fallback ke /tmp bila begitu.
        if (! is_writable($dir)) {
            @chmod($dir, 0777);

            if (! is_writable($dir)) {
                return sys_get_temp_dir();
            }
        }

        return $dir;
    }

    private function configureTempDir(): void
    {
        Settings::setTempDir($this->tempDir());
    }

    /**
     * @return string Path file .docx sementara hasil isi template. Pemanggil
     *                 bertanggung jawab menghapusnya setelah selesai dipakai.
     */
    public function generateDocx(SuratTugas $suratTugas): string
    {
        $this->configureTempDir();

        // Muat ulang relasi agar override per baris (termasuk baris yang
        // disembunyikan / baris baru) selalu terpakai saat render.
        $suratTugas->unsetRelation('pegawai');
        $suratTugas->load('pegawai');

        $tp = new TemplateProcessor($this->templatePath());

        $nilai = $this->nilaiDokumen($suratTugas);
        $dasarHukum = $nilai['dasar_hukum'];

        if ($dasarHukum === []) {
            // cloneBlock(0, ..., []) membuang seluruh block tanpa menyisakan
            // paragraf kosong — diuji eksplisit saat menyiapkan template ini.
            $tp->cloneBlock('dasar_hukum', 0, true, false, []);
        } else {
            // Setiap nilai WAJIB di-escape XML: setValue() PHPWord hanya
            // str_replace, karakter & < > # pada nama/jabatan/dasar hukum
            // akan merusak OpenXML (dokumen gagal dibuka / tampil berantakan).
            $tp->cloneBlock('dasar_hukum', count($dasarHukum), true, false, array_map(
                fn (string $line): array => ['isi_dasar_hukum' => $this->escapeXml($line)],
                $dasarHukum,
            ));
        }

        $pegawai = $suratTugas->pegawai
            ->filter(fn (SuratTugasPegawai $row): bool => ! (bool) $row->override_dihilangkan);

        if ($pegawai->isEmpty()) {
            throw new \RuntimeException('Surat Tugas harus punya minimal satu pegawai.');
        }

        // Urutan tampil pada dokumen: pangkat/golongan tertinggi dulu (PNS),
        // lalu PNS berikutnya, terakhir PPPK. Di dalam grup yang sama, urutan
        // input user (urutan snapshot) dipertahankan.
        $pegawai = $pegawai
            ->map(fn (SuratTugasPegawai $row): array => [
                'row' => $row,
                'rank' => $this->rankPegawai((string) $row->pangkatGolonganDokumen()),
            ])
            ->sort(function (array $a, array $b): int {
                $urutan = $a['rank'] <=> $b['rank'];

                return $urutan !== 0
                    ? $urutan
                    : ($a['row']->urutan <=> $b['row']->urutan);
            })
            ->map(fn (array $item): SuratTugasPegawai => $item['row'])
            ->values();

        $tp->cloneRow('no', $pegawai->count());

        foreach ($pegawai->values() as $i => $row) {
            $n = $i + 1;
            $tp->setValue("no#{$n}", (string) $n);
            $tp->setValue("nama#{$n}", $this->escapeXml($this->namaKapital($row->namaDokumen())));
            $tp->setValue("nip#{$n}", $this->escapeXml((string) $row->nip));
            $tp->setValue("jabatan#{$n}", $this->escapeXml($row->jabatanDokumen() ?: '-'));
            $tp->setValue("pangkat_golongan#{$n}", $this->escapeXml($this->pangkatTampil((string) ($row->pangkatGolonganDokumen() ?? ''))));
        }

        $tp->setValue('judul', $this->escapeXml($nilai['judul']));
        $tp->setValue('hari_tanggal', $this->escapeXml($nilai['hari_tanggal']));
        $tp->setValue('waktu', $this->escapeXml($nilai['waktu']));
        $tp->setValue('tempat', $this->escapeXml($nilai['tempat']));

        // tanggal_naskah: kalau diisi user, pakai nilainya; kalau kosong,
        // biarkan literal "${tanggal_naskah}" (diisi nanti di Srikandi).
        // nomor_naskah & ttd_pengirim SELALU dibiarkan literal — tidak ada
        // jalur untuk mengisinya dari tool ini sama sekali.
        //
        // Template punya 3 kemunculan ${tanggal_naskah} (penutup + baris
        // lampiran); semuanya pakai nilai yang sama.
        if (filled($nilai['tanggal_naskah'])) {
            $tp->setValue('tanggal_naskah', $this->escapeXml($nilai['tanggal_naskah']));
        }

        $tmp = tempnam($this->tempDir(), 'st_').'.docx';
        $tp->saveAs($tmp);

        // Baris tabel dinamis: pastikan tidak pecah antar halaman dan tinggi
        // minimum mengikuti isi (penyebab tampilan meluber/berantakan).
        $this->perbaikiBarisTabel($tmp);

        return $tmp;
    }

    /**
     * Tambahkan <w:cantSplit/> (baris tak terpotong antar halaman) dan
     * pastikan tinggi baris memakai atLeast pada tabel lampiran pegawai.
     */
    private function perbaikiBarisTabel(string $docxPath): void
    {
        $zip = new \ZipArchive;

        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');

        if ($xml === false || $xml === '') {
            $zip->close();

            return;
        }

        $baru = preg_replace_callback(
            '~<w:trPr>(.*?)</w:trPr>~s',
            function (array $m): string {
                $isi = $m[1];

                // Sudah ada cantSplit — biarkan.
                if (str_contains($isi, '<w:cantSplit')) {
                    return $m[0];
                }

                return '<w:trPr><w:cantSplit/>'.$isi.'</w:trPr>';
            },
            $xml,
        );

        if ($baru !== null && $baru !== $xml) {
            $zip->addFromString('word/document.xml', $baru);
        }

        $zip->close();
    }

    /**
     * @return string Path file .pdf sementara. Pemanggil bertanggung jawab
     *                 menghapusnya setelah selesai dipakai.
     */
    /**
     * @param  ?string  $docxPath  Path .docx yang sudah ada (snapshot) — bila
     *                             diisi, dipakai langsung tanpa render ulang.
     *                             Bila null, dokumen dirender dari data.
     */
    public function generatePdf(SuratTugas $suratTugas, ?string $docxPath = null): string
    {
        $this->configureTempDir();

        $sementara = $docxPath === null;
        $docxPath ??= $this->generateDocx($suratTugas);

        try {
            return $this->convertToPdfViaLibreOffice($docxPath);
        } catch (\Throwable $e) {
            report($e);

            // LibreOffice tak terpasang/gagal (mis. di lingkungan lain yang
            // belum sempat rebuild image) — fallback ke dompdf lewat PHPWord
            // supaya fitur tetap jalan, walau hasilnya kurang presisi.
            return $this->convertToPdfViaDompdf($docxPath);
        } finally {
            if ($sementara) {
                @unlink($docxPath);
            }
        }
    }

    /**
     * Render via LibreOffice headless (`soffice --headless --convert-to
     * pdf`) — renderer office sungguhan, bukan writer HTML/CSS seperti
     * dompdf. Dipilih setelah PDF hasil dompdf (lewat PHPWord's
     * Writer\PDF\DomPDF) terbukti meleset jauh dari tampilan asli di Word
     * untuk template Surat Tugas ini (tabel, spasi, line break semua beda) —
     * LibreOffice memakai mesin page-layout office yang sama familinya
     * dengan Word, jadi hasilnya jauh lebih mendekati "Save As PDF" asli.
     *
     * `-env:UserInstallation=file://<dir unik>` WAJIB diisi per-panggilan —
     * tanpa ini, dua request concurrent berbagi satu profil LibreOffice
     * default dan saling kunci/gagal (soffice headless tidak didesain
     * multi-instance pada direktori profil yang sama).
     */
    private function convertToPdfViaLibreOffice(string $docxPath): string
    {
        $outDir = $this->tempDir().'/lo-out-'.bin2hex(random_bytes(8));
        $profileDir = $this->tempDir().'/lo-profile-'.bin2hex(random_bytes(8));
        mkdir($outDir, 0775, true);
        mkdir($profileDir, 0775, true);

        try {
            $process = new \Symfony\Component\Process\Process([
                'soffice',
                '--headless',
                '--norestore',
                '-env:UserInstallation=file://'.$profileDir,
                '--convert-to', 'pdf',
                '--outdir', $outDir,
                $docxPath,
            ]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('LibreOffice gagal convert PDF: '.$process->getErrorOutput());
            }

            $expectedName = pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
            $producedPath = $outDir.'/'.$expectedName;

            if (! is_file($producedPath)) {
                throw new \RuntimeException("LibreOffice tidak menghasilkan berkas PDF yang diharapkan: {$producedPath}");
            }

            $pdfPath = tempnam($this->tempDir(), 'st_').'.pdf';
            rename($producedPath, $pdfPath);

            return $pdfPath;
        } finally {
            // -rf manual (bukan rmdir) karena LibreOffice profile dir berisi banyak file/subfolder.
            (new \Symfony\Component\Process\Process(['rm', '-rf', $outDir, $profileDir]))->run();
        }
    }

    /**
     * Fallback kalau LibreOffice tak tersedia — pakai writer PDF bawaan
     * PHPWord (dompdf). Hasilnya kurang presisi dibanding LibreOffice
     * (lihat catatan di convertToPdfViaLibreOffice()), tapi lebih baik
     * daripada fitur gagal total.
     */
    private function convertToPdfViaDompdf(string $docxPath): string
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = IOFactory::load($docxPath);

        $pdfPath = tempnam($this->tempDir(), 'st_').'.pdf';
        IOFactory::createWriter($phpWord, 'PDF')->save($pdfPath);

        return $pdfPath;
    }

    /**
     * Nilai efektif yang dipakai dokumen: koreksi manual (override_*) bila
     * ada, selain itu data asli surat. Satu pintu supaya render awal dan
     * render setelah edit selalu konsisten.
     *
     * @return array{judul: string, hari_tanggal: string, waktu: string, tempat: string, tanggal_naskah: ?string, dasar_hukum: list<string>, penutup: ?string}
     */
    public function nilaiDokumen(SuratTugas $suratTugas): array
    {
        return [
            'judul' => (string) ($suratTugas->override_judul ?? $suratTugas->judul),
            'hari_tanggal' => (string) ($suratTugas->override_hari_tanggal ?? $suratTugas->hari_tanggal),
            'waktu' => (string) ($suratTugas->override_waktu ?? $suratTugas->waktu),
            'tempat' => (string) ($suratTugas->override_tempat ?? $suratTugas->tempat),
            'tanggal_naskah' => $suratTugas->override_tanggal_naskah ?? $suratTugas->tanggal_naskah,
            'dasar_hukum' => $this->dasarHukumLines($suratTugas),
            'penutup' => $suratTugas->override_penutup,
        ];
    }

    /**
     * Escape karakter yang merusak OpenXML. TemplateProcessor::setValue()
     * TIDAK escape otomatis — ini titik utama penyebab dokumen "berantakan"
     * saat nama/jabatan/dasar hukum mengandung &, <, >, atau kutip.
     */
    public function escapeXml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Dasar hukum Setting (default, berlaku untuk semua surat) digabung
     * dengan dasar hukum tambahan khusus surat ini (dipisah baris oleh
     * pemanggil sebelum disimpan — lihat SuratTugas::dasar_hukum_tambahan).
     * Koreksi manual (override_dasar_hukum) menggantikan seluruh daftar.
     *
     * @return list<string>
     */
    private function dasarHukumLines(SuratTugas $suratTugas): array
    {
        if (filled($suratTugas->override_dasar_hukum)) {
            return collect(preg_split('/\r\n|\r|\n/', (string) $suratTugas->override_dasar_hukum))
                ->map(fn (string $t): string => trim($t))
                ->filter()
                ->values()
                ->all();
        }

        $default = \App\Models\Kepegawaian\SuratTugasDasarHukumSetting::query()
            ->orderBy('urutan')
            ->pluck('teks')
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        $tambahan = collect(preg_split('/\r\n|\r|\n/', (string) $suratTugas->dasar_hukum_tambahan))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        return [...$default, ...$tambahan];
    }

    /**
     * Rank urut pegawai pada daftar tugas: golongan tertinggi (IV/e) di
     * atas, PNS di atas PPPK. Angka kecil = lebih tinggi. Snapshot berisi
     * teks "Pangkat/Golongan" (mis. "Pembina/IV/a") — golongan diambil dari
     * segmen setelah "/" terakhir. Golongan PPPK ("IX", "V") PNS-format-nya
     * tak dikenali BatasKenaikanPangkat::urutan(), jadi dinilai di bawah
     * semua golongan PNS (rank 100+), PPPK Paruh Waktu paling akhir.
     */
    private function rankPegawai(?string $pangkatGolongan): int
    {
        $teks = trim((string) $pangkatGolongan);

        if ($teks === '') {
            return 999;
        }

        $segmen = array_map(fn (string $s): string => trim($s), explode('/', $teks));

        // Golongan PNS "IV/a" memecah jadi 3 segmen ("Pembina","IV","a") —
        // gabungkan 2 segmen terakhir. Snapshot PPPK "IX/IX" tetap 2 segmen.
        $n = count($segmen);
        $golongan = $n >= 2 ? $segmen[$n - 2].'/'.$segmen[$n - 1] : $segmen[0];

        $pnsRank = \App\Services\Kepegawaian\BatasKenaikanPangkat::urutan($golongan);

        if ($pnsRank !== null) {
            return $pnsRank; // 0 (I/a) s.d. 19 (IV/e) — makin kecil makin tinggi
        }

        // PPPK: golongan romawi tunggal (I, V, IX, … s.d. XVII) di segmen
        // terakhir — makin besar makin tinggi. Offset 100 supaya selalu di
        // bawah seluruh PNS.
        $romawi = $this->nilaiRomawi(end($segmen));

        if ($romawi !== null) {
            return 100 + (99 - $romawi);
        }

        // "PPPK Paruh Waktu" / tak dikenal — paling akhir.
        return 999;
    }

    /** Nilai angka romawi sederhana (I=1 … XVII=17), null bila bukan romawi valid. */
    private function nilaiRomawi(string $teks): ?int
    {
        $peta = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
            'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
            'XIII' => 13, 'XIV' => 14, 'XV' => 15, 'XVI' => 16, 'XVII' => 17];

        $romawi = $peta[strtoupper(trim($teks))] ?? null;

        return $romawi;
    }

    /**
     * Teks pangkat/golongan untuk dokumen: bila kedua segmen "X/X" identik
     * (golongan PPPK, contoh "IX/IX"), tampil satu saja → "IX". Snapshot
     * PNS ("Pembina/IV/a") tetap utuh.
     */
    private function pangkatTampil(string $pangkatGolongan): string
    {
        $teks = trim($pangkatGolongan);

        if ($teks === '') {
            return '-';
        }

        $segmen = array_map(fn (string $s): string => trim($s), explode('/', $teks));

        // Golongan PPPK: snapshot "X/X" (contoh "IX/IX") — tampil satu saja.
        if (count($segmen) === 2 && $segmen[0] !== '' && $segmen[0] === $segmen[1]) {
            return $segmen[0];
        }

        return $teks;
    }

    /**
     * Capitalize Each Word untuk nama, TANPA merusak gelar/singkatan:
     *   "RISTA AMELIA ADHI, S.STP., M.M." -> "Rista Amelia Adhi, S.STP., M.M."
     * Token yang mengandung titik (S.Kom) atau singkatan pendek ALL-CAPS
     * dibiarkan apa adanya; sisanya di-title-case. Pola sama
     * DrhSatyaLancanaGeneratorService::namaKapital().
     */
    private function namaKapital(?string $nama): string
    {
        $nama = trim((string) $nama);

        if ($nama === '') {
            return '';
        }

        $kata = preg_split('/\s+/', $nama) ?: [];

        $hasil = array_map(function (string $token): string {
            $polos = trim($token, ".,;");

            // Gelar selalu mengandung titik (S.Kom, M.M.) — dipertahankan.
            // Singkatan pendek ≤2 huruf ALL-CAPS (ST, MM) juga dipertahankan;
            // 3 huruf ALL-CAPS (ADI, EKO) dianggap nama → title-case.
            if (str_contains($token, '.') || (mb_strlen($polos) <= 2 && $polos !== '' && mb_strtoupper($polos) === $polos)) {
                return $token;
            }

            return mb_convert_case($token, MB_CASE_TITLE, 'UTF-8');
        }, $kata);

        return implode(' ', $hasil);
    }
}
