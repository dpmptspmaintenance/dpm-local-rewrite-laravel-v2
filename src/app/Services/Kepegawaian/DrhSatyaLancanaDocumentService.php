<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\DrhSatyaLancanaDokumen;
use iio\libmergepdf\Driver\Fpdi2Driver;
use iio\libmergepdf\Merger;

/**
 * Simpan & gabung berkas lampiran DRH (a–e). Semua berkas disimpan lokal di
 * storage/app/drh-satya-lancana/{id}/{jenis}.{ext} (bukan Google Drive — per
 * keputusan user). Penggabungan jadi 1 PDF: file PDF dipakai apa adanya,
 * gambar (jpg/png) dikonversi dulu ke PDF 1 halaman lewat dompdf (yang sudah
 * dipakai app ini), lalu semua digabung berurutan a→e dengan iio/libmergepdf
 * (driver FPDI).
 *
 * Satu-satunya tempat Merger/dompdf dipanggil untuk fitur ini.
 */
class DrhSatyaLancanaDocumentService
{
    /** Urutan resmi lampiran sesuai surat usulan. */
    public const URUTAN_JENIS = ['a', 'b', 'c', 'd', 'e'];

    private function baseDir(): string
    {
        $dir = storage_path('app/drh-satya-lancana');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function drhDir(DrhSatyaLancana $drh): string
    {
        $dir = $this->baseDir().'/'.$drh->getKey();

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    public function absolutePath(DrhSatyaLancanaDokumen $dokumen): string
    {
        return storage_path('app/'.ltrim($dokumen->path, '/'));
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
        $relative = 'drh-satya-lancana/'.$drh->getKey().'/'.$jenis.'.'.$ext;
        $target = storage_path('app/'.$relative);

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }

        // Hapus berkas lama jenis ini (ekstensi bisa berbeda).
        $this->deleteFilesForJenis($drh, $jenis);

        copy($tmpPath, $target);

        return DrhSatyaLancanaDokumen::updateOrCreate(
            ['drh_satya_lancana_id' => $drh->getKey(), 'jenis' => $jenis],
            [
                'path' => $relative,
                'original_filename' => $originalFilename,
                'mime_type' => $mime,
                'file_size' => is_file($target) ? filesize($target) : null,
            ],
        );
    }

    public function delete(DrhSatyaLancanaDokumen $dokumen): void
    {
        $path = $this->absolutePath($dokumen);
        if (is_file($path)) {
            @unlink($path);
        }

        $dokumen->delete();
    }

    private function deleteFilesForJenis(DrhSatyaLancana $drh, string $jenis): void
    {
        foreach (glob($this->drhDir($drh).'/'.$jenis.'.*') ?: [] as $f) {
            @unlink($f);
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
                $merger->addFile($path);
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
