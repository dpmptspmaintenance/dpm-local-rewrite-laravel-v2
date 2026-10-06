<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat CPNS (satu baris per pegawai) + riwayat jabatan (banyak baris)
     * dari JSON impor SISDM — field baru yang dikirim sumber selain
     * riwayat_anak/kompetensi/penghargaan. Pola sama tabel kepegawaian lain:
     * koneksi `kepegawaian`, no FK ke `mysql`, no timestamps, full-snapshot
     * replace per NIP tiap impor (lihat PegawaiImportService::importOne()).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('pegawai_riwayat_cpns', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100)->unique();
            $table->string('file_field_name', 255)->nullable();
            $table->text('file_url')->nullable();
            $table->string('gaji', 50)->nullable();
            $table->string('golongan', 50)->nullable();
            $table->string('jabatan', 255)->nullable();
            $table->string('kd_golongan', 20)->nullable();
            $table->unsignedInteger('masa_kerja_bulan')->nullable();
            $table->unsignedInteger('masa_kerja_tahun')->nullable();
            $table->string('nomor_sk', 255)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->date('tmt_sk')->nullable();
            $table->string('unit_kerja', 255)->nullable();
        });

        Schema::connection('kepegawaian')->create('pegawai_riwayat_jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100);
            $table->unsignedInteger('no_urut')->nullable();
            $table->text('file_jabatan_url')->nullable();
            $table->string('jabatan_baru', 255)->nullable();
            $table->string('jenis_jabatan', 100)->nullable();
            $table->string('nomor_sk_jabatan', 255)->nullable();
            $table->string('opd', 255)->nullable();
            $table->date('tanggal_sk_jabatan')->nullable();
            $table->date('tmt_sk_jabatan')->nullable();
            $table->string('unit_kerja', 255)->nullable();
            $table->string('verifikasi', 50)->nullable();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('pegawai_riwayat_jabatan');
        Schema::connection('kepegawaian')->dropIfExists('pegawai_riwayat_cpns');
    }
};
