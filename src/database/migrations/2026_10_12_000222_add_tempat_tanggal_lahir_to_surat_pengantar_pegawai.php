<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tempat & tanggal lahir pada baris pegawai Surat Pengantar — mengikuti
     * format dokumen naskah dinas yang mencantumkan keduanya di blok detail
     * pegawai. Disnapshot saat surat dibuat, sama seperti nama/jabatan.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar_pegawai', function (Blueprint $table) {
            $table->string('tempat_lahir', 255)->nullable()->after('nama');
            $table->string('tanggal_lahir', 255)->nullable()->after('tempat_lahir');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar_pegawai', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tanggal_lahir']);
        });
    }
};
