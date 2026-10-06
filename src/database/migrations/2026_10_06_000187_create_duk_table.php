<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DAFTAR URUT KEPANGKATAN (DUK) — snapshot hasil impor PDF. Beda dari
     * penghargaan/rekap yang menghitung masa kerja dari NIP: DUK membawa
     * masa kerja hasil perhitungan resmi kepegawaian (masa_kerja_tahun +
     * masa_kerja_bulan) apa adanya dari dokumen, jadi tak dihitung ulang.
     *
     * Snapshot terbaru saja: tiap impor PDF mengganti seluruh isi tabel
     * (delete-all lalu insert), bukan per-periode.
     *
     * Kolom mengikuti dokumen DUK asli + warisan `sekre_pegawai_duk`.
     * Koneksi `kepegawaian` (no FK ke `mysql`).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('duk', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('urutan_duk')->nullable();
            $table->string('nama', 255)->nullable();
            $table->string('nip', 100)->nullable();
            $table->string('gol', 10)->nullable();
            $table->string('tmt', 30)->nullable();
            $table->string('gol_cpns', 10)->nullable();
            $table->string('tmt_cpns', 30)->nullable();
            $table->string('jabatan', 255)->nullable();
            $table->string('eselon', 30)->nullable();
            $table->unsignedInteger('masa_kerja_tahun')->nullable();
            $table->unsignedInteger('masa_kerja_bulan')->nullable();
            $table->string('pendidikan', 255)->nullable();

            // Metadata sumber.
            $table->string('opd', 255)->nullable();
            $table->string('periode', 50)->nullable();
            $table->string('original_filename')->nullable();
            $table->string('diimpor_oleh_nama', 150)->nullable();
            $table->timestamp('diimpor_pada')->nullable();

            $table->timestamps();

            $table->index('nip');
            $table->index('urutan_duk');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('duk');
    }
};
