<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan asal-usul batch riwayat: manual (halaman generator) atau
     * extension (API), plus input mentahnya untuk audit.
     */
    public function up(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('riwayat_generator_cuti')) {
            return;
        }

        $schema->table('riwayat_generator_cuti', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('riwayat_generator_cuti', 'sumber')) {
                $table->string('sumber', 20)->default('manual')->after('batch');
            }

            if (! $schema->hasColumn('riwayat_generator_cuti', 'sumber_label')) {
                $table->string('sumber_label', 100)->nullable()->after('sumber');
            }

            if (! $schema->hasColumn('riwayat_generator_cuti', 'teks_mentah')) {
                $table->longText('teks_mentah')->nullable()->after('sisa');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('riwayat_generator_cuti')) {
            return;
        }

        $schema->table('riwayat_generator_cuti', function (Blueprint $table) use ($schema) {
            foreach (['sumber', 'sumber_label', 'teks_mentah'] as $kolom) {
                if ($schema->hasColumn('riwayat_generator_cuti', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
