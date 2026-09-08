<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simbg_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi', 100);
            $table->string('no_dokumen', 200)->nullable();
            $table->string('jenis_permohonan')->nullable();
            $table->string('jenis_registrasi', 50)->nullable();
            $table->string('nama_pemilik')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota_kab_bangunan', 100)->nullable();
            $table->string('kecamatan_bangunan', 100)->nullable();
            $table->string('kelurahan_bangunan', 100)->nullable();
            $table->text('alamat_pemilik')->nullable();
            $table->string('no_kontak', 50)->nullable();
            $table->string('e_mail', 150)->nullable();
            $table->string('no_identitas', 50)->nullable();
            $table->string('nama_bangunan')->nullable();
            $table->string('fungsi_bangunan', 100)->nullable();
            $table->string('sub_fungsi_bangunan')->nullable();
            $table->string('tipe_bangunan', 100)->nullable();
            $table->decimal('luas_bangunan', 15, 2)->default(0.00);
            $table->decimal('tinggi_bangunan', 15, 2)->default(0.00);
            $table->decimal('luas_basement', 15, 2)->default(0.00);
            $table->integer('jumlah_lantai')->default(0);
            $table->integer('lapis_basement')->default(0);
            $table->integer('jumlah_unit')->default(0);
            $table->string('okupansi', 100)->nullable();
            $table->string('permanensi', 50)->nullable();
            $table->string('status', 100)->nullable();
            $table->string('status_slf', 100)->nullable();
            $table->string('fungsi', 100)->nullable();
            $table->string('tipe_konsultasi_1', 100)->nullable();
            $table->string('tipe_konsultasi_2', 100)->nullable();
            $table->date('tgl_registrasi')->nullable();
            $table->date('tgl_sk')->nullable();
            $table->tinyInteger('is_sync')->default(0);
            $table->year('tahun_pengambilan_data')->nullable();
            $table->string('bulan_pengambilan_data', 2)->nullable();
            $table->string('hari_pengambilan_data', 2)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique('no_registrasi', 'idx_no_registrasi');
            $table->index('nama_pemilik', 'idx_nama_pemilik');
            $table->index('no_dokumen', 'idx_no_dokumen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simbg_monitoring');
    }
};
