<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dasi_master_resiko', function (Blueprint $table) {
            $table->id('Id');
            $table->string('kd_resiko', 2)->nullable();
            $table->string('Resiko', 30)->nullable();
        });

        Schema::create('dasi_master_izin', function (Blueprint $table) {
            $table->id('Id');
            $table->string('jenis_izin', 50)->nullable();
            $table->string('is_aktif', 1)->nullable();
        });

        Schema::create('dasi_master_dokumen_izin', function (Blueprint $table) {
            $table->id('Id');
            $table->string('dok_izin')->nullable();
            $table->string('is_aktif', 1)->nullable();
        });

        Schema::create('dasi_master_status_respon', function (Blueprint $table) {
            $table->id('Id');
            $table->string('status_respon')->nullable();
            $table->string('is_aktif', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dasi_master_status_respon');
        Schema::dropIfExists('dasi_master_dokumen_izin');
        Schema::dropIfExists('dasi_master_izin');
        Schema::dropIfExists('dasi_master_resiko');
    }
};
