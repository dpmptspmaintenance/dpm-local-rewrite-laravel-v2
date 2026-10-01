<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat penghargaan pegawai (`riwayat_penghargaan` di JSON scrape
     * SISDM — Satyalancana Karya Satya dan sejenisnya), sumber terpisah dari
     * bucket 10/20/30 tahun yang dihitung dari NIP (PenghargaanMasaKerja).
     * Tabel ini murni data mentah hasil impor, ditampilkan sebagai riwayat
     * resmi di detail pegawai — tidak dipakai untuk hitungan bucket.
     *
     * Lives on the `kepegawaian` connection, same pattern as pegawai_anak /
     * pegawai_kompetensi (full-snapshot replace per NIP on every import, no
     * FK to pegawai_profil).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('pegawai_penghargaan', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100);
            $table->unsignedSmallInteger('no_urut')->nullable();
            $table->string('jenis_penghargaan', 255)->nullable();
            $table->string('nama_penghargaan', 255)->nullable();
            $table->string('asal_perolehan_penghargaan', 255)->nullable();
            $table->string('peringkat_penghargaan', 100)->nullable();
            $table->string('nomor_sk_penghargaan', 100)->nullable();
            $table->date('tanggal_sk_penghargaan')->nullable();
            $table->text('file_penghargaan_url')->nullable();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('pegawai_penghargaan');
    }
};
