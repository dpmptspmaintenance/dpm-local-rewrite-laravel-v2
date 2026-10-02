<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\SuratTugas;
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
            mkdir($dir, 0775, true);
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

        $tp = new TemplateProcessor($this->templatePath());

        $dasarHukum = $this->dasarHukumLines($suratTugas);

        if ($dasarHukum === []) {
            // cloneBlock(0, ..., []) membuang seluruh block tanpa menyisakan
            // paragraf kosong — diuji eksplisit saat menyiapkan template ini.
            $tp->cloneBlock('dasar_hukum', 0, true, false, []);
        } else {
            $tp->cloneBlock('dasar_hukum', count($dasarHukum), true, false, array_map(
                fn (string $line): array => ['isi_dasar_hukum' => $line],
                $dasarHukum,
            ));
        }

        $pegawai = $suratTugas->pegawai;

        if ($pegawai->isEmpty()) {
            throw new \RuntimeException('Surat Tugas harus punya minimal satu pegawai.');
        }

        $tp->cloneRow('no', $pegawai->count());

        foreach ($pegawai->values() as $i => $row) {
            $n = $i + 1;
            $tp->setValue("no#{$n}", (string) $n);
            $tp->setValue("nama#{$n}", (string) $row->nama);
            $tp->setValue("nip#{$n}", (string) $row->nip);
            $tp->setValue("jabatan#{$n}", (string) ($row->jabatan ?? '-'));
            $tp->setValue("pangkat_golongan#{$n}", (string) ($row->pangkat_golongan ?? '-'));
        }

        $tp->setValue('judul', $suratTugas->judul);
        $tp->setValue('hari_tanggal', $suratTugas->hari_tanggal);
        $tp->setValue('waktu', $suratTugas->waktu);
        $tp->setValue('tempat', $suratTugas->tempat);

        // tanggal_naskah: kalau diisi user, pakai nilainya; kalau kosong,
        // biarkan literal "${tanggal_naskah}" (diisi nanti di Srikandi).
        // nomor_naskah & ttd_pengirim SELALU dibiarkan literal — tidak ada
        // jalur untuk mengisinya dari tool ini sama sekali.
        //
        // Dua slot berbeda di template pakai nilai yang sama tapi FORMAT
        // beda: penutup surat (dekat "Kepala,") butuh "Semarang, j F Y"
        // (kota + tanggal, lazim di naskah dinas), sementara baris
        // "Nomor : ... / Tanggal : ..." di lampiran cuma butuh tanggal
        // polos tanpa kota — placeholder template-nya sengaja dibedakan
        // jadi tanggal_naskah vs tanggal_naskah_lampiran supaya tak perlu
        // nebak-nebak strip prefix "Semarang, " dari string tersimpan.
        if (filled($suratTugas->tanggal_naskah)) {
            $tp->setValue('tanggal_naskah', $suratTugas->tanggal_naskah);
            $tp->setValue(
                'tanggal_naskah_lampiran',
                (string) str($suratTugas->tanggal_naskah)->after('Semarang, ')
            );
        }

        $tmp = tempnam($this->tempDir(), 'st_').'.docx';
        $tp->saveAs($tmp);

        return $tmp;
    }

    /**
     * @return string Path file .pdf sementara. Pemanggil bertanggung jawab
     *                 menghapusnya setelah selesai dipakai.
     */
    public function generatePdf(SuratTugas $suratTugas): string
    {
        $this->configureTempDir();

        $docxPath = $this->generateDocx($suratTugas);

        try {
            return $this->convertToPdfViaLibreOffice($docxPath);
        } catch (\Throwable $e) {
            report($e);

            // LibreOffice tak terpasang/gagal (mis. di lingkungan lain yang
            // belum sempat rebuild image) — fallback ke dompdf lewat PHPWord
            // supaya fitur tetap jalan, walau hasilnya kurang presisi.
            return $this->convertToPdfViaDompdf($docxPath);
        } finally {
            @unlink($docxPath);
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
     * Dasar hukum Setting (default, berlaku untuk semua surat) digabung
     * dengan dasar hukum tambahan khusus surat ini (dipisah baris oleh
     * pemanggil sebelum disimpan — lihat SuratTugas::dasar_hukum_tambahan).
     *
     * @return list<string>
     */
    private function dasarHukumLines(SuratTugas $suratTugas): array
    {
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
}
