<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berkas lampiran DRH (a–e) — diunggah per-DRH, disimpan di disk lokal
     * app (bukan Google Drive), lalu bisa digabung jadi 1 PDF oleh
     * DrhSatyaLancanaDocumentService. Satu baris per jenis lampiran; re-upload
     * jenis yang sama mengganti barisnya (unique per drh+jenis).
     *
     * Jenis (kolom `jenis`, varchar 1):
     *  a = DRH yang ditandatangani & diketahui atasan langsung
     *  b = SK CPNS
     *  c = SK Pangkat Terakhir
     *  d = SK Jabatan Terakhir
     *  e = Piagam/Petikan Keppres SLKS tingkat sebelumnya (opsional)
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('drh_satya_lancana_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drh_satya_lancana_id')
                ->constrained('drh_satya_lancana')
                ->cascadeOnDelete();
            $table->string('jenis', 1);
            $table->string('path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->unique(['drh_satya_lancana_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('drh_satya_lancana_dokumen');
    }
};
