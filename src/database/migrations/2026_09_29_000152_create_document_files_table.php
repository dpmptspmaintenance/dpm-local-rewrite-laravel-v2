<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran tambahan untuk satu dokumen. Kolom flat di `documents`
     * (google_file_id dst.) tetap merepresentasikan berkas UTAMA (primary) —
     * tabel ini menampung berkas EKSTRA saat admin "menggabung" berkas dari
     * dokumen lain lewat fitur Pindah Berkas (bukan mengganti/menghapus
     * berkas lama target, sesuai keputusan user).
     */
    public function up(): void
    {
        Schema::create('document_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('google_file_id', 191);
            $table->text('google_web_view_link')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();

            $table->index('google_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_files');
    }
};
