<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk Tool Notulen Maker di Modul Kepegawaian.
     * Menggunakan koneksi 'kepegawaian' dan menyimpan snapshot penandatangan
     * (Atasan Mengetahui & Yang Melaporkan) dari tabel users.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('notulen', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 500);
            $table->string('hari_tanggal', 255);
            $table->string('waktu', 255);
            $table->text('tempat');
            $table->text('dasar')->nullable();
            $table->text('narasumber')->nullable();
            $table->text('peserta_deskripsi')->nullable();
            $table->text('peserta_daftar')->nullable();
            $table->longText('hasil_acara');
            $table->text('penutup')->nullable();
            $table->string('tanggal_naskah', 255)->nullable();

            // Penandatangan 1: Mengetahui (Atasan)
            $table->unsignedBigInteger('atasan_user_id')->nullable();
            $table->string('atasan_nama', 255);
            $table->string('atasan_nip', 100)->nullable();
            $table->string('atasan_jabatan', 255)->nullable();

            // Penandatangan 2: Yang Melaporkan
            $table->unsignedBigInteger('pelapor_user_id')->nullable();
            $table->string('pelapor_nama', 255);
            $table->string('pelapor_nip', 100)->nullable();
            $table->string('pelapor_jabatan', 255)->nullable();

            // Pembuat record
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->string('dibuat_oleh_nama', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('notulen');
    }
};
