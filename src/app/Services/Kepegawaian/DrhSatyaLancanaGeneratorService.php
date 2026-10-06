<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\DrhSatyaLancana;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Isi template Word DRH Satya Lancana Karya Satya
 * (resources/templates/kepegawaian/drh-satya-lancana.docx) dengan data satu
 * DrhSatyaLancana, lalu opsional convert ke PDF. Satu-satunya tempat
 * TemplateProcessor dipanggil untuk fitur ini — pola sama
 * SuratTugasGeneratorService (kop surat di-reuse dari template Surat Tugas,
 * lihat progress.md).
 *
 * Semua field di-snapshot di tabel drh_satya_lancana (bukan live-join) —
 * service ini tak query PegawaiProfil lagi saat generate, persis seperti
 * surat_tugas_pegawai supaya dokumen lama tak berubah kalau data pegawai
 * di-update belakangan.
 */
class DrhSatyaLancanaGeneratorService
{
    private const TEMPLATE_PATH = 'templates/kepegawaian/drh-satya-lancana.docx';

    public function templatePath(): string
    {
        $path = resource_path(self::TEMPLATE_PATH);

        if (! is_file($path)) {
            throw new \RuntimeException("Template DRH tidak ditemukan: {$path}");
        }

        return $path;
    }

    /**
     * Direktori sementara khusus, di bawah storage/app milik www-data — sama
     * alasan dengan SuratTugasGeneratorService::tempDir() (PHPWord meng-extract
     * gambar kop ke Settings::getTempDir(); folder bersama /tmp bisa bikin
     * "Permission denied" kalau ownership-nya campur).
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
     * @return string Path file .docx sementara. Pemanggil bertanggung jawab
     *                 menghapusnya setelah selesai dipakai.
     */
    public function generateDocx(DrhSatyaLancana $drh): string
    {
        $this->configureTempDir();

        $tp = new TemplateProcessor($this->templatePath());

        // Tempat, Tanggal Lahir digabung jadi satu nilai ("Bandung, 19 November 1981").
        $tempatTanggalLahir = trim(implode(', ', array_filter([
            $drh->tempat_lahir,
            $drh->tanggal_lahir,
        ])));

        $values = [
            'nama' => $drh->nama,
            'tempat_tanggal_lahir' => $tempatTanggalLahir,
            'nip' => $drh->nip,
            'nip_lama' => $drh->nip_lama ?: '-',
            'pendidikan' => $drh->pendidikan_terakhir ?: '-',
            'pangkat_golongan' => trim(implode(' ', array_filter([
                $drh->pangkat_golongan,
                $drh->tmt_pangkat_golongan ? '(TMT '.$drh->tmt_pangkat_golongan.')' : null,
            ]))) ?: '-',
            'sk_cpns_nomor' => $drh->sk_cpns_nomor ?: '-',
            'sk_cpns_tanggal' => $drh->sk_cpns_tanggal ?: '-',
            'sk_cpns_tmt' => $drh->sk_cpns_tmt ? 'TMT '.$drh->sk_cpns_tmt : '-',
            'sk_jabatan_nomor' => $drh->sk_jabatan_nomor ?: '-',
            'sk_jabatan_tanggal' => $drh->sk_jabatan_tanggal ?: '-',
            'sk_jabatan_tmt' => $drh->sk_jabatan_tmt ? 'TMT '.$drh->sk_jabatan_tmt : '-',
            'jenis_kelamin' => $drh->jenis_kelamin ?: '-',
            'tanda_kehormatan' => $drh->tanda_kehormatan_dimiliki ?: '-',
            'hukuman' => $drh->hukuman_disiplin ?: DrhSatyaLancana::HUKUMAN_DISIPLIN_DEFAULT,
            'cltn' => $drh->cltn ?: DrhSatyaLancana::CLTN_DEFAULT,
            'ditetapkan_di' => $drh->ditetapkan_di ?: 'SEMARANG',
            'tanggal_ditetapkan' => $drh->tanggal_ditetapkan ?: '-',
            'ttd_kiri_nama' => $drh->ttd_kiri_nama ?: DrhSatyaLancana::TTD_KIRI_NAMA_DEFAULT,
            'ttd_kiri_nip' => 'NIP. '.(trim((string) ($drh->ttd_kiri_nip ?: DrhSatyaLancana::TTD_KIRI_NIP_DEFAULT))),
            'ttd_kanan_nama' => $drh->nama,
            'ttd_kanan_nip' => 'NIP. '.$drh->nip,
        ];

        // Jabatan blok TTD kiri dipecah jadi 3 baris (template menyediakan 3
        // placeholder terpisah; teks simpanan bisa berupa beberapa baris).
        $jabatanLines = preg_split('/\r\n|\r|\n/', (string) ($drh->ttd_kiri_jabatan ?: DrhSatyaLancana::TTD_KIRI_JABATAN_DEFAULT));
        $jabatanLines = array_values(array_filter(array_map('trim', $jabatanLines), fn ($l) => $l !== ''));
        for ($i = 1; $i <= 3; $i++) {
            $values['ttd_kiri_jabatan_'.$i] = $jabatanLines[$i - 1] ?? '';
        }

        foreach ($values as $key => $value) {
            $tp->setValue($key, (string) $value);
        }

        $tmp = tempnam($this->tempDir(), 'drh_').'.docx';
        $tp->saveAs($tmp);

        return $tmp;
    }

    /**
     * @return string Path file .pdf sementara. Pemanggil bertanggung jawab
     *                 menghapusnya setelah selesai dipakai.
     */
    public function generatePdf(DrhSatyaLancana $drh): string
    {
        $this->configureTempDir();

        $docxPath = $this->generateDocx($drh);

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
     * Render via LibreOffice headless — renderer office sungguhan, pola sama
     * SuratTugasGeneratorService::convertToPdfViaLibreOffice() (termasuk
     * -env:UserInstallation unik per panggilan supaya request concurrent tak
     * saling kunci).
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

            $pdfPath = tempnam($this->tempDir(), 'drh_').'.pdf';
            rename($producedPath, $pdfPath);

            return $pdfPath;
        } finally {
            (new \Symfony\Component\Process\Process(['rm', '-rf', $outDir, $profileDir]))->run();
        }
    }

    /**
     * Fallback kalau LibreOffice tak tersedia — writer PDF bawaan PHPWord
     * (dompdf), pola sama SuratTugasGeneratorService.
     */
    private function convertToPdfViaDompdf(string $docxPath): string
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = IOFactory::load($docxPath);

        $pdfPath = tempnam($this->tempDir(), 'drh_').'.pdf';
        IOFactory::createWriter($phpWord, 'PDF')->save($pdfPath);

        return $pdfPath;
    }
}
