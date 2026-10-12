<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\SuratPengantar;
use App\Models\Kepegawaian\SuratTugasDasarHukumSetting;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Isi template Word Surat Pengantar (resources/templates/kepegawaian/
 * surat-pengantar.docx) dengan data satu SuratPengantar, lalu opsional convert
 * ke PDF. Satu-satunya tempat TemplateProcessor dipanggil untuk fitur ini.
 *
 * Template memakai placeholder polos "${...}" (bukan MERGEFIELD), dengan 3
 * block yang di-cloneBlock():
 *   - ${dasar_hukum}...${/dasar_hukum}      : baris dasar hukum (bisa kosong)
 *   - ${detail_pegawai}...${/detail_pegawai} : blok detail pegawai (diulang)
 *   - ${tembusan}...${/tembusan}            : baris tembusan (bisa kosong)
 *
 * Semua nilai di-escape XML (htmlspecialchars ENT_XML1) sebelum masuk
 * TemplateProcessor — karakter mentah seperti "&" akan merusak OpenXML dan
 * membuat LibreOffice gagal memuat dokumen.
 *
 * tempat_tanggal (nomor/tanggal naskah) dibiarkan apa adanya dari field
 * tanggal_naskah; kalau kosong, placeholder dibiarkan literal untuk diisi di
 * aplikasi Srikandi.
 */
class SuratPengantarGeneratorService
{
    private const TEMPLATE_PATH = 'templates/kepegawaian/surat-pengantar.docx';

    public function templatePath(): string
    {
        $path = resource_path(self::TEMPLATE_PATH);

        if (! is_file($path)) {
            throw new \RuntimeException("Template Surat Pengantar tidak ditemukan: {$path}");
        }

        return $path;
    }

