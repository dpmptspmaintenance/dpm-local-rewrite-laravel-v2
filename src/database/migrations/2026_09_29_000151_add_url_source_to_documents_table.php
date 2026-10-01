<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            // Sumber dokumen: 'file' = berkas fisik diunggah ke Google Drive
            // (google_file_id terisi), 'url' = referensi tautan saja (Google
            // Drive / web) tanpa berkas fisik — tidak ada yang dipindah/dihapus
            // di Drive untuk tipe ini.
            $table->string('source_type', 10)->default('file')->after('title');
            $table->string('source_url', 1024)->nullable()->after('source_type');

            // Tiga kolom ini nullable supaya dokumen tipe 'url' (tanpa berkas
            // fisik) bisa disimpan; untuk tipe 'file' tetap selalu terisi.
            $table->string('google_file_id', 191)->nullable()->change();
            $table->string('original_filename', 255)->nullable()->change();
            $table->string('file_extension', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn(['source_type', 'source_url']);
        });
    }
};
