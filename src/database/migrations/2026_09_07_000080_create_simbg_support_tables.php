<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simbg_penyerahan_dokumen_pbg', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi', 100);
            $table->string('jenis_permohonan')->nullable();
            $table->date('tgl_registrasi')->nullable();
            $table->string('no_dokumen_pbg', 100)->nullable();
            $table->date('tgl_dokumen_pbg')->nullable();
            $table->string('nama_pemilik')->nullable();
            $table->string('dokumen')->nullable();
            $table->string('status', 100)->nullable();
            $table->date('tgl_ambil_data')->nullable();
            $table->time('jam_ambil_data')->nullable();
            $table->date('tgl_pengambilan_sk')->nullable();
            $table->string('nama_pengambil_sk')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique('no_registrasi', 'idx_pbg_noreg');
        });

        Schema::create('simbg_data_tambahan', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi', 100);
            $table->string('nik', 20)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('hak_atas_tanah')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique('no_registrasi', 'idx_add_noreg');
            $table->foreign('no_registrasi', 'fk_data_tambahan')
                ->references('no_registrasi')
                ->on('simbg_penyerahan_dokumen_pbg')
                ->onDelete('cascade');
        });

        Schema::create('simbg_master_jenis_regist', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_registrasi', 100);
            $table->string('uraian_jenis_registrasi');
            $table->tinyInteger('is_aktif')->default(1);
        });

        Schema::create('simbg_master_jenis_konsultasi', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_konsultasi', 100);
            $table->tinyInteger('is_aktif')->default(1);
        });

        Schema::create('simbg_master_fungsi_bangunan_gedung', function (Blueprint $table) {
            $table->id();
            $table->string('fungsi_bg', 100);
            $table->tinyInteger('is_aktif')->default(1);
        });

        Schema::create('datakita_validasi_pembayaran_retribusi_pbg', function (Blueprint $table) {
            $table->id();
            $table->string('bulan', 50)->nullable();
            $table->date('tanggal_validasi')->nullable();
            $table->string('no_registrasi', 100)->nullable();
            $table->string('nama_pemilik')->nullable();
            $table->string('lokasi_bangunan')->nullable();
            $table->string('id_biling', 100)->nullable();
            $table->date('tgl_bayar')->nullable();
            $table->decimal('nominal', 20, 2)->nullable();
            $table->string('petugas', 150)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datakita_validasi_pembayaran_retribusi_pbg');
        Schema::dropIfExists('simbg_master_fungsi_bangunan_gedung');
        Schema::dropIfExists('simbg_master_jenis_konsultasi');
        Schema::dropIfExists('simbg_master_jenis_regist');
        Schema::dropIfExists('simbg_data_tambahan');
        Schema::dropIfExists('simbg_penyerahan_dokumen_pbg');
    }
};
