<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\DrhSatyaLancanaDokumen;
use App\Services\ArsipDigital\LocalArsipStorage;
use iio\libmergepdf\Driver\Fpdi2Driver;
use iio\libmergepdf\Merger;
use Symfony\Component\Process\Process;

/**
 * Simpan & gabung berkas lampiran DRH (a–e). Berkas disimpan di disk arsip
 * yang sama dengan modul /arsip, di bawah subfolder "Kepegawaian/drh/{id}/"
 * (bukan storage/app lagi, bukan Google Drive). Penggabungan jadi 1 PDF:
 * file PDF dipakai apa adanya, gambar (jpg/png) dikonversi dulu ke PDF 1
 * halaman lewat dompdf (yang sudah dipakai app ini), lalu semua digabung
 * berurutan a→e dengan iio/libmergepdf (driver FPDI).
 *
 * Satu-satunya tempat Merger/dompdf dipanggil untuk fitur ini.
 */
class DrhSatyaLancanaDocumentService
{
    /** Urutan resmi lampiran sesuai surat usulan. */
    public const URUTAN_JENIS = ['a', 'b', 'c', 'd', 'e'];

    /** Subfolder DRH di dalam disk arsip (ARSIP_LOCAL_ROOT). */
    private const BASE_FOLDER = 'Kepegawaian/drh';

    public function __construct(private readonly LocalArsipStorage $drive) {}

    /** Path subfolder berkas satu DRH di disk arsip (dibuat bila belum ada). */
    private function drhFolder(DrhSatyaLancana $drh): string
    {
        $root = $this->drive->findOrCreateFolder('Kepegawaian', $this->drive->rootFolderId());

        return $this->drive->findOrCreateFolder('drh', $root).'/'.$drh->getKey();
    }

    public function absolutePath(DrhSatyaLancanaDokumen $dokumen): string
    {
        return $this->drive->absolutePath($dokumen->path);
    }

    /**
     * Simpan satu berkas untuk jenis tertentu, ganti yang lama bila ada
     * (unique drh+jenis). Mengembalikan baris dokumen.
     *
     * @param  string  $tmpPath  path file sementara (hasil upload)
     */
    public function store(DrhSatyaLancana $drh, string $jenis, string $tmpPath, string $originalFilename, ?string $mime = null): DrhSatyaLancanaDokumen
    {
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'bin');

        // Hapus berkas lama jenis ini (ekstensi bisa berbeda).
        $this->deleteFilesForJenis($drh, $jenis);

        $uploaded = $this->drive->uploadTo(
            $tmpPath,
            $jenis.'.'.$ext,
            $mime ?: 'application/octet-stream',
            $this->drhFolder($drh),
        );

