<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran gambar notulen (JSON array path di disk `arsip`). Digambar
     * sebagai tabel 2 kolom sebelum blok tanda tangan oleh
     * NotulenGeneratorService.
     */
    public function up(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('notulen')) {
            return;
        }

        if (! $schema->hasColumn('notulen', 'gambar')) {
            $schema->table('notulen', function (Blueprint $table): void {
                $table->longText('gambar')->nullable()->after('hasil_acara');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('kepegawaian');

        if ($schema->hasTable('notulen') && $schema->hasColumn('notulen', 'gambar')) {
            $schema->table('notulen', function (Blueprint $table): void {
                $table->dropColumn('gambar');
            });
        }
    }
};
