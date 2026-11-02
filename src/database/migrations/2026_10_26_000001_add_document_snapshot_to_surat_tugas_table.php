<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot dokumen .docx per Surat Tugas: satu-satunya sumber kebenaran
     * saat user mengunduh Word/PDF. Isinya TIDAK di-regenerate otomatis,
     * supaya koreksi manual di halaman "Dokumen" tidak hilang saat dibuka
     * lagi. Lihat App\Services\Kepegawaian\SuratTugasDocumentService.
     *
     * Kolom override_* menyimpan koreksi teks per surat. NULL berarti
     * memakai nilai asli dari data surat (judul/hari/waktu/tempat/...).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_tugas', function (Blueprint $table) {
            $table->string('document_docx_path', 500)->nullable()->after('dasar_hukum_tambahan');
            $table->timestamp('document_generated_at')->nullable()->after('document_docx_path');
            $table->unsignedBigInteger('document_generated_by')->nullable()->after('document_generated_at');
            $table->timestamp('document_edited_at')->nullable()->after('document_generated_by');
            $table->unsignedBigInteger('document_edited_by')->nullable()->after('document_edited_at');

            $table->text('override_judul')->nullable()->after('document_edited_by');
            $table->string('override_hari_tanggal', 255)->nullable()->after('override_judul');
            $table->string('override_waktu', 255)->nullable()->after('override_hari_tanggal');
            $table->string('override_tempat', 255)->nullable()->after('override_waktu');
            $table->string('override_tanggal_naskah', 255)->nullable()->after('override_tempat');
            $table->text('override_dasar_hukum')->nullable()->after('override_tanggal_naskah');
            $table->text('override_penutup')->nullable()->after('override_dasar_hukum');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_tugas', function (Blueprint $table) {
            $table->dropColumn([
                'document_docx_path',
                'document_generated_at',
                'document_generated_by',
                'document_edited_at',
                'document_edited_by',
                'override_judul',
                'override_hari_tanggal',
                'override_waktu',
                'override_tempat',
                'override_tanggal_naskah',
                'override_dasar_hukum',
                'override_penutup',
            ]);
        });
    }
};
