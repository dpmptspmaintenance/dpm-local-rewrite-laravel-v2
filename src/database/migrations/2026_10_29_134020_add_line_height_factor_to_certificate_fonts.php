<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faktor jarak antar-baris Dompdf per font.
 *
 * Dompdf mengalikan `line-height` yang kita minta dengan faktor internal
 * (diturunkan dari metrik font). Akibatnya jarak baris di PDF ~1.26-1.28x
 * lebih longgar daripada browser, sehingga teks 2+ baris bergeser dan
 * terlihat berbeda dari pratinjau.
 *
 * Nilai di bawah diukur dari render nyata (pdftotext -bbox, 2 baris,
 * line-height:1em), lalu dipakai renderer untuk menghitung line-height
 * efektif (1.15 / faktor) supaya pitch PDF = pitch browser (1.15em).
 */
return new class extends Migration
{
    /** @var array<string, float> css_family => faktor pitch Dompdf */
    private const FACTORS = [
        'DejaVu Sans' => 1.280,
        'DejaVu Serif' => 1.280,
        'DejaVu Sans Mono' => 1.280,
        'Helvetica' => 1.265,
        'Times' => 1.264,
        'Courier' => 1.246,
    ];

    public function up(): void
    {
        Schema::table('certificate_fonts', function (Blueprint $table): void {
            $table->decimal('line_height_factor', 6, 3)->default(1.264)->after('bold_italic_path');
        });

        foreach (self::FACTORS as $family => $factor) {
            DB::table('certificate_fonts')
                ->where('css_family', $family)
                ->update(['line_height_factor' => $factor, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('certificate_fonts', function (Blueprint $table): void {
            $table->dropColumn('line_height_factor');
        });
    }
};
