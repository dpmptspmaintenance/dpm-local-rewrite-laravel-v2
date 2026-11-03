<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot dokumen .docx per Notulen: satu-satunya sumber kebenaran saat
     * user mengunduh Word/PDF. Isinya TIDAK di-regenerate otomatis, supaya
     * koreksi manual di editor Word tidak hilang saat dibuka lagi.
     * Lihat App\Services\Kepegawaian\NotulenDocumentService.
     */
    public function up(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('notulen')) {
            return;
        }

        $schema->table('notulen', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('notulen', 'document_docx_path')) {
                $table->string('document_docx_path', 500)->nullable()->after('tanggal_naskah');
            }

            if (! $schema->hasColumn('notulen', 'document_generated_at')) {
                $table->timestamp('document_generated_at')->nullable()->after('document_docx_path');
            }

            if (! $schema->hasColumn('notulen', 'document_generated_by')) {
                $table->unsignedBigInteger('document_generated_by')->nullable()->after('document_generated_at');
            }

            if (! $schema->hasColumn('notulen', 'document_edited_at')) {
                $table->timestamp('document_edited_at')->nullable()->after('document_generated_by');
            }

            if (! $schema->hasColumn('notulen', 'document_edited_by')) {
                $table->unsignedBigInteger('document_edited_by')->nullable()->after('document_edited_at');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('notulen')) {
            return;
        }

        $schema->table('notulen', function (Blueprint $table) use ($schema): void {
            $columns = array_values(array_filter([
                'document_docx_path',
                'document_generated_at',
                'document_generated_by',
                'document_edited_at',
                'document_edited_by',
            ], fn (string $column): bool => $schema->hasColumn('notulen', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
