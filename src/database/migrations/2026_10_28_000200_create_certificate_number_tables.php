<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor Sertifikat — daftar kegiatan beserta nomor surat sertifikatnya.
 *
 * Satu kegiatan punya satu blok penomoran (kode + tahun + nomor awal). Nomor
 * berlanjut antar kegiatan pada tahun yang sama; nomor awal disarankan
 * otomatis (nomor terakhir + 1) tetapi tetap bisa diubah manual.
 *
 * `nomor_surat` disimpan sebagai snapshot supaya cetak lama tidak berubah
 * saat kode/tahun kegiatan diedit (ada aksi regenerate terpisah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_number_activities', function (Blueprint $table): void {
            $table->id();
            $table->text('kegiatan');
            $table->string('tempat', 150)->nullable();
            $table->date('tanggal')->nullable();
            $table->string('kode', 30)->default('800.2/');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_awal')->default(1);
            $table->unsignedInteger('nomor_akhir')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tahun');
            $table->index('created_by');
        });

        Schema::create('certificate_number_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained('certificate_number_activities')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->unsignedInteger('nomor');
            $table->string('kode', 30);
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_surat', 100);
            $table->string('nama', 255);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'urutan']);
            $table->index(['activity_id', 'nomor']);
            $table->index(['tahun', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_number_rows');
        Schema::dropIfExists('certificate_number_activities');
    }
};
