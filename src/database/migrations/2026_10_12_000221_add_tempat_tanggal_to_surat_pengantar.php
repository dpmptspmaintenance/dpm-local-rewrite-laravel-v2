<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tempat+tanggal terbit surat (mis. "Semarang, 1 Oktober 2026")
     * untuk Surat Pengantar. Dipisah dari migration pembuatan tabel karena
     * tertinggal saat penulisan awal.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar', function (Blueprint $table) {
            $table->string('tempat_tanggal', 255)->nullable()->after('lampiran');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar', function (Blueprint $table) {
            $table->dropColumn('tempat_tanggal');
        });
    }
};
