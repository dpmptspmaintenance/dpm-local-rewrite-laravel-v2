<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tempelkan berkas TTF ke font sistem lama (DejaVu, Helvetica, Times,
 * Courier) supaya SEMUA font yang bisa dipilih benar-benar ter-embed ke PDF.
 *
 * Masalah yang diperbaiki: Helvetica/Times/Courier dulu memakai font AFM
 * bawaan Dompdf yang TIDAK di-embed (`emb no`, Type 1, WinAnsi). Akibatnya
 * pembaca PDF (Word/Acrobat di Windows) mengganti fontnya sendiri — muncul
 * sebagai "Cambria-Bold" / "Microsoft Sans Serif" — sehingga hasil unduhan
 * berbeda dari pratinjau.
 *
 * Berkas TTF disimpan di resources/fonts/certificate/ dan ikut repo (prefix
 * "bundled:"), jadi tersedia di semua environment tanpa upload manual.
 * Times/Helvetica/Courier memakai Liberation (metrik identik dengan
 * Times New Roman / Arial / Courier New) — tampilan tetap sama, tapi kini
 * ter-embed dan mendukung UTF-8 penuh.
 */
return new class extends Migration
{
    /** @var array<string, array{regular: string, bold: string, italic: string, bold_italic: string}> */
    private const FILES = [
        'DejaVu Sans' => [
            'regular' => 'DejaVuSans.ttf',
            'bold' => 'DejaVuSans-Bold.ttf',
            'italic' => 'DejaVuSans-Oblique.ttf',
            'bold_italic' => 'DejaVuSans-BoldOblique.ttf',
        ],
        'DejaVu Serif' => [
            'regular' => 'DejaVuSerif.ttf',
            'bold' => 'DejaVuSerif-Bold.ttf',
            'italic' => 'DejaVuSerif-Italic.ttf',
            'bold_italic' => 'DejaVuSerif-BoldItalic.ttf',
        ],
        'DejaVu Sans Mono' => [
            'regular' => 'DejaVuSansMono.ttf',
            'bold' => 'DejaVuSansMono-Bold.ttf',
            'italic' => 'DejaVuSansMono-Oblique.ttf',
            'bold_italic' => 'DejaVuSansMono-BoldOblique.ttf',
        ],
        'Helvetica' => [
            'regular' => 'LiberationSans-Regular.ttf',
            'bold' => 'LiberationSans-Bold.ttf',
            'italic' => 'LiberationSans-Italic.ttf',
            'bold_italic' => 'LiberationSans-BoldItalic.ttf',
        ],
        'Times' => [
            'regular' => 'LiberationSerif-Regular.ttf',
            'bold' => 'LiberationSerif-Bold.ttf',
            'italic' => 'LiberationSerif-Italic.ttf',
            'bold_italic' => 'LiberationSerif-BoldItalic.ttf',
        ],
        'Courier' => [
            'regular' => 'LiberationMono-Regular.ttf',
            'bold' => 'LiberationMono-Bold.ttf',
            'italic' => 'LiberationMono-Italic.ttf',
            'bold_italic' => 'LiberationMono-BoldItalic.ttf',
        ],
    ];

    public function up(): void
    {
        foreach (self::FILES as $family => $files) {
            DB::table('certificate_fonts')
                ->where('css_family', $family)
                ->update([
                    'regular_path' => 'bundled:'.$files['regular'],
                    'bold_path' => 'bundled:'.$files['bold'],
                    'italic_path' => 'bundled:'.$files['italic'],
                    'bold_italic_path' => 'bundled:'.$files['bold_italic'],
                    'is_system' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Kembali ke font AFM bawaan Dompdf (tidak embedded).
        DB::table('certificate_fonts')
            ->whereIn('css_family', array_keys(self::FILES))
            ->update([
                'regular_path' => null,
                'bold_path' => null,
                'italic_path' => null,
                'bold_italic_path' => null,
                'updated_at' => now(),
            ]);
    }
};
