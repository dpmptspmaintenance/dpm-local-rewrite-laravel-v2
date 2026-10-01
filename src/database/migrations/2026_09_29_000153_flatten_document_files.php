<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refactor ke model list berkas flat: SEMUA berkas milik dokumen sekarang
     * hidup di document_files, tanpa konsep "utama vs ekstra" lagi. Kolom flat
     * di documents (google_file_id dst.) dipensiunkan — baris flat yang sudah
     * ada dipindahkan dulu ke document_files sebelum kolomnya dihapus.
     */
    public function up(): void
    {
        // 1. Pindahkan berkas flat yang sudah ada ke document_files.
        DB::table('documents')
            ->whereNotNull('google_file_id')
            ->orderBy('id')
            ->eachById(function (object $doc): void {
                DB::table('document_files')->insert([
                    'document_id' => $doc->id,
                    'google_file_id' => $doc->google_file_id,
                    'google_web_view_link' => $doc->google_web_view_link,
                    'original_filename' => $doc->original_filename,
                    'file_extension' => $doc->file_extension,
                    'mime_type' => $doc->mime_type,
                    'file_size' => $doc->file_size ?? 0,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->updated_at,
                ]);
            });

        // 2. Hapus kolom flat dari documents.
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['google_file_id']);
            $table->dropColumn([
                'google_file_id',
                'google_web_view_link',
                'original_filename',
                'file_extension',
                'mime_type',
                'file_size',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('google_file_id', 191)->nullable();
            $table->text('google_web_view_link')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
        });

        // Kembalikan berkas pertama tiap dokumen ke kolom flat (best effort).
        DB::table('document_files')->orderBy('id')->eachById(function (object $file): void {
            DB::table('documents')
                ->where('id', $file->document_id)
                ->whereNull('google_file_id')
                ->update([
                    'google_file_id' => $file->google_file_id,
                    'google_web_view_link' => $file->google_web_view_link,
                    'original_filename' => $file->original_filename,
                    'file_extension' => $file->file_extension,
                    'mime_type' => $file->mime_type,
                    'file_size' => $file->file_size,
                ]);
        });
    }
};
