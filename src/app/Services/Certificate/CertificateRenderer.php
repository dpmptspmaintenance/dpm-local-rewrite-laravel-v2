<?php

namespace App\Services\Certificate;

use App\Models\Certificate\CertificateField;
use App\Models\Certificate\CertificateFont;
use App\Models\Certificate\CertificateTemplate;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Render satu sertifikat (gambar background + field posisi absolut) menjadi
 * PDF lewat Dompdf. Ukuran halaman & posisi field memakai milimeter absolut
 * sehingga hasil PDF identik dengan preview canvas di browser.
 *
 * Teks selalu di-escape (htmlspecialchars) — aman untuk karakter &, <, >,
 * kutip — dan font DejaVu bawaan Dompdf mendukung UTF-8 penuh.
 *
 * Font yang dipakai field dibaca dari master font (`certificate_fonts`).
 * Font non-sistem didaftarkan ke Dompdf lewat @font-face yang menunjuk
 * berkas TTF di storage/app/private/certificate/fonts — Dompdf lalu menulis
 * metrik + salinan font ke fontDir yang sama.
 */
class CertificateRenderer
{
    /** Field harus masih bisa terbaca; pengecilan font berhenti di 50%. */
    private const MIN_FONT_SCALE = 0.5;


    /**
     * @param  array<string, string>  $row  nilai per key kolom Excel
     * @return string isi biner PDF
     */
    public function renderPdf(CertificateTemplate $template, array $row): string
    {
        $html = $this->renderHtml($template, $row);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        // Background template berada di storage privat, di luar folder temp;
        // chroot WAJIB mencakup keduanya agar Dompdf mengizinkan file://
        // (validasi validateLocalUri memeriksa realpath terhadap chroot).
        $options->set('chroot', array_values(array_unique([
            $this->tempDir(),
            storage_path('app/private'),
            $this->fontDir(),
            // Font "bundled:" (resources/fonts/certificate) harus ikut chroot,
            // kalau tidak Dompdf menolak url(file://...) dan diam-diam jatuh
            // ke font AFM bawaan yang TIDAK ter-embed.
            resource_path('fonts'),
        ])));
        $options->set('tempDir', $this->tempDir());
        // Font TTF milik user didaftarkan lewat @font-face; Dompdf menyimpan
        // metrik + salinan font ke fontDir ini (harus writable oleh www-data).
        $options->set('fontDir', $this->fontDir());
        $options->set('fontCache', $this->fontDir());

        $dompdf = new Dompdf($options);
        $dompdf->setPaper([0, 0, $this->mmToPt($template->page_width_mm), $this->mmToPt($template->page_height_mm)]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * HTML halaman sertifikat — dipakai juga oleh preview bila perlu.
     */
    public function renderHtml(CertificateTemplate $template, array $row): string
    {
        $width = $template->page_width_mm;
        $height = $template->page_height_mm;

        $background = $template->backgroundAbsolutePath();
        $backgroundSrc = is_file($background)
            ? 'file://'.str_replace('\\', '/', $background)
            : '';

        $fields = $template->fields;

        $fonts = $this->fontsForFields($fields);

        $blocks = $fields->map(function (CertificateField $field) use ($row, $width, $fonts): string {
            $value = (string) ($row[$field->key] ?? '');
            $value = trim($value);

            [$fontSize, $text] = $this->fitText($value, $field);

            $align = in_array($field->align, ['left', 'center', 'right'], true) ? $field->align : 'center';
            $valign = in_array($field->valign, ['top', 'middle', 'bottom'], true) ? $field->valign : 'middle';
            // Posisi vertikal dihitung manual, BUKAN flex/table-cell: Dompdf
            // mengabaikan `justify-content` pada flex sehingga teks selalu
            // menempel di atas field (itu sebabnya unduhan terlihat "naik"
            // dibanding pratinjau). Rumusnya mengikuti cara browser memusatkan
            // blok teks, lalu dikoreksi dengan selisih metrik Dompdf.
            $lineHeight = 1.15;

            // Dompdf mengalikan line-height dengan faktor internal per font
            // (1.25-1.28). Bagi nilainya supaya pitch baris PDF = 1.15em,
            // sama seperti browser.
            $fontModel = $fonts->get(trim((string) $field->font_family));
            $pitchFactor = $fontModel instanceof CertificateFont ? $fontModel->lineHeightFactor() : 1.264;
            $dompdfLineHeight = $lineHeight / max(0.5, $pitchFactor);

            $lineHeightMm = $fontSize * 0.3528 * $lineHeight;
            $lineCount = $this->estimateLines($value, $field, $fontSize);
            $blockHeightMm = $lineHeightMm * max(1, $lineCount);

            $textTopMm = match ($valign) {
                'top' => 0.0,
                'bottom' => max(0.0, (float) $field->h_mm - $blockHeightMm),
                default => max(0.0, ((float) $field->h_mm - $blockHeightMm) / 2),
            };

            // Tidak perlu koreksi half-leading: setelah line-height dibagi
            // faktor pitch Dompdf, ukuran line box PDF sama dengan browser
            // sehingga pusat glyph otomatis jatuh di tengah (terukur 0.50 x
            // line-height pada nama maupun nomor surat).

            $style = implode(';', [
                'position:absolute',
                'left:'.$this->num($field->x_mm).'mm',
                'top:'.$this->num($field->y_mm).'mm',
                'width:'.$this->num($field->w_mm).'mm',
                'height:'.$this->num($field->h_mm).'mm',
                'overflow:hidden',
                'text-align:'.$align,
                'font-family:'.$this->cssFont($field->font_family, $fonts),
                'font-size:'.$this->num($fontSize).'pt',
                'font-weight:'.($field->bold ? 'bold' : 'normal'),
                'font-style:'.($field->italic ? 'italic' : 'normal'),
                'color:'.$this->cssColor($field->color),
                'line-height:'.$this->num($dompdfLineHeight),
            ]);

            $textStyle = 'position:absolute;left:0;right:0;width:100%;'
                .'top:'.$this->num($textTopMm).'mm;'
                .'text-align:'.$align.';';

            return '<div style="'.htmlspecialchars($style, ENT_QUOTES, 'UTF-8').'">'
                .'<div style="'.htmlspecialchars($textStyle, ENT_QUOTES, 'UTF-8').'">'.$text.'</div></div>';
        })->implode('');

        $backgroundStyle = $backgroundSrc !== ''
            ? 'position:absolute;left:0;top:0;width:'.$this->num($width).'mm;height:'.$this->num($height).'mm;z-index:-1;'
            : '';

        $backgroundTag = $backgroundSrc !== ''
            ? '<img src="'.htmlspecialchars($backgroundSrc, ENT_QUOTES, 'UTF-8').'" style="'.$backgroundStyle.'">'
            : '';

        return '<!doctype html><html><head><meta charset="utf-8">'
            .'<style>'.$this->fontFaceCss($fonts)
            .'@page { margin: 0; } html, body { margin: 0; padding: 0; }'
            .' body { font-family: "DejaVu Sans", sans-serif; }</style></head><body>'
            .'<div style="position:relative;width:'.$this->num($width).'mm;height:'.$this->num($height).'mm;overflow:hidden;">'
            .$backgroundTag
            .$blocks
            .'</div></body></html>';
    }

    /**
     * Perkirakan berapa baris teks butuh ruang; jika melebihi tinggi field,
     * kecilkan font bertahap sampai batas minimum. Semua nilai di-escape.
     *
     * @return array{0: float, 1: string}
     */
    private function fitText(string $value, CertificateField $field): array
    {
        $escaped = nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);

        if ($escaped === '') {
            return [$field->font_size_pt, ''];
        }

        $fontSize = $field->font_size_pt;
        $min = max(4, $fontSize * self::MIN_FONT_SCALE);

        while ($fontSize > $min && $this->estimateLines($value, $field, $fontSize) * $this->lineHeightMm($fontSize) > $field->h_mm) {
            $fontSize -= 0.5;
        }

        return [$fontSize, $escaped];
    }

    private function estimateLines(string $value, CertificateField $field, float $fontSizePt): int
    {
        // Rata-rata lebar karakter DejaVu Sans ≈ 0.55 em; 1 pt = 0.3528 mm.
        $charWidthMm = $fontSizePt * 0.55 * 0.3528;
        $usableWidth = max(0.1, $field->w_mm);
        $perLine = max(1, (int) floor($usableWidth / max(0.01, $charWidthMm)));

        $lines = 0;

        foreach (preg_split('/\R/u', $value) ?: [$value] as $paragraph) {
            $lines += max(1, (int) ceil(mb_strlen($paragraph) / $perLine));
        }

        return max(1, $lines);
    }

    private function lineHeightMm(float $fontSizePt): float
    {
        return $fontSizePt * 1.15 * 0.3528;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CertificateFont>  $fonts
     */
    private function cssFont(?string $font, \Illuminate\Support\Collection $fonts): string
    {
        // PENTING: font-family memakai kutip TUNGGAL. Style ini disisipkan ke
        // atribut style="..." (kutip ganda); nama font berkutip ganda akan
        // menutup atribut lebih awal sehingga font-size/pt hilang dan teks
        // tercetak kecil (default 12pt) meski setting-nya 36pt.
        $family = trim((string) $font);
        $model = $fonts->get($family);

        if ($model instanceof CertificateFont) {
            return $model->cssStack();
        }

        // Font lama (mis. sudah dihapus dari master) tetap dirender dengan
        // fallback DejaVu Sans agar PDF tidak gagal.
        return "'DejaVu Sans', sans-serif";
    }

    /**
     * Keluarga font yang dipakai field, di-key dengan css_family.
     *
     * @param  \Illuminate\Support\Collection<int, CertificateField>  $fields
     * @return \Illuminate\Support\Collection<string, CertificateFont>
     */
    private function fontsForFields(\Illuminate\Support\Collection $fields): \Illuminate\Support\Collection
    {
        $families = $fields
            ->map(fn (CertificateField $field): string => trim((string) $field->font_family))
            ->filter()
            ->unique()
            ->values();

        if ($families->isEmpty()) {
            return collect();
        }

        return CertificateFont::query()
            ->whereIn('css_family', $families->all())
            ->get()
            ->filter(fn (CertificateFont $font): bool => $font->is_active || $font->is_system)
            ->keyBy('css_family');
    }

    /**
     * @font-face untuk setiap varian font non-sistem yang dipakai. Font
     * sistem (DejaVu bundle + AFM core) sudah dikenal Dompdf.
     *
     * @param  \Illuminate\Support\Collection<string, CertificateFont>  $fonts
     */
    private function fontFaceCss(\Illuminate\Support\Collection $fonts): string
    {
        $css = '';

        foreach ($fonts as $font) {
            if (! $font->isEmbedded()) {
                continue;
            }

            $family = str_replace("'", '', $font->safeFamily());

            if ($family === '') {
                continue;
            }

            $regular = $font->absolutePath('regular');

            foreach ($font->variants() as $variantKey => $variant) {
                // Varian yang tidak diunggah memakai berkas Regular: Dompdf
                // tidak menyintesis bold/italic, tanpa ini teks jatuh ke font
                // fallback CSS (mis. Times) dan hasilnya berbeda dari preview.
                $absolute = $font->absolutePath($variantKey) ?? $regular;

                if ($absolute === null) {
                    continue;
                }

                $css .= '@font-face { font-family: "'.$family.'"; font-style: '.$variant['style']
                    .'; font-weight: '.$variant['weight']
                    .'; src: url("file://'.str_replace('\\', '/', $absolute).'") format("truetype"); }';
            }
        }

        return $css;
    }

    private function fontDir(): string
    {
        $dir = storage_path('app/private/'.CertificateFont::DIRECTORY);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (! is_writable($dir)) {
            @chmod($dir, 0777);
        }

        return $dir;
    }

    private function cssColor(?string $color): string
    {
        $color = trim((string) $color);

        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) === 1 ? $color : '#000000';
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function mmToPt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }

    /**
     * Folder sementara di bawah storage/app milik www-data (bukan /tmp
     * sistem) — mengikuti pola SuratTugasGeneratorService::tempDir().
     */
    private function tempDir(): string
    {
        $dir = storage_path('app/private/certificate/tmp');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (! is_writable($dir)) {
            @chmod($dir, 0777);

            if (! is_writable($dir)) {
                return sys_get_temp_dir();
            }
        }

        return $dir;
    }
}
