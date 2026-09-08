<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sikenut', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kegiatan');
            $table->string('surat_tugas', 100);
            $table->date('tanggal_surat')->nullable();
            $table->date('tanggal_acara')->nullable();
            $table->string('tipe_anggaran', 50)->nullable();
            $table->string('anggaran_bulan', 20)->nullable();
            $table->integer('tahun')->nullable();
            $table->unsignedBigInteger('disposisi')->nullable();
            $table->string('bidang', 100)->nullable();
            $table->string('anggaran_bidang', 100)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['nama_kegiatan', 'surat_tugas'], 'idx_kegiatan_surat');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sikenut');
    }
};
