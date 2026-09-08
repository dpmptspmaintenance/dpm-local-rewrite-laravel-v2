<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_dp_proyek', function (Blueprint $table) {
            $table->id();
            $table->year('tahun_pengambilan_data');
            $table->decimal('jml_investasi', 20, 2)->nullable()->default(0.00);
            $table->integer('bulan_pengambilan_data')->nullable();
            $table->string('nama_proyek')->nullable();
            $table->string('nama_perusahaan')->nullable();
            $table->string('sektor', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->integer('jml_tki')->default(0);
            $table->string('kbli', 50)->nullable();

            $table->index('tahun_pengambilan_data', 'idx_tahun');
            $table->index('bulan_pengambilan_data', 'idx_bulan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_dp_proyek');
    }
};
