<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag per baris cuti: TRUE bila baris pernah disimpan lewat form edit
     * UI. CutiImportService melewati baris ber-flag saat upsert by no_surat,
     * supaya koreksi manual tidak tertimpa file import berikutnya. Import
     * tetap memperbarui semua baris lain yang tak pernah diedit manual.
     *
     * ALTER pada tabel `cuti` yang sudah ada datanya — pengecualian ke-3
     * dari kebijakan "no migrations untuk skema kepegawaian" (setelah
     * cuti_kuota_tahunan dan sesuai_jabatan), dikonfirmasi eksplisit user.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('cuti', function (Blueprint $table) {
            $table->boolean('diedit_manual')->default(false)->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('cuti', function (Blueprint $table) {
            $table->dropColumn('diedit_manual');
        });
    }
};
