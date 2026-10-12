<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat KGB (Kenaikan Gaji Berkala) dari JSON impor SISDM — ringkasan
     * per pegawai (kolom kgb_* di pegawai_profil) + detail baris per kenaikan
     * (tabel pegawai_riwayat_kgb). Pola sama tabel kepegawaian lain: koneksi
     * `kepegawaian`, no FK, no timestamps, full-snapshot replace per NIP tiap
     * impor (lihat PegawaiImportService::importOne()).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('pegawai_profil', function (Blueprint $table) {
            $table->string('kgb_gaji_pokok', 50)->nullable()->after('kgb_selanjutnya');
            $table->string('kgb_masa_kerja_saat', 50)->nullable()->after('kgb_gaji_pokok');
            $table->string('kgb_masa_kerja_terakhir', 50)->nullable()->after('kgb_masa_kerja_saat');
            $table->date('kgb_tmt_golongan_terakhir')->nullable()->after('kgb_masa_kerja_terakhir');
        });

        Schema::connection('kepegawaian')->create('pegawai_riwayat_kgb', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100);
            $table->unsignedInteger('no_urut')->nullable();
            $table->text('file_kgb_url')->nullable();
            $table->string('gaji_baru', 50)->nullable();
            $table->string('gaji_lama', 50)->nullable();
            $table->string('golongan_kgb', 50)->nullable();
            $table->string('masa_kerja_kgb', 50)->nullable();
            $table->string('nomor_sk_kgb', 255)->nullable();
            $table->date('tanggal_sk_kgb')->nullable();
            $table->date('tmt_kgb')->nullable();
            $table->string('unit_kerja_kgb', 255)->nullable();
            $table->string('verifikasi', 50)->nullable();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('pegawai_riwayat_kgb');

        Schema::connection('kepegawaian')->table('pegawai_profil', function (Blueprint $table) {
            $table->dropColumn([
                'kgb_gaji_pokok',
                'kgb_masa_kerja_saat',
                'kgb_masa_kerja_terakhir',
                'kgb_tmt_golongan_terakhir',
            ]);
        });
    }
};
