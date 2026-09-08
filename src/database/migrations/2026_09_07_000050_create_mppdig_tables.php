<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mppdig_faskes', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->nullable();
            $table->string('kategori', 100)->nullable();
            $table->string('nama')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('telepon', 50)->nullable();
            $table->string('email', 50)->nullable();
        });

        Schema::create('mppdig_permohonan_sip_semua', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_registrasi', 50)->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('profesi', 100)->nullable();
            $table->string('tempat_praktik', 100)->nullable();
            $table->string('nama_lengkap', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->string('nomor_hp', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->dateTime('waktu_input')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('no_sip', 50)->nullable();
            $table->dateTime('waktu_selesai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mppdig_permohonan_sip_semua');
        Schema::dropIfExists('mppdig_faskes');
    }
};
