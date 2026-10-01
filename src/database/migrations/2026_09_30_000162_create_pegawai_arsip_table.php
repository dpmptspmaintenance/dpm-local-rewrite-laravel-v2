<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip berkas pegawai (SKP, SK Kenaikan Pangkat, SK Jabatan, Foto,
     * Ijazah, dll) — beda dari modul Arsip Digital (/arsip, App\Models\Document)
     * yang untuk dokumen OPD umum. Ini KHUSUS per pegawai, tapi reuse
     * infrastruktur Google Drive yang sama (App\Services\ArsipDigital\GoogleDriveService)
     * lewat subfolder "Kepegawaian" tersendiri di dalam folder induk arsip
     * (ARSIP_DRIVE_FOLDER_ID), supaya tidak perlu Service Account/OAuth kedua.
     *
     * Berbeda dari pegawai_anak/pegawai_kompetensi/pegawai_penghargaan (yang
     * full-snapshot replace dari impor JSON SISDM), tabel ini SELALU manual —
     * tidak ada impor otomatis untuk arsip berkas fisik.
     *
     * Lives on the `kepegawaian` connection (its own database) — TIDAK ada FK
     * ke categories/users di database `mysql` (dpmptsp_new), karena lintas
     * database tak bisa constraint; kategori di sini bebas ketik (TagsInput-
     * style di UI, bukan tabel categories terpisah), dan uploaded_by cuma
     * simpan id+nama user sebagai snapshot teks.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('pegawai_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100);
            $table->string('judul', 255);
            $table->string('kategori', 100)->nullable();
            $table->string('google_file_id', 191);
            $table->text('google_web_view_link')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('uploaded_by_nama', 150)->nullable();
            $table->timestamps();

            $table->index('nip');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('pegawai_arsip');
    }
};
