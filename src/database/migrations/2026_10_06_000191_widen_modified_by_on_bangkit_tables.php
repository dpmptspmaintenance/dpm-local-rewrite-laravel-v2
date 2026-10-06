<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dump legacy menyimpan nilai teks pada kolom modified_by data_barang
     * ("direct database") dan permohonan ("TES"). Legacy tak punya FK, jadi
     * lebarkan ke string agar data dump bisa masuk apa adanya.
     */
    public function up(): void
    {
        Schema::connection('bangkit')->table('sekre_bangkit_data_barang', function (Blueprint $table) {
            $table->string('modified_by', 255)->nullable()->change();
        });
        Schema::connection('bangkit')->table('sekre_bangkit_data_permohonan_perbaikan', function (Blueprint $table) {
            $table->string('modified_by', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('bangkit')->table('sekre_bangkit_data_barang', function (Blueprint $table) {
            $table->unsignedInteger('modified_by')->nullable()->change();
        });
        Schema::connection('bangkit')->table('sekre_bangkit_data_permohonan_perbaikan', function (Blueprint $table) {
            $table->unsignedInteger('modified_by')->nullable()->change();
        });
    }
};
