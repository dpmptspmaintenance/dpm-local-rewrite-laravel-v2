<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat Generator Surat Cuti: snapshot nomor yang pernah dibuat
     * (nama, jenis, durasi, nomor, flag duplikat, sisa saat itu). MURNI
     * arsip — tidak dibaca oleh perhitungan cuti mana pun.
     *
     * Lives on the `kepegawaian` connection.
     */
    public function up(): void
    {
        // Tabel bisa sudah dibuat manual di production sebelum migration ini
        // deploy — lewati bila sudah ada supaya migrate tetap hijau.
        if (! Schema::connection('kepegawaian')->hasTable('riwayat_generator_cuti')) {
            Schema::connection('kepegawaian')->create('riwayat_generator_cuti', function (Blueprint $table) {
                $table->id();
                $table->string('batch', 50);
                $table->string('nama', 150);
                $table->string('jenis', 100);
                $table->unsignedInteger('hari')->default(0);
                $table->string('rentang', 150)->nullable();
                $table->string('pengajuan', 50)->nullable();
                $table->string('nomor_surat', 100);
                $table->boolean('is_duplikat')->default(false);
                $table->text('sisa')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('created_by_nama', 150)->nullable();
                $table->timestamps();

                $table->index('batch');
                $table->index('nomor_surat');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('riwayat_generator_cuti');
    }
};
