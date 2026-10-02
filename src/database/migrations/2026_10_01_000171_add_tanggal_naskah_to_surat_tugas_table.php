<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opsional: tanggal naskah bisa diisi langsung (format "Semarang, j F Y")
     * kalau sudah diketahui saat Surat Tugas dibuat. Kalau dibiarkan kosong,
     * placeholder "${tanggal_naskah}" tetap literal di dokumen hasil (diisi
     * nanti di Srikandi) — lihat SuratTugasGeneratorService.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_tugas', function (Blueprint $table) {
            $table->string('tanggal_naskah', 255)->nullable()->after('tempat');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_tugas', function (Blueprint $table) {
            $table->dropColumn('tanggal_naskah');
        });
    }
};
