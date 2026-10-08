<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip Digital pindah dari Google Drive ke disk lokal. Berkas fisik kini
     * ditunjuk oleh `storage_path` (path relatif di disk `arsip`), sedangkan
     * `google_file_id` jadi legacy — dibuat nullable agar baris baru (dan data
     * lama yang diabaikan) tetap valid.
     */
    public function up(): void
    {
        Schema::table('document_files', function (Blueprint $table): void {
            $table->string('google_file_id', 191)->nullable()->change();
            $table->string('storage_path', 512)->nullable()->after('document_id');
        });

        Schema::connection('kepegawaian')->table('pegawai_arsip', function (Blueprint $table): void {
            $table->string('google_file_id', 191)->nullable()->change();
            $table->string('storage_path', 512)->nullable()->after('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('document_files', function (Blueprint $table): void {
            $table->dropColumn('storage_path');
        });

        Schema::connection('kepegawaian')->table('pegawai_arsip', function (Blueprint $table): void {
            $table->dropColumn('storage_path');
        });
    }
};
