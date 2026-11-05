<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\Notulen;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Shared\XMLWriter;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\Word2007\Element\Container;
use Symfony\Component\Process\Process;

/**
 * Service untuk mengisi template Word Notulen
 * (resources/templates/kepegawaian/notulen.docx) dan convert ke PDF.
 */
class NotulenGeneratorService
{
    private const TEMPLATE_PATH = 'templates/kepegawaian/notulen.docx';

    public function templatePath(): string
    {
        $path = resource_path(self::TEMPLATE_PATH);

        if (! is_file($path)) {
            throw new \RuntimeException("Template Notulen tidak ditemukan: {$path}");
        }

        return $path;
    }

    private function tempDir(): string
    {
        $dir = storage_path('app/notulen-tmp');

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
     * @return string Path file .docx sementara hasil isi template.
     */
    public function generateDocx(Notulen $notulen): string
    {
        $this->configureTempDir();

        $tp = new TemplateProcessor($this->templatePath());

        // Judul Kegiatan
        $tp->setValue('judul', $this->xmlEscape($notulen->judul));

        $secNo = 1;

        // Dasar
        $dasarLines = collect(preg_split('/\r\n|\r|\n/', (string) $notulen->dasar))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        if ($dasarLines === []) {
            $tp->cloneBlock('dasar_hdr_block', 0, true, false, []);
            $tp->cloneBlock('dasar_block', 0, true, false, []);
        } else {
            $tp->cloneBlock('dasar_hdr_block', 1, true, false, [[]]);
            $tp->setValue('dasar_hdr', $this->xmlEscape("{$secNo}. Dasar"));
            $secNo++;

            $multiple = count($dasarLines) > 1;
            $formattedDasar = [];
            foreach ($dasarLines as $i => $line) {
                if ($multiple && ! preg_match('/^[a-z0-9][\.\)]\s/i', $line)) {
                    $prefix = chr(97 + ($i % 26)).'. ';
                    $formattedDasar[] = $prefix.$line;
                } else {
                    $formattedDasar[] = $line;
                }
            }

            $tp->cloneBlock('dasar_block', count($formattedDasar), true, false, array_map(
                fn (string $line): array => ['dasar_item' => $this->xmlEscape($line)],
                $formattedDasar
            ));
        }

        // Waktu dan Tempat Pelaksanaan
        $tp->setValue('waktu_hdr', $this->xmlEscape("{$secNo}. Waktu dan Tempat Pelaksanaan"));
        $secNo++;
        $tp->setValue('hari_tanggal', $this->xmlEscape($notulen->hari_tanggal));
        $tp->setValue('waktu', $this->xmlEscape($notulen->waktu));
        $tp->setValue('tempat', $this->xmlEscape($notulen->tempat));

        // Narasumber
        $narasumberLines = collect(preg_split('/\r\n|\r|\n/', (string) $notulen->narasumber))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        if ($narasumberLines === []) {
            $tp->cloneBlock('narasumber_hdr_block', 0, true, false, []);
            $tp->cloneBlock('narasumber_block', 0, true, false, []);
        } else {
            $tp->cloneBlock('narasumber_hdr_block', 1, true, false, [[]]);
            $tp->setValue('narasumber_hdr', $this->xmlEscape("{$secNo}. Narasumber :"));
            $secNo++;

            $formattedNara = [];
            foreach ($narasumberLines as $i => $line) {
                if (! preg_match('/^\d+[\.\)]\s/', $line)) {
                    $prefix = ($i + 1).'. ';
                    $formattedNara[] = $prefix.$line;
                } else {
                    $formattedNara[] = $line;
                }
            }

            $tp->cloneBlock('narasumber_block', count($formattedNara), true, false, array_map(
                fn (string $line): array => ['narasumber_item' => $this->xmlEscape($line)],
                $formattedNara
            ));
        }

        // Peserta - Deskripsi & Daftar
        $pesertaLines = collect(preg_split('/\r\n|\r|\n/', (string) $notulen->peserta_daftar))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();

        $hasDeskripsi = filled($notulen->peserta_deskripsi);
        $hasDaftar = $pesertaLines !== [];

        if (! $hasDeskripsi && ! $hasDaftar) {
            $tp->cloneBlock('peserta_hdr_block', 0, true, false, []);
            $tp->cloneBlock('peserta_deskripsi_block', 0, true, false, []);
            $tp->cloneBlock('peserta_daftar_block', 0, true, false, []);
        } else {
            $tp->cloneBlock('peserta_hdr_block', 1, true, false, [[]]);
            $tp->setValue('peserta_hdr', $this->xmlEscape("{$secNo}. Peserta :"));
            $secNo++;

            if ($hasDeskripsi) {
                $tp->cloneBlock('peserta_deskripsi_block', 1, true, false, [
                    ['peserta_deskripsi' => $this->xmlEscape($notulen->peserta_deskripsi)],
                ]);
            } else {
                $tp->cloneBlock('peserta_deskripsi_block', 0, true, false, []);
            }

            if ($hasDaftar) {
                $formattedPeserta = [];
                foreach ($pesertaLines as $i => $line) {
                    if (! preg_match('/^\d+[\.\)]\s/', $line) && ! preg_match('/^[a-z][\.\)]\s/i', $line)) {
                        $prefix = ($i + 1).'. ';
                        $formattedPeserta[] = $prefix.$line;
                    } else {
                        $formattedPeserta[] = $line;
                    }
                }

                $tp->cloneBlock('peserta_daftar_block', count($formattedPeserta), true, false, array_map(
                    fn (string $line): array => ['peserta_item' => $this->xmlEscape($line)],
                    $formattedPeserta
                ));
            } else {
                $tp->cloneBlock('peserta_daftar_block', 0, true, false, []);
            }
        }

        // Hasil Acara (mendukung format WYSIWYG / HTML dan teks polos)
        $tp->setValue('hasil_hdr', $this->xmlEscape("{$secNo}. Hasil Acara :"));
        $this->injectHasilAcara($tp, (string) $notulen->hasil_acara);

        // Penutup (tanpa penomoran list)
        $penutup = filled($notulen->penutup)
            ? (string) $notulen->penutup
            : "Demikian Notulen {$notulen->judul} untuk menjadikan periksa.";

        $tp->setValue('penutup', $this->xmlEscape($penutup));

        // Tanggal Naskah
        $tanggalNaskah = filled($notulen->tanggal_naskah)
            ? (string) $notulen->tanggal_naskah
            : 'Semarang, '.Carbon::now()->locale('id')->translatedFormat('j F Y');
        $tp->setValue('tanggal_naskah', $this->xmlEscape($tanggalNaskah));

        // Penandatangan Atasan (Mengetahui)
        $atasanJabatan = (string) ($notulen->atasan_jabatan ?: '-');
        $atasanNama = (string) ($notulen->atasan_nama ?: '-');
        $atasanNip = filled($notulen->atasan_nip)
            ? (str_starts_with((string) $notulen->atasan_nip, 'NIP') ? (string) $notulen->atasan_nip : 'NIP. '.(string) $notulen->atasan_nip)
            : '-';

        $tp->setValue('atasan_jabatan', $this->xmlEscape($atasanJabatan));
        $tp->setValue('atasan_nama', $this->xmlEscape($atasanNama));
        $tp->setValue('atasan_nip', $this->xmlEscape($atasanNip));

        // Penandatangan Yang Melaporkan
        $pelaporJabatan = (string) ($notulen->pelapor_jabatan ?: '-');
        $pelaporNama = (string) ($notulen->pelapor_nama ?: '-');
        $pelaporNip = filled($notulen->pelapor_nip)
            ? (str_starts_with((string) $notulen->pelapor_nip, 'NIP') ? (string) $notulen->pelapor_nip : 'NIP. '.(string) $notulen->pelapor_nip)
            : '-';

        $tp->setValue('pelapor_jabatan', $this->xmlEscape($pelaporJabatan));
        $tp->setValue('pelapor_nama', $this->xmlEscape($pelaporNama));
        $tp->setValue('pelapor_nip', $this->xmlEscape($pelaporNip));

        $this->collapseEmptyParagraphs($tp);

        $tmp = tempnam($this->tempDir(), 'notulen_').'.docx';
        $tp->saveAs($tmp);

        $this->injectGambarLampiran($tmp, (array) ($notulen->gambar ?? []));

        return $tmp;
    }

    /**
     * Sisipkan lampiran gambar sebagai tabel 2 kolom (satu gambar per sel)
     * tepat di atas blok tanda tangan. Marker `${gambar_block}` pada template
     * diganti tabel OpenXML; bila tidak ada gambar, paragraf marker dibuang.
     *
     * @param  list<string>  $paths  Path relatif di disk `arsip`.
     */
    private function injectGambarLampiran(string $docxPath, array $paths): void
    {
        $zip = new \ZipArchive;

        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        $types = $zip->getFromName('[Content_Types].xml');

        if ($xml === false || $rels === false || $types === false) {
            $zip->close();

            return;
        }

        $maxRid = 0;
        if (preg_match_all('/Id="rId(\d+)"/', $rels, $matches)) {
            $maxRid = max(array_map('intval', $matches[1]));
        }

        $images = [];
        $docPrId = 900;

        foreach (array_values(array_filter(array_map('strval', $paths))) as $relative) {
            $abs = $this->arsipAbsolutePath($relative);

            if ($abs === null) {
                continue;
            }

            $info = @getimagesize($abs);

            if ($info === false) {
                continue;
            }

            $ext = match ($info[2]) {
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_JPEG => 'jpeg',
                IMAGETYPE_GIF => 'gif',
                IMAGETYPE_WEBP => 'webp',
                default => null,
            };

            if ($ext === null) {
                continue;
            }

            $name = 'notulen_'.substr(sha1($relative.'|'.count($images)), 0, 12).'.'.$ext;
            $zip->addFromString('word/media/'.$name, (string) file_get_contents($abs));

            $rid = 'rId'.(++$maxRid);
            $rels = str_replace(
                '</Relationships>',
                '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$name.'"/></Relationships>',
                $rels
            );

            $mime = match ($ext) {
                'png' => 'image/png',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                default => 'image/webp',
            };

            if (! preg_match('/Extension="'.preg_quote($ext, '/').'"/', $types)) {
                $types = str_replace('</Types>', '<Default Extension="'.$ext.'" ContentType="'.$mime.'"/></Types>', $types);
            }

            [$cx, $cy] = $this->gambarExtent((int) $info[0], (int) $info[1]);

            $images[] = ['rid' => $rid, 'cx' => $cx, 'cy' => $cy, 'id' => ++$docPrId];
        }

        $tabel = $images === [] ? '' : $this->gambarTabelXml($images);

        $xml = preg_replace('~<w:p\b(?:(?!<w:p\b).)*?\$\{gambar_block\}.*?</w:p>~s', $tabel, $xml, 1);

        $zip->addFromString('word/document.xml', (string) $xml);
        $zip->addFromString('word/_rels/document.xml.rels', $rels);
        $zip->addFromString('[Content_Types].xml', $types);
        $zip->close();
    }

    private function arsipAbsolutePath(string $relative): ?string
    {
        if ($relative === '') {
            return null;
        }

        if (str_starts_with($relative, '/')) {
            return is_file($relative) ? $relative : null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('arsip');

        return $disk->exists($relative) ? $disk->path($relative) : null;
    }

    /**
     * Ukuran gambar dalam EMU, dibatasi agar muat satu sel tabel 2 kolom.
     *
     * @return array{0: int, 1: int}
     */
    private function gambarExtent(int $widthPx, int $heightPx): array
    {
        $emuPerPixel = 914400 / 96;
        $maxCx = (int) (3.1 * 914400);
        $maxCy = (int) (2.3 * 914400);

        $cx = max(1, (int) round($widthPx * $emuPerPixel));
        $cy = max(1, (int) round($heightPx * $emuPerPixel));

        $scale = min(1, $maxCx / $cx, $maxCy / $cy);

        return [(int) round($cx * $scale), (int) round($cy * $scale)];
    }

    /**
     * @param  list<array{rid: string, cx: int, cy: int, id: int}>  $images
     */
    private function gambarTabelXml(array $images): string
    {
        $rows = '';

        foreach (array_chunk($images, 2) as $chunk) {
            $cells = '';

            foreach ($chunk as $index => $image) {
                $width = $index === 0 ? 4961 : 4962;
                $cells .= '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'
                    .'<w:p><w:pPr><w:spacing w:line="240" w:lineRule="auto"/><w:jc w:val="center"/></w:pPr>'
                    .'<w:r>'.$this->gambarDrawingXml($image).'</w:r></w:p></w:tc>';
            }

            if (count($chunk) === 1) {
                $cells .= '<w:tc><w:tcPr><w:tcW w:w="4962" w:type="dxa"/></w:tcPr><w:p/></w:tc>';
            }

            $rows .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>'.$cells.'</w:tr>';
        }

        $tabel = '<w:tbl><w:tblPr><w:tblW w:w="9923" w:type="dxa"/><w:jc w:val="center"/></w:tblPr>'
            .'<w:tblGrid><w:gridCol w:w="4961"/><w:gridCol w:w="4962"/></w:tblGrid>'
            .$rows.'</w:tbl>';

        $judul = '<w:p><w:pPr><w:spacing w:before="240" w:after="120" w:line="240" w:lineRule="auto"/></w:pPr>'
            .'<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:bCs/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>'
            .'<w:t xml:space="preserve">Dokumentasi</w:t></w:r></w:p>';

        return $judul.$tabel;
    }

    /**
     * @param  array{rid: string, cx: int, cy: int, id: int}  $image
     */
    private function gambarDrawingXml(array $image): string
    {
        $rid = htmlspecialchars($image['rid'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cx = $image['cx'];
        $cy = $image['cy'];
        $id = $image['id'];

        return '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
            .'<wp:docPr id="'.$id.'" name="Gambar '.$id.'"/>'
            .'<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:nvPicPr><pic:cNvPr id="'.$id.'" name="Gambar '.$id.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm>'
            .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';
    }

    /**
     * Meredam paragraf kosong yang menumpuk (mis. sisa spacer dari section
     * Dasar/Narasumber/Peserta yang dikosongkan) agar jarak antar bagian naskah
     * tidak melebar. Hanya paragraf kosong BERURUTAN yang diringkas menjadi satu,
     * sehingga spacer tunggal (termasuk di dalam sel tabel tanda tangan) tetap utuh.
     */
    private function collapseEmptyParagraphs(TemplateProcessor $tp): void
    {
        $ref = new \ReflectionProperty(TemplateProcessor::class, 'tempDocumentMainPart');
        $ref->setAccessible(true);
        $mainPart = (string) $ref->getValue($tp);

        $emptyPara = '<w:p(?: [^>]*)?><w:pPr>(?:(?!</w:pPr>).)*?</w:pPr></w:p>';

        // Ulangi sampai tidak ada lagi dua paragraf kosong berdampingan.
        $previous = null;
        while ($previous !== $mainPart) {
            $previous = $mainPart;
            $mainPart = preg_replace(
                '~('.$emptyPara.')\s*(?='.$emptyPara.')~s',
                '',
                $mainPart
            );
        }

        $ref->setValue($tp, $mainPart);
    }

    /**
     * @param  string|null  $docxPath  Render PDF dari dokumen ini bila diberikan
     *                                 (mis. snapshot hasil koreksi editor Word);
     *                                 null = generate baru dari data notulen.
     * @return string Path file .pdf sementara hasil convert.
     */
    public function generatePdf(Notulen $notulen, ?string $docxPath = null): string
    {
        $this->configureTempDir();

        $sementara = $docxPath === null;
        $docxPath ??= $this->generateDocx($notulen);

        try {
            return $this->convertToPdfViaLibreOffice($docxPath);
        } catch (\Throwable $e) {
            report($e);

            return $this->convertToPdfViaDompdf($docxPath);
        } finally {
            if ($sementara) {
                @unlink($docxPath);
            }
        }
    }

    private function convertToPdfViaLibreOffice(string $docxPath): string
    {
        $outDir = $this->tempDir().'/lo-out-'.bin2hex(random_bytes(8));
        $profileDir = $this->tempDir().'/lo-profile-'.bin2hex(random_bytes(8));
        mkdir($outDir, 0775, true);
        mkdir($profileDir, 0775, true);

        try {
            $process = new Process([
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

            $pdfPath = tempnam($this->tempDir(), 'notulen_').'.pdf';
            rename($producedPath, $pdfPath);

            return $pdfPath;
        } finally {
            (new Process(['rm', '-rf', $outDir, $profileDir]))->run();
        }
    }

    private function convertToPdfViaDompdf(string $docxPath): string
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = IOFactory::load($docxPath);

        $pdfPath = tempnam($this->tempDir(), 'notulen_').'.pdf';
        IOFactory::createWriter($phpWord, 'PDF')->save($pdfPath);

        return $pdfPath;
    }

    /**
     * Menyuntikkan konten Hasil Acara (yang berasal dari RichEditor WYSIWYG)
     * ke dalam template Word sebagai elemen OpenXML berformat naskah dinas
     * resmi (Arial 11pt, spasi 1.25, dan perataan justified).
     */
    private function injectHasilAcara(TemplateProcessor $tp, string $raw): void
    {
        $raw = trim($raw);
        if ($raw === '') {
            $raw = '<p>-</p>';
        }

        // Jika bukan tag HTML (misal teks polos), bungkus paragraf ke <p>
        if (! str_contains($raw, '<p>') && ! str_contains($raw, '<div>') && ! str_contains($raw, '<ul') && ! str_contains($raw, '<ol')) {
            $paragraphs = collect(preg_split('/\r\n\s*\r\n|\n\s*\n/', $raw))
                ->map(fn (string $t): string => '<p>'.nl2br(e(trim($t))).'</p>')
                ->implode('');
            $raw = $paragraphs ?: '<p>-</p>';
        }

        // RichEditor kerap menyisakan paragraf kosong di akhir (mis. "...</ol><p></p>")
        // sehingga menambah satu baris kosong berlebih di bawah isi Hasil Acara.
        $raw = $this->stripTrailingEmptyBlocks($raw);

        // Normalisasi list HTML agar numbered list 1, 2, 3... dan unordered list terformat pasti
        $raw = $this->normalizeHtmlLists($raw);

        $openXml = $this->htmlToOpenXml($raw);

        $ref = new \ReflectionProperty(TemplateProcessor::class, 'tempDocumentMainPart');
        $ref->setAccessible(true);
        $mainPart = $ref->getValue($tp);

        $pattern = '/(<w:p\b(?:(?!<w:p\b).)*?\\$\\{hasil_acara_block\\}<\/w:.*?p>)(.*?)(<w:p\b(?:(?!<w:p\b).)*?\\$\\{\\/hasil_acara_block\\}<\/w:.*?p>)/is';

        if (preg_match($pattern, $mainPart)) {
            $mainPart = preg_replace($pattern, $openXml, $mainPart);
            $ref->setValue($tp, $mainPart);
        } else {
            $tp->setValue('hasil_acara_item', strip_tags($raw));
        }
    }

    /**
     * Membuang blok/paragraf kosong di akhir konten (mis. "<p></p>", "<p><br></p>",
     * atau "<div></div>") yang tidak membawa isi apa pun.
     */
    private function stripTrailingEmptyBlocks(string $html): string
    {
        $emptyBlock = '<(p|div)\b[^>]*>(?:\s|&nbsp;|<br\s*/?>|<\/?span[^>]*>)*<\/\1>';

        do {
            $previous = $html;
            $html = preg_replace('~\s*'.$emptyBlock.'\s*$~i', '', $html);
        } while ($html !== $previous);

        return trim($html);
    }

    /**
     * Menormalisasi tag <ol> dan <ul> dari WYSIWYG RichEditor menjadi paragraf
     * bernomor eksplisit (1. 2. 3. dst) dan berpeluru (• dst) sehingga tidak
     * terdistorsi oleh mapping numId pada word/numbering.xml.
     */
    private function normalizeHtmlLists(string $html): string
    {
        if (! str_contains($html, '<ol') && ! str_contains($html, '<ul')) {
            return $html;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $processList = function (\DOMElement $listNode, int $level = 0) use (&$processList, $dom) {
            $isOrdered = strtolower($listNode->nodeName) === 'ol';
            $index = 1;
            if ($isOrdered && $listNode->hasAttribute('start')) {
                $index = (int) $listNode->getAttribute('start');
            }

            $frag = $dom->createDocumentFragment();

            foreach (iterator_to_array($listNode->childNodes) as $child) {
                if ($child instanceof \DOMElement && strtolower($child->nodeName) === 'li') {
                    $nestedLists = [];
                    foreach (iterator_to_array($child->childNodes) as $subChild) {
                        if ($subChild instanceof \DOMElement && in_array(strtolower($subChild->nodeName), ['ol', 'ul'], true)) {
                            $nestedLists[] = $subChild;
                            $child->removeChild($subChild);
                        }
                    }

                    $liContent = '';
                    foreach ($child->childNodes as $cn) {
                        $liContent .= $dom->saveHTML($cn);
                    }
                    $liContent = trim($liContent);
                    if (preg_match('/^<p(?:\s+[^>]*)?>(.*?)<\/p>$/is', $liContent, $pm)) {
                        $liContent = $pm[1];
                    }

                    if ($isOrdered) {
                        $prefix = match ($level % 4) {
                            0 => "{$index}. ",
                            1 => chr(96 + (($index - 1) % 26 + 1)).'. ',
                            2 => "{$index}) ",
                            default => chr(96 + (($index - 1) % 26 + 1)).') ',
                        };
                        $index++;
                    } else {
                        $prefix = match ($level % 4) {
                            0 => '• ',
                            1 => '◦ ',
                            2 => '▪ ',
                            default => '‣ ',
                        };
                    }

                    $pNode = $dom->createElement('p');

                    $subDoc = new \DOMDocument;
                    libxml_use_internal_errors(true);
                    $subDoc->loadHTML('<?xml encoding="utf-8" ?><div>'.htmlspecialchars($prefix, ENT_QUOTES | ENT_XML1, 'UTF-8').$liContent.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    libxml_clear_errors();

                    $imported = $dom->importNode($subDoc->documentElement, true);
                    while ($imported->hasChildNodes()) {
                        $pNode->appendChild($imported->removeChild($imported->firstChild));
                    }

                    $frag->appendChild($pNode);

                    foreach ($nestedLists as $nl) {
                        $subFrag = $processList($nl, $level + 1);
                        $frag->appendChild($subFrag);
                    }
                }
            }

            return $frag;
        };

        $xpath = new \DOMXPath($dom);
        $topLists = $xpath->query('//ol[not(ancestor::ol or ancestor::ul)] | //ul[not(ancestor::ol or ancestor::ul)]');
        foreach ($topLists as $tl) {
            if ($tl instanceof \DOMElement) {
                $frag = $processList($tl, 0);
                $tl->parentNode->replaceChild($frag, $tl);
            }
        }

        $out = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    /**
     * Menentukan kedalaman (level) item list dari prefix teks yang dihasilkan
     * normalizeHtmlLists(). Mengembalikan null bila baris bukan item list.
     *
     * Ordered (level % 4): 0 => "1. ", 1 => "a. ", 2 => "1) ", 3 => "a) "
     * Unordered (level % 4): 0 => "• ", 1 => "◦ ", 2 => "▪ ", 3 => "‣ "
     */
    private function detectListLevel(string $plain): ?int
    {
        if (preg_match('/^\d+\.\s/', $plain)) {
            return 0;
        }

        if (preg_match('/^[a-z]\.\s/i', $plain)) {
            return 1;
        }

        if (preg_match('/^\d+\)\s/', $plain)) {
            return 2;
        }

        if (preg_match('/^[a-z]\)\s/i', $plain)) {
            return 3;
        }

        if (preg_match('/^•\s/u', $plain)) {
            return 0;
        }

        if (preg_match('/^◦\s/u', $plain)) {
            return 1;
        }

        if (preg_match('/^▪\s/u', $plain)) {
            return 2;
        }

        if (preg_match('/^‣\s/u', $plain)) {
            return 3;
        }

        return null;
    }

    private function htmlToOpenXml(string $html): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection();
        Html::addHtml($section, $html);

        $xmlWriter = new XMLWriter;
        $writer = new Container($xmlWriter, $section);
        $writer->write();
        $xml = $xmlWriter->getData();

        // Format paragraf dan teks hasil konversi ke standar naskah dinas: Arial 11pt, spasi 1.25, rata kanan-kiri
        return preg_replace_callback('/<w:p(?:\s+[^>]*)?>(.*?)<\/w:p>/s', function (array $m): string {
            $content = $m[1];

            // Hapus <w:numPr> bawaan agar tidak tertukar menjadi unordered bullet list
            $content = preg_replace('/<w:numPr>.*?<\/w:numPr>/s', '', $content);

            $plain = trim(strip_tags($content));

            // Tentukan kedalaman item list dari pola prefix yang dihasilkan normalizeHtmlLists():
            // ordered (level%4): 0:"N. " 1:"a. " 2:"N) " 3:"a) " ; unordered (level%4): 0:"• " 1:"◦ " 2:"▪ " 3:"‣ "
            $level = $this->detectListLevel($plain);
            $isListItem = $level !== null;
            $isHeading = (bool) preg_match('/<w:pStyle w:val="Heading\d"/', $content);

            if ($isListItem) {
                $left = 720 + ($level * 360);
                $indentXml = sprintf('<w:ind w:left="%d" w:hanging="360"/>', $left);
            } elseif ($isHeading) {
                // Sub-judul (h2/h3 dari WYSIWYG) tetap rata kiri, tanpa tab indent
                $indentXml = '';
            } else {
                // Paragraf child (non-heading, non-list) di bawah heading Hasil Acara diberi 1 tab indent
                $indentXml = '<w:ind w:left="720"/>';
            }

            if (preg_match('/<w:pPr\b[^>]*>(.*?)<\/w:pPr>/s', $content, $pm)) {
                $inner = $pm[1];
                if (! str_contains($inner, '<w:jc')) {
                    $inner .= '<w:jc w:val="both"/>';
                }
                if (! str_contains($inner, '<w:spacing')) {
                    $inner .= '<w:spacing w:line="360" w:lineRule="auto"/>';
                }
                if ($indentXml !== '' && ! str_contains($inner, '<w:ind')) {
                    $inner .= $indentXml;
                }
                $content = str_replace($pm[0], '<w:pPr>'.$inner.'</w:pPr>', $content);
            } elseif (preg_match('/<w:pPr\s*\/>/s', $content, $pm)) {
                $content = str_replace($pm[0], '<w:pPr><w:spacing w:line="360" w:lineRule="auto"/><w:jc w:val="both"/>'.$indentXml.'</w:pPr>', $content);
            } else {
                $content = '<w:pPr><w:spacing w:line="360" w:lineRule="auto"/><w:jc w:val="both"/>'.$indentXml.'</w:pPr>'.$content;
            }

            $content = preg_replace_callback('/<w:r\b([^>]*)>(.*?)<\/w:r>/s', function (array $rm): string {
                $rAttrs = $rm[1];
                $rContent = $rm[2];
                if (preg_match('/<w:rPr\b[^>]*>(.*?)<\/w:rPr>/s', $rContent, $rpm)) {
                    $rInner = $rpm[1];
                    if (! str_contains($rInner, '<w:rFonts')) {
                        $rInner = '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>'.$rInner;
                    }
                    if (! str_contains($rInner, '<w:sz')) {
                        $rInner .= '<w:sz w:val="22"/><w:szCs w:val="22"/>';
                    }

                    return '<w:r'.$rAttrs.'>'.str_replace($rpm[0], '<w:rPr>'.$rInner.'</w:rPr>', $rContent).'</w:r>';
                } elseif (preg_match('/<w:rPr\s*\/>/s', $rContent, $rpm)) {
                    return '<w:r'.$rAttrs.'>'.str_replace($rpm[0], '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>', $rContent).'</w:r>';
                } else {
                    return '<w:r'.$rAttrs.'><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>'.$rContent.'</w:r>';
                }
            }, $content);

            return '<w:p>'.$content.'</w:p>';
        }, $xml);
    }

    private function xmlEscape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
