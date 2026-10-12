<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\SuratPengantarKp;
use App\Models\Kepegawaian\SuratTugasDasarHukumSetting;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Isi template Word Surat Pengantar Kenaikan Pangkat (resources/templates/
 * kepegawaian/surat-pengantar-kp.docx) dengan data satu SuratPengantarKp, lalu
 * opsional convert ke PDF. Kop surat di-reuse dari template surat-tugas.
 *
 * 2 block di-cloneBlock(): ${dasar_hukum} (bisa kosong) + ${detail_pegawai}
 * (diulang per pegawai). Nilai di-escape XML (ENT_XML1) — karakter mentah
 * seperti "&" merusak OpenXML dan membuat LibreOffice gagal memuat dokumen.
 */
class SuratPengantarKpGeneratorService
{
    private const TEMPLATE_PATH = 'templates/kepegawaian/surat-pengantar-kp.docx';

    public function templatePath(): string
    {
        $path = resource_path(self::TEMPLATE_PATH);

        if (! is_file($path)) {
            throw new \RuntimeException("Template Surat Pengantar KP tidak ditemukan: {$path}");
        }

        return $path;
    }

    private function tempDir(): string
    {
        $dir = storage_path('app/surat-pengantar-kp-tmp');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Folder bisa dibuat proses lain (root) sehingga php-fpm (www-data) tak
        // bisa menulisnya — tempnam() lalu jadi warning/ErrorException.
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

    private function esc(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @return string Path file .docx sementara. Pemanggil menghapusnya.
     */
    public function generateDocx(SuratPengantarKp $surat): string
    {
        $this->configureTempDir();

        $tp = new TemplateProcessor($this->templatePath());

        // Block dasar hukum (bisa kosong).
        $dasar = $this->dasarHukumLines($surat);
        if ($dasar === []) {
            $tp->cloneBlock('dasar_hukum', 0, true, false, []);
        } else {
            $tp->cloneBlock('dasar_hukum', count($dasar), true, false, array_map(
                fn (string $line): array => ['isi_dasar_hukum' => $this->esc($line)],
                $dasar,
            ));
        }

        // Block detail pegawai.
        $pegawai = $surat->pegawai;
        if ($pegawai->isEmpty()) {
            throw new \RuntimeException('Surat Pengantar KP harus punya minimal satu pegawai.');
        }

        $tp->cloneBlock('detail_pegawai', $pegawai->count(), true, false, $pegawai->values()->map(
            fn ($row): array => [
                'nama' => $this->esc($row->nama),
                'nip' => $this->esc($row->nip),
                'tempat_tanggal_lahir' => $this->esc($row->tempat_tanggal_lahir),
                'pangkat_golongan' => $this->esc($row->pangkat_golongan ?? '-'),
                'jabatan' => $this->esc($row->jabatan ?? '-'),
                'instansi' => $this->esc($row->instansi ?? '-'),
            ],
        )->all());

        // Block tembusan (bisa kosong).
        $tembusan = $this->tembusanLines($surat);
        if ($tembusan === []) {
            $tp->cloneBlock('tembusan', 0, true, false, []);
        } else {
            $tp->cloneBlock('tembusan', count($tembusan), true, false, array_map(
                fn (string $line): array => ['tembusan' => $this->esc($line)],
                $tembusan,
            ));
        }

        $tp->setValue('tempat_tanggal', $this->esc($surat->tempat_tanggal ?? ''));
        $tp->setValue('lampiran', $this->esc($surat->lampiran ?? '-'));
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

        $tmp = tempnam($this->tempDir(), 'spkp_').'.docx';
        $tp->saveAs($tmp);

        return $tmp;
    }

    /**
     * @return string Path file .pdf sementara. Pemanggil menghapusnya.
     */
    public function generatePdf(SuratPengantarKp $surat): string
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
                throw new \RuntimeException("LibreOffice tidak menghasilkan berkas PDF: {$producedPath}");
            }

            $pdfPath = tempnam($this->tempDir(), 'spkp_').'.pdf';
            rename($producedPath, $pdfPath);

            return $pdfPath;
        } finally {
            (new \Symfony\Component\Process\Process(['rm', '-rf', $outDir, $profileDir]))->run();
        }
    }

    private function convertToPdfViaDompdf(string $docxPath): string
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = IOFactory::load($docxPath);

        $pdfPath = tempnam($this->tempDir(), 'spkp_').'.pdf';
        IOFactory::createWriter($phpWord, 'PDF')->save($pdfPath);

        return $pdfPath;
    }

    /**
     * @return list<string>
     */
    private function dasarHukumLines(SuratPengantarKp $surat): array
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
     * @return list<string>
     */
    private function tembusanLines(SuratPengantarKp $surat): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $surat->tembusan))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();
    }
}
