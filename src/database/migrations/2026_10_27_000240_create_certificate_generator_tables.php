<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generator Sertifikat — template gambar + field posisi absolut (mm) +
 * dataset Excel + log unduhan. Satu template dimiliki satu pembuat; field
 * disimpan dalam satuan milimeter supaya preview canvas = hasil PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('background_path');
            $table->unsignedInteger('bg_width_px')->default(0);
            $table->unsignedInteger('bg_height_px')->default(0);
            $table->decimal('page_width_mm', 8, 2)->default(297);
            $table->decimal('page_height_mm', 8, 2)->default(210);
            $table->string('file_name_pattern')->default('sertifikat <<nama>>');
            $table->timestamps();
        });

        Schema::create('certificate_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('certificate_templates')->cascadeOnDelete();
            $table->string('key');
            $table->string('label')->nullable();
            $table->decimal('x_mm', 8, 2)->default(10);
            $table->decimal('y_mm', 8, 2)->default(10);
            $table->decimal('w_mm', 8, 2)->default(80);
            $table->decimal('h_mm', 8, 2)->default(20);
            $table->string('font_family', 100)->default('DejaVu Sans');
            $table->decimal('font_size_pt', 5, 2)->default(24);
            $table->string('color', 20)->default('#000000');
            $table->boolean('bold')->default(false);
            $table->boolean('italic')->default(false);
            $table->string('align', 10)->default('center');
            $table->string('valign', 10)->default('middle');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'sort_order']);
        });

        Schema::create('certificate_datasets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('certificate_templates')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name')->nullable();
            $table->json('headers');
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamps();
        });

        Schema::create('certificate_dataset_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dataset_id')->constrained('certificate_datasets')->cascadeOnDelete();
            $table->unsignedInteger('row_index');
            $table->json('data');
            $table->timestamps();

            $table->index(['dataset_id', 'row_index']);
        });

        Schema::create('certificate_generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('certificate_templates')->cascadeOnDelete();
            $table->foreignId('dataset_id')->nullable()->constrained('certificate_datasets')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 10)->default('single');
            $table->string('file_name')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_generations');
        Schema::dropIfExists('certificate_dataset_rows');
        Schema::dropIfExists('certificate_datasets');
        Schema::dropIfExists('certificate_fields');
        Schema::dropIfExists('certificate_templates');
    }
};
