<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat impor DUK: tiap impor PDF jadi satu "batch" (duk_impor) supaya
     * data bisa disimpan per upload (bukan snapshot terbaru yang menimpa).
     * Tabel `duk` dapat kolom duk_impor_id → tiap baris milik satu batch;
     * batch lama tetap tersimpan dan bisa dipilih di halaman DUK.
     *
     * Metadata (opd/periode/original_filename/diimpor_oleh_nama/diimpor_pada)
     * tetap ada di baris `duk` (denormalisasi warisan) tapi sekarang juga
     * disimpan sekali di batch — sumber untuk dropdown "kapan upload".
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('duk_impor', function (Blueprint $table) {
            $table->id();
            $table->string('opd', 255)->nullable();
            $table->string('periode', 50)->nullable();
            $table->string('original_filename')->nullable();
            $table->string('diimpor_oleh_nama', 150)->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->timestamp('diimpor_pada')->nullable();
            $table->timestamps();
        });

        Schema::connection('kepegawaian')->table('duk', function (Blueprint $table) {
            $table->unsignedBigInteger('duk_impor_id')->nullable()->after('id');
            $table->index('duk_impor_id');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('duk', function (Blueprint $table) {
            $table->dropIndex(['duk_impor_id']);
            $table->dropColumn('duk_impor_id');
        });

        Schema::connection('kepegawaian')->dropIfExists('duk_impor');
    }
};
