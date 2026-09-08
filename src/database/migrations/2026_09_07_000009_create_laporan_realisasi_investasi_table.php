<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_realisasi_investasi', function (Blueprint $table) {
            $table->id();
            $table->string('no_laporan', 100)->nullable();
            $table->string('no_proyek', 100)->nullable();
            $table->string('nama_perusahaan')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('negara', 100)->nullable();
            $table->integer('tahun')->nullable();
            $table->string('triwulan', 20)->nullable();
            $table->string('periode_tahap', 50)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->text('lokasi_usaha')->nullable();
            $table->string('kab_kot', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('no_izin', 100)->nullable();
            $table->text('deskripsi_kbli')->nullable();
            $table->text('deskripsi_kbli_5digit')->nullable();
            $table->string('nama_sektor', 150)->nullable();
            $table->decimal('nilai_investasi', 20, 2)->nullable();
            $table->integer('tki')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_realisasi_investasi');
    }
};
