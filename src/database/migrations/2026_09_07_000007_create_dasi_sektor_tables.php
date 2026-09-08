<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dasi_data_sektor', function (Blueprint $table) {
            $table->id('Id');
            $table->string('nama_sektor');
            $table->boolean('is_aktif')->default(true);
        });

        Schema::create('dasi_data_proyek', function (Blueprint $table) {
            $table->id('Id');
            $table->string('kbli')->nullable();
            $table->string('nama_proyek')->nullable();
            $table->string('id_data_sektor', 11)->nullable();
            $table->string('id_data_source', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dasi_data_proyek');
        Schema::dropIfExists('dasi_data_sektor');
    }
};