    /**
     * Direktori sementara khusus fitur ini di bawah storage/app (lihat catatan
     * serupa di SuratTugasGeneratorService tentang alasan tidak memakai
     * /tmp sistem).
     */
    private function tempDir(): string
    {
        $dir = storage_path('app/surat-pengantar-tmp');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Folder bisa saja sudah dibuat proses lain dengan UID berbeda (mis.
        // root lewat `docker exec`/tinker) sehingga php-fpm (www-data) tak bisa
        // menulisnya — tempnam() lalu memunculkan warning yang Laravel ubah
        // jadi ErrorException. Pastikan writable; kalau tidak, pakai /tmp
        // sistem sebagai fallback supaya fitur tetap jalan.
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

    /** Escape nilai agar valid sebagai XML, termasuk ENT_XML1 + quotes. */
    private function esc(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @return string Path file .docx sementara. Pemanggil menghapusnya.
     */
    public function generateDocx(SuratPengantar $surat): string
    {
        $this->configureTempDir();

        $tp = new TemplateProcessor($this->templatePath());

        // --- Block dasar hukum (bisa kosong) ---
        $dasar = $this->dasarHukumLines($surat);
        if ($dasar === []) {
            $tp->cloneBlock('dasar_hukum', 0, true, false, []);
        } else {
            $tp->cloneBlock('dasar_hukum', count($dasar), true, false, array_map(
                fn (string $line): array => ['isi_dasar_hukum' => $this->esc($line)],
                $dasar,
            ));
        }

        // --- Block detail pegawai (satu baris blok per pegawai) ---
        $pegawai = $surat->pegawai;
        if ($pegawai->isEmpty()) {
            throw new \RuntimeException('Surat Pengantar harus punya minimal satu pegawai.');
        }

        $tp->cloneBlock('detail_pegawai', $pegawai->count(), true, false, $pegawai->values()->map(
            fn ($row): array => [
                'nama' => $this->esc($row->nama),
                'nip' => $this->esc($row->nip),
                'pangkat_golongan' => $this->esc($row->pangkat_golongan ?? '-'),
                'jabatan' => $this->esc($row->jabatan ?? '-'),
                'instansi' => $this->esc($row->instansi ?? '-'),
            ],
        )->all());

        // --- Block tembusan (bisa kosong) ---
        $tembusan = $this->tembusanLines($surat);
        if ($tembusan === []) {
            $tp->cloneBlock('tembusan', 0, true, false, []);
        } else {
            $tp->cloneBlock('tembusan', count($tembusan), true, false, array_map(
                fn (string $line): array => ['isi_tembusan' => $this->esc($line)],
                $tembusan,
            ));
        }

        // --- Nilai tunggal ---
        $tp->setValue('nomor_naskah', $this->esc($surat->nomor_naskah ?? ''));
        $tp->setValue('tempat_tanggal', $this->esc($surat->tempat_tanggal ?? ''));
        $tp->setValue('lampiran', $this->esc($surat->lampiran ?? '-'));
        $tp->setValue('perihal', $this->esc($surat->perihal));
        $tp->setValue('tujuan', $this->esc($surat->tujuan));
        $tp->setValue('tujuan_sambungan', $this->esc($surat->tujuan_sambungan ?? ''));
        $tp->setValue('tujuan_kota', $this->esc($surat->tujuan_kota ?? ''));
        $tp->setValue('paragraf_pembuka', $this->esc($surat->paragraf_pembuka ?? ''));
        $tp->setValue('paragraf_penutup', $this->esc($surat->paragraf_penutup ?? ''));
        $tp->setValue('ttd_baris_1', $this->esc($surat->ttd_baris_1 ?? ''));
        $tp->setValue('ttd_baris_2', $this->esc($surat->ttd_baris_2 ?? ''));
        $tp->setValue('ttd_baris_3', $this->esc($surat->ttd_baris_3 ?? ''));
        $tp->setValue('ttd_jabatan', $this->esc($surat->ttd_jabatan ?? ''));
        $tp->setValue('ttd_nama', $this->esc($surat->ttd_nama ?? ''));
        $tp->setValue('ttd_nip', $this->esc($surat->ttd_nip ?? ''));

        $tmp = tempnam($this->tempDir(), 'sp_').'.docx';
        $tp->saveAs($tmp);

        return $tmp;
    }

    /**
     * @return string Path file .pdf sementara. Pemanggil menghapusnya.
     */
    public function generatePdf(SuratPengantar $surat): string
    {
        $this->configureTempDir();

        $docxPath = $this->generateDocx($surat);

        try {
            return $this->convertToPdfViaLibreOffice($docxPath);
        } catch (\Throwable $e) {
            report($e);

            return $this->convertToPdfViaDompdf($docxPath);
        } finally {
            @unlink($docxPath);
        }
    }

    /**
     * Render via LibreOffice headless — lihat catatan lengkap di
     * SuratTugasGeneratorService::convertToPdfViaLibreOffice().
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

            $pdfPath = tempnam($this->tempDir(), 'sp_').'.pdf';
            rename($producedPath, $pdfPath);

            return $pdfPath;
        } finally {
            (new \Symfony\Component\Process\Process(['rm', '-rf', $outDir, $profileDir]))->run();
        }
    }

    /**
     * Fallback kalau LibreOffice tak tersedia — pakai writer PDF bawaan PHPWord.
     */
    private function convertToPdfViaDompdf(string $docxPath): string
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = IOFactory::load($docxPath);

        $pdfPath = tempnam($this->tempDir(), 'sp_').'.pdf';
        IOFactory::createWriter($phpWord, 'PDF')->save($pdfPath);

        return $pdfPath;
    }

    /**
     * Dasar hukum Setting default digabung dasar hukum tambahan per-surat.
     *
     * @return list<string>
     */
    private function dasarHukumLines(SuratPengantar $surat): array
    {
        $default = SuratTugasDasarHukumSetting::query()
            ->orderBy('urutan')
            ->pluck('teks')
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        $tambahan = collect(preg_split('/\r\n|\r|\n/', (string) $surat->dasar_hukum_tambahan))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        return [...$default, ...$tambahan];
    }

    /**
     * Tembusan dirapikan jadi list bernomor di dokumen ("1. ...", "2. ...")
     * — baris sudah dinomori di sini supaya template cukup menampung teks.
     *
     * @return list<string>
     */
    private function tembusanLines(SuratPengantar $surat): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $surat->tembusan_snapshot))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->map(fn (string $t, int $i): string => ($i + 1).'. '.$t)
            ->all();
    }
}