        return DrhSatyaLancanaDokumen::updateOrCreate(
            ['drh_satya_lancana_id' => $drh->getKey(), 'jenis' => $jenis],
            [
                'path' => $uploaded['id'],
                'original_filename' => $originalFilename,
                'mime_type' => $mime,
                'file_size' => is_file($tmpPath) ? filesize($tmpPath) : null,
            ],
        );
    }

    public function delete(DrhSatyaLancanaDokumen $dokumen): void
    {
        $this->drive->delete((string) $dokumen->path);

        $dokumen->delete();
    }

    private function deleteFilesForJenis(DrhSatyaLancana $drh, string $jenis): void
    {
        foreach ($drh->dokumen()->where('jenis', $jenis)->get() as $old) {
            $this->drive->delete((string) $old->path);
        }
    }

    /**
     * Gabung semua berkas terunggah (urut a→e) jadi satu PDF. Gambar
     * dikonversi ke PDF dulu. Mengembalikan path PDF sementara; pemanggil
     * bertanggung jawab menghapusnya.
     */
    public function merge(DrhSatyaLancana $drh): string
    {
        $dokumen = $drh->dokumen()
            ->get()
            ->keyBy('jenis');

        $merger = new Merger(new Fpdi2Driver);
        $tmpFiles = [];
        $added = 0;

        foreach (self::URUTAN_JENIS as $jenis) {
            $doc = $dokumen->get($jenis);

            if (! $doc) {
                continue;
            }

            $path = $this->absolutePath($doc);

            if (! is_file($path)) {
                continue;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($ext === 'pdf') {
                // FPDI (parser gratis) tak bisa membaca stream CCITTFaxDecode
                // (scan B/W) maupun cross-reference stream PDF 1.5+ yang
                // dihasilkan iLovePDF/Adobe Scan. Normalisasi dulu lewat
                // Ghostscript supaya formatnya didukung sebelum digabung.
                $pdf = $this->normalizePdfIfNeeded($path);

                if ($pdf !== $path) {
                    $tmpFiles[] = $pdf;
                }

                $merger->addFile($pdf);
                $added++;
                continue;
            }

            // Gambar → PDF 1 halaman lewat dompdf.
            $pdf = $this->imageToPdf($path);
            $merger->addFile($pdf);
            $tmpFiles[] = $pdf;
            $added++;
        }

        if ($added === 0) {
            throw new \RuntimeException('Belum ada berkas lampiran yang diunggah — tidak ada yang bisa digabung.');
        }

        $out = tempnam(sys_get_temp_dir(), 'drh_merge_').'.pdf';
        file_put_contents($out, $merger->merge());

        foreach ($tmpFiles as $f) {
            @unlink($f);
        }

        return $out;
    }

    /**
     * Cek apakah PDF memakai fitur yang tak didukung parser gratis FPDI:
     * - filter kompresi gambar tak didukung (CCITTFax/JBIG2/JPX) — umum pada
     *   hasil scan B/W (iLovePDF / Adobe Scan Library);
     * - cross-reference stream (/Type /XRef), dipakai PDF 1.5+ yang disimpan
     *   iLovePDF. FPDI free membaca xref klasik saja.
     * Bila salah satu ada, stream tak bisa diparse saat merge → error
     * "compression technique which is not supported by the free parser".
     */
    private function hasUnsupportedFilter(string $path): bool
    {
        $head = @file_get_contents($path);

        if ($head === false) {
            return false;
        }

        if (preg_match('~/(CCITTFaxDecode|JBIG2Decode|JPXDecode)\b~', $head)) {
            return true;
        }

        return (bool) preg_match('~/Type\s*/XRef\b~', $head);
    }

    /**
     * Normalisasi PDF ber-filter tak didukung jadi PDF standar (FlateDecode)
     * lewat Ghostscript headless. Bila berkas sudah didukung, kembalikan path
     * asli apa adanya. Path hasil ada di temp dan tanggung jawab pemanggil
     * (merge()) untuk menghapusnya.
     */
    private function normalizePdfIfNeeded(string $path): string
    {
        if (! $this->hasUnsupportedFilter($path)) {
            return $path;
        }

        $out = tempnam(sys_get_temp_dir(), 'drh_norm_').'.pdf';

        $process = new Process([
            'gs',
            '-q',
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            // Paksa dekode ulang semua gambar ke Flate/DCT — tanpa ini
            // Ghostscript mempertahankan stream CCITTFaxDecode apa adanya
            // (pass-through) dan FPDI tetap gagal.
            '-dAutoFilterColorImages=false',
            '-dAutoFilterGrayImages=false',
            '-dAutoFilterMonoImages=false',
            '-dColorImageFilter=/FlateEncode',
            '-dGrayImageFilter=/FlateEncode',
            '-dMonoImageFilter=/FlateEncode',
            '-sOutputFile='.$out,
            $path,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($out) || filesize($out) === 0) {
            @unlink($out);

            throw new \RuntimeException(
                'Gagal menormalisasi PDF lampiran (Ghostscript): '.$process->getErrorOutput(),
            );
        }

        return $out;
    }

    /** Konversi gambar (jpg/png/gif/webp) jadi PDF 1 halaman ukuran gambar. */
    private function imageToPdf(string $imagePath): string
    {
        $info = @getimagesize($imagePath);
        if (! $info) {
            throw new \RuntimeException('Berkas lampiran bukan PDF/gambar yang didukung.');
        }

        [$w, $h] = $info;
        $data = base64_encode((string) file_get_contents($imagePath));
        $mime = $info['mime'] ?: 'image/png';

        // Ukuran halaman mengikuti gambar (pt = px * 0.75), margin 0.
        $wPt = $w * 0.75;
        $hPt = $h * 0.75;

        $html = '<html><head><style>@page{margin:0}body{margin:0}img{width:'.$wPt.'pt;height:'.$hPt.'pt}</style></head>'
            .'<body><img src="data:'.$mime.';base64,'.$data.'"></body></html>';

        $dompdf = new \Dompdf\Dompdf(['enable_remote' => false]);
        $dompdf->setPaper([0, 0, $wPt, $hPt]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        $out = tempnam(sys_get_temp_dir(), 'drh_img_').'.pdf';
        file_put_contents($out, $dompdf->output());

        return $out;
    }
}
