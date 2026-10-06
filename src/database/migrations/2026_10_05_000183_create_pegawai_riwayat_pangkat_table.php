<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat pangkat (banyak baris per pegawai) dari JSON impor SISDM —
     * field baru selain riwayat_cpns/jabatan/penghargaan. Pola sama tabel
     * kepegawaian lain: koneksi `kepegawaian`, no FK, no timestamps,
     * full-snapshot replace per NIP tiap impor (PegawaiImportService).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('pegawai_riwayat_pangkat', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100);
            $table->unsignedInteger('no_urut')->nullable();
            $table->text('file_pangkat_url')->nullable();
            $table->string('golongan', 50)->nullable();
            $table->string('jenis_kenaikan_pangkat', 255)->nullable();
            $table->unsignedInteger('masa_kerja_bulan')->nullable();
            $table->unsignedInteger('masa_kerja_tahun')->nullable();
            $table->string('nomor_sk_pangkat', 255)->nullable();
            $table->string('pangkat', 255)->nullable();
            $table->string('siasn', 255)->nullable();
            $table->date('tanggal_sk_pangkat')->nullable();
            $table->date('tmt_sk_pangkat')->nullable();
            $table->string('verifikasi', 50)->nullable();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('pegawai_riwayat_pangkat');
    }
};
