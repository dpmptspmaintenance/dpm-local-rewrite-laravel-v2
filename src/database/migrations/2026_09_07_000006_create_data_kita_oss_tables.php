<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('2023_dp_nib_kantor', function (Blueprint $table) {
            $table->id();
            $table->string('nib', 20)->nullable()->index('idx_nib_kantor');
            $table->date('day_of_tanggal_terbit_oss')->nullable();
            $table->string('nama_perusahaan')->nullable();
            $table->string('status_penanaman_modal', 50)->nullable();
            $table->string('uraian_jenis_perusahaan')->nullable();
            $table->string('uraian_skala_usaha', 50)->nullable();
            $table->text('alamat_perusahaan')->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kab_kota', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('nomor_telp', 50)->nullable();
            $table->string('flag', 50)->nullable();
            $table->year('tahun_pengambilan_data')->nullable();
            $table->string('bulan_pengambilan_data', 2)->nullable();
            $table->string('hari_pengambilan_data', 2)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('2023_dp_proyek', function (Blueprint $table) {
            $table->id();
            $table->string('id_proyek', 50)->nullable()->index('idx_id_proyek');
            $table->string('uraian_jenis_proyek')->nullable();
            $table->string('nib', 20)->nullable()->index('idx_proyek_nib');
            $table->string('nama_perusahaan')->nullable();
            $table->date('tanggal_terbit_oss')->nullable();
            $table->string('uraian_status_penanaman_modal', 100)->nullable();
            $table->string('uraian_jenis_perusahaan')->nullable();
            $table->string('uraian_risiko_proyek', 100)->nullable();
            $table->string('nama_proyek')->nullable();
            $table->string('uraian_skala_usaha', 50)->nullable();
            $table->text('alamat_usaha')->nullable();
            $table->string('kab_kota_kantor_pusat', 100)->nullable();
            $table->string('kecamatan_usaha', 100)->nullable();
            $table->string('kelurahan_usaha', 100)->nullable();
            $table->date('day_of_tanggal_pengajuan_proyek')->nullable();
            $table->string('kbli', 10)->nullable();
            $table->string('judul_kbli')->nullable();
            $table->string('sektor_pembina', 100)->nullable();
            $table->string('nama_user', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('nomor_telp', 50)->nullable();
            $table->decimal('luas_tanah', 15, 2)->nullable();
            $table->string('satuan_tanah', 20)->nullable();
            $table->decimal('jumlah_investasi3', 20, 2)->nullable();
            $table->integer('tki')->nullable();
            $table->date('tanggal_proyek')->nullable();
            $table->year('tahun_pengambilan_data')->nullable();
            $table->string('bulan_pengambilan_data', 2)->nullable();
            $table->string('hari_pengambilan_data', 2)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('2023_list_izin', function (Blueprint $table) {
            $table->id();
            $table->string('id_permohonan_izin', 50)->nullable()->index('idx_id_permohonan');
            $table->string('nama_perusahaan')->nullable();
            $table->string('nib', 20)->nullable();
            $table->date('day_of_tanggal_terbit_oss')->nullable();
            $table->string('uraian_status_penanaman_modal', 50)->nullable();
            $table->string('propinsi', 100)->nullable();
            $table->string('kab_kota', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('id_proyek', 50)->nullable()->index('idx_izin_proyek_id');
            $table->string('kd_resiko', 10)->nullable();
            $table->string('kbli', 10)->nullable();
            $table->date('day_of_tanggal_izin')->nullable();
            $table->string('uraian_jenis_perizinan')->nullable();
            $table->string('nama_dokumen')->nullable();
            $table->string('uraian_kewenangan')->nullable();
            $table->string('uraian_status_respon', 50)->nullable();
            $table->string('kewenangan', 100)->nullable();
            $table->string('kl_sektor', 100)->nullable();
            $table->year('tahun_pengambilan_data')->nullable();
            $table->string('bulan_pengambilan_data', 2)->nullable();
            $table->string('hari_pengambilan_data', 2)->nullable();
            $table->date('tanggal_permohonan')->nullable();
            $table->date('tanggal_proyek')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('dasi_master_status_penanaman_modal', function (Blueprint $table) {
            $table->id('Id');
            $table->string('kode_sandal', 4)->nullable();
            $table->string('nama_sandal', 30)->nullable();
        });

        Schema::create('dasi_master_jenis_perusahaan', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Uraian', 100)->nullable();
            $table->integer('is_aktif')->default(0);
        });

        Schema::create('dasi_master_skala_usaha', function (Blueprint $table) {
            $table->id();
            $table->string('skala_usaha', 100);
            $table->boolean('is_aktif')->default(true);
        });

        Schema::create('dasi_master_kecamatan', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Kecamatan', 50)->nullable();
        });

        Schema::create('dasi_data_pembina', function (Blueprint $table) {
            $table->id();
            $table->string('pembina');
            $table->boolean('is_aktif')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dasi_data_pembina');
        Schema::dropIfExists('dasi_master_kecamatan');
        Schema::dropIfExists('dasi_master_skala_usaha');
        Schema::dropIfExists('dasi_master_jenis_perusahaan');
        Schema::dropIfExists('dasi_master_status_penanaman_modal');
        Schema::dropIfExists('2023_list_izin');
        Schema::dropIfExists('2023_dp_proyek');
        Schema::dropIfExists('2023_dp_nib_kantor');
    }
};
