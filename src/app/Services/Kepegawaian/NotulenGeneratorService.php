<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\Notulen;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

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
                    $prefix = chr(97 + ($i % 26)) . '. ';
                    $formattedDasar[] = $prefix . $line;
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
                    $prefix = ($i + 1) . '. ';
                    $formattedNara[] = $prefix . $line;
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
                        $prefix = ($i + 1) . '. ';
                        $formattedPeserta[] = $prefix . $line;
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
        $secNo++;
        $this->injectHasilAcara($tp, (string) $notulen->hasil_acara);

        // Penutup
        $penutup = filled($notulen->penutup)
            ? (string) $notulen->penutup
            : "Demikian Notulen {$notulen->judul} untuk menjadikan periksa.";

        if (! preg_match('/^\d+[\.\)]\s/', $penutup)) {
            $penutup = "{$secNo}. {$penutup}";
        }
        $tp->setValue('penutup', $this->xmlEscape($penutup));

        // Tanggal Naskah
        $tanggalNaskah = filled($notulen->tanggal_naskah)
            ? (string) $notulen->tanggal_naskah
            : 'Semarang, '.\Illuminate\Support\Carbon::now()->locale('id')->translatedFormat('j F Y');
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

        $tmp = tempnam($this->tempDir(), 'notulen_').'.docx';
        $tp->saveAs($tmp);

        return $tmp;
    }

    /**
     * @return string Path file .pdf sementara hasil convert.
     */
    public function generatePdf(Notulen $notulen): string
    {
        $this->configureTempDir();

        $docxPath = $this->generateDocx($notulen);

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
                throw new \RuntimeException("LibreOffice tidak menghasilkan berkas PDF yang diharapkan: {$producedPath}");
            }

            $pdfPath = tempnam($this->tempDir(), 'notulen_').'.pdf';
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
     * Menormalisasi tag <ol> dan <ul> dari WYSIWYG RichEditor menjadi paragraf
     * bernomor eksplisit (1. 2. 3. dst) dan berpeluru (• dst) sehingga tidak
     * terdistorsi oleh mapping numId pada word/numbering.xml.
     */
    private function normalizeHtmlLists(string $html): string
    {
        if (! str_contains($html, '<ol') && ! str_contains($html, '<ul')) {
            return $html;
        }

        $dom = new \DOMDocument();
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
                        $prefix = match ($level % 3) {
                            0 => "{$index}. ",
                            1 => chr(96 + (($index - 1) % 26 + 1)).'. ',
                            default => "{$index}) ",
                        };
                        $index++;
                    } else {
                        $prefix = match ($level % 2) {
                            0 => '• ',
                            default => '- ',
                        };
                    }

                    $pNode = $dom->createElement('p');

                    $subDoc = new \DOMDocument();
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

    private function htmlToOpenXml(string $html): string
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection();
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);

        $xmlWriter = new \PhpOffice\PhpWord\Shared\XMLWriter();
        $writer = new \PhpOffice\PhpWord\Writer\Word2007\Element\Container($xmlWriter, $section);
        $writer->write();
        $xml = $xmlWriter->getData();

        // Format paragraf dan teks hasil konversi ke standar naskah dinas: Arial 11pt, spasi 1.25, rata kanan-kiri
        return preg_replace_callback('/<w:p(?:\s+[^>]*)?>(.*?)<\/w:p>/s', function (array $m): string {
            $content = $m[1];

            // Hapus <w:numPr> bawaan agar tidak tertukar menjadi unordered bullet list
            $content = preg_replace('/<w:numPr>.*?<\/w:numPr>/s', '', $content);

            $plain = trim(strip_tags($content));
            $isLvl0Numbered = (bool) preg_match('/^\d+[\.\)]\s/', $plain);
            $isLvl1Numbered = (bool) preg_match('/^[a-z][\.\)]\s/i', $plain);
            $isBullet = (bool) preg_match('/^[•\-\*]\s/', $plain);

            $indentXml = '';
            if ($isLvl0Numbered || $isBullet) {
                $indentXml = '<w:ind w:left="720" w:hanging="360"/>';
            } elseif ($isLvl1Numbered) {
                $indentXml = '<w:ind w:left="1080" w:hanging="360"/>';
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

