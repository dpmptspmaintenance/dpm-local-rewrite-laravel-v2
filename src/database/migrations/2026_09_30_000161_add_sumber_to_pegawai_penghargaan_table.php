<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bedakan baris hasil impor JSON SISDM dari baris yang ditambah manual
     * lewat halaman "Daftar Penghargaan" — perlu karena PegawaiImportService
     * melakukan full-snapshot replace per NIP setiap impor. Tanpa kolom ini,
     * baris manual (dibuat karena staf malas mengisi SIMPATIK/SISDM) akan
     * ikut terhapus begitu pegawai yang sama di-reimport. Dengan kolom ini,
     * impor HANYA menghapus-ganti baris sumber='impor' — baris 'manual'
     * dibiarkan apa adanya selamanya.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('pegawai_penghargaan', function (Blueprint $table) {
            $table->string('sumber', 10)->default('impor')->after('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('pegawai_penghargaan', function (Blueprint $table) {
            $table->dropColumn('sumber');
        });
    }
};
