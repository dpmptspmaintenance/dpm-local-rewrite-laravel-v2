<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datakita_upload_file', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->text('lokasi_file');
            $table->string('klasifikasi', 100);
            $table->string('created_by', 100);
            $table->tinyInteger('is_aktif')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('klasifikasi', 'idx_klasifikasi');
            $table->index('is_aktif', 'idx_is_aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datakita_upload_file');
    }
};
