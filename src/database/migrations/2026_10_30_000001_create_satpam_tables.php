<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data Satpam + arsip berkasnya (KTA, KTP, Ijazah, dll) — manual, tidak
     * ada impor otomatis. Satpam bukan ASN/SISDM sehingga tidak ada baris di
     * pegawai_profil; tabel sendiri supaya tidak bercampur arsip pegawai.
     *
     * Berkas fisik di disk lokal: {ARSIP_LOCAL_ROOT}/Kepegawaian/satpam/
     * "{id} - {Nama}"/ — lihat App\Services\Kepegawaian\SatpamArsipService.
     *
     * Lives on the `kepegawaian` connection (its own database) — TIDAK ada FK
     * ke users di database `mysql`, uploaded_by cuma snapshot id+nama.
     * satpam_arsip -> satpam satu database sehingga pakai FK cascade.
     */
    public function up(): void
    {
        // Tabel bisa sudah dibuat manual di production sebelum migration ini
        // deploy — lewati yang sudah ada supaya migrate tetap hijau.
        if (! Schema::connection('kepegawaian')->hasTable('satpam')) {
            Schema::connection('kepegawaian')->create('satpam', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('nik', 50)->nullable();
            $table->string('telepon', 50)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('nama');
            });
        }

        if (! Schema::connection('kepegawaian')->hasTable('satpam_arsip')) {
            Schema::connection('kepegawaian')->create('satpam_arsip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satpam_id')->constrained('satpam')->cascadeOnDelete();
            $table->string('judul', 255);
            $table->string('kategori', 100)->nullable();
            $table->string('storage_path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('uploaded_by_nama', 150)->nullable();
            $table->timestamps();

            $table->index('kategori');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('satpam_arsip');
        Schema::connection('kepegawaian')->dropIfExists('satpam');
    }
};
