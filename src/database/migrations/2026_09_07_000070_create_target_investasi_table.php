<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_investasi', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tahun')->nullable();
            $table->string('status', 50)->nullable()->default('PMDN')->comment('PMA atau PMDN');
            $table->string('kecamatan', 150)->nullable();
            $table->decimal('target_nilai', 20, 2)->nullable()->default(0);
            $table->text('keterangan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_investasi');
    }
};
