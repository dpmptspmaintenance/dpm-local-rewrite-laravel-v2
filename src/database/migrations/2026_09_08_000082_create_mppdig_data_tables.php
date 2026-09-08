<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mppdig_permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('no_reg', 100);
            $table->string('nik', 20)->nullable();
            $table->string('nama')->nullable();
            $table->string('profesi', 100)->nullable();
            $table->text('tempat_praktik')->nullable();
            $table->string('status_permohonan', 100)->nullable();
            $table->dateTime('tgl_permohonan')->nullable();
            $table->string('nomor_sip', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index(['nama', 'nik'], 'idx_search');
            $table->index('profesi', 'idx_filter_profesi');
            $table->index('status_permohonan', 'idx_filter_status');
            $table->index('tgl_permohonan', 'idx_filter_tgl');
        });

        Schema::create('mppdig_pemohon', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('telp', 50)->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('mppdig_kendala', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('email', 150)->nullable();
            $table->text('kendala')->nullable();
            $table->string('status', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mppdig_kendala');
        Schema::dropIfExists('mppdig_pemohon');
        Schema::dropIfExists('mppdig_permohonan');
    }
};
