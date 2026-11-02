<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Master Font untuk Generator Sertifikat. Daftar font tidak lagi hardcoded
 * di Blade/CertificateRenderer: admin bisa menambah (unggah TTF) atau
 * menonaktifkan font dari panel Kepegawaian.
 *
 * Baris `is_system` = font yang sudah tersedia di Dompdf tanpa unggahan
 * (3 keluarga DejaVu ter-bundle + 3 core font AFM). Font non-sistem wajib
 * punya berkas TTF di storage/app/private/certificate/fonts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_fonts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('css_family', 100)->unique();
            $table->string('category', 20)->default('sans');
            $table->string('regular_path', 500)->nullable();
            $table->string('bold_path', 500)->nullable();
            $table->string('italic_path', 500)->nullable();
            $table->string('bold_italic_path', 500)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        $fonts = [
            ['name' => 'DejaVu Sans', 'css_family' => 'DejaVu Sans', 'category' => 'sans', 'sort_order' => 10],
            ['name' => 'DejaVu Serif', 'css_family' => 'DejaVu Serif', 'category' => 'serif', 'sort_order' => 20],
            ['name' => 'DejaVu Sans Mono', 'css_family' => 'DejaVu Sans Mono', 'category' => 'mono', 'sort_order' => 30],
            ['name' => 'Helvetica', 'css_family' => 'Helvetica', 'category' => 'sans', 'sort_order' => 40],
            ['name' => 'Times', 'css_family' => 'Times', 'category' => 'serif', 'sort_order' => 50],
            ['name' => 'Courier', 'css_family' => 'Courier', 'category' => 'mono', 'sort_order' => 60],
        ];

        DB::table('certificate_fonts')->insert(array_map(
            fn (array $font): array => $font + [
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $fonts,
        ));

        // Font lama di template yang tidak lagi ada di master tetap bisa
        // dirender (fallback DejaVu Sans), tapi nilainya dinormalisasi bila
        // hanya beda kapitalisasi/spasi.
        foreach (DB::table('certificate_fields')->select('font_family')->distinct()->pluck('font_family') as $family) {
            if (blank($family)) {
                continue;
            }

            $canonical = collect($fonts)->first(
                fn (array $font): bool => Str::lower($font['css_family']) === Str::lower(trim((string) $family)),
            );

            if ($canonical !== null && $canonical['css_family'] !== $family) {
                DB::table('certificate_fields')
                    ->where('font_family', $family)
                    ->update(['font_family' => $canonical['css_family']]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_fonts');
    }
};
