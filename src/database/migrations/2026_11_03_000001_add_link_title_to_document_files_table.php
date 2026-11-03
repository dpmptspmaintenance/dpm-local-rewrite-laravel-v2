<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tautan berjudul sebagai baris document_files: judul di
     * `original_filename`, URL di `google_web_view_link`. Dokumen bisa
     * berisi campuran berkas fisik + tautan sekaligus.
     */
    public function up(): void
    {
        if (Schema::hasTable('document_files') && ! Schema::hasColumn('document_files', 'link_title')) {
            Schema::table('document_files', function (Blueprint $table): void {
                $table->string('link_title', 255)->nullable()->after('google_web_view_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_files') && Schema::hasColumn('document_files', 'link_title')) {
            Schema::table('document_files', function (Blueprint $table): void {
                $table->dropColumn('link_title');
            });
        }
    }
};
