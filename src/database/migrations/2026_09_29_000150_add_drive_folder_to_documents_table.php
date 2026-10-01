<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            // ID folder Drive khusus dokumen ini — diisi saat Approve (folder
            // "YYYY-MM-DD - Judul" dibuat di folder induk dan berkas dipindah
            // ke sana). NULL untuk pending/rejected karena berkas mereka masih
            // numpang di folder penampung "etc".
            $table->string('drive_folder_id')->nullable()->after('google_web_view_link');
            $table->string('drive_folder_name')->nullable()->after('drive_folder_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn(['drive_folder_id', 'drive_folder_name']);
        });
    }
};
