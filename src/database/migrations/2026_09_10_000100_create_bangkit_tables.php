<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bangkit (legacy "sekre" / "klinik" CodeIgniter) module tables.
     *
     * Lives on the dedicated `bangkit` connection — separate physical database,
     * mirroring the legacy app which pointed its own mysqli connection at a
     * standalone "sekre" DB. See config/database.php.
     *
     * Column names are preserved VERBATIM from the legacy schema (reverse-
     * engineered from controllers/views since no dump exists for most tables):
     *   - capital `Id` primary keys
     *   - `isaktif` (no underscore) on sekre_bangkit_user vs `is_aktif` elsewhere
     *   - capital `Jenis` on sekre_bangkit_data_barang
     *   - `pagu_anggaran` (canonical; legacy code also had a "pagu_anggarang"
     *     typo in a couple of spots)
     *
     * Legacy declared NO foreign-key constraints (soft-delete via is_aktif,
     * loose/mixed id references). We mirror that: plain indexes on FK-like
     * columns, no DB-level foreign() constraints, to avoid over-constraining.
     */
    public function up(): void
    {
        Schema::connection('bangkit')->create('sekre_bangkit_user', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('username', 255)->unique();
            $table->string('password', 255); // legacy md5 (32 hex); width kept generous
            $table->unsignedTinyInteger('role')->nullable(); // 1 superadmin,2 kadin,3 sekdin,4 kasubag,5 bendahara,6 user
            $table->unsignedInteger('bidang')->nullable(); // -> katkit_bidang.Id
            $table->unsignedInteger('id_pegawai')->nullable(); // -> sekre_pegawai_kekuatan.Id
            $table->boolean('isaktif')->default(true); // verbatim: no underscore
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('modified_at')->nullable();
        });

        Schema::connection('bangkit')->create('sekre_bangkit_role', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('role_name', 255);
        });

        Schema::connection('bangkit')->create('katkit_bidang', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('nama_bidang', 255);
        });

        Schema::connection('bangkit')->create('sekre_pegawai_kekuatan', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('nama', 255);
        });

        // Ground truth from sql/sekre_pegawai_duk.sql (only real dump available).
        Schema::connection('bangkit')->create('sekre_pegawai_duk', function (Blueprint $table) {
            $table->increments('Id');
            $table->integer('urutan_duk')->nullable();
            $table->string('nama', 255)->nullable();
            $table->string('nip', 255)->nullable();
            $table->string('gol', 5)->nullable();
            $table->date('tmt')->nullable();
            $table->string('gol_cpns', 5)->nullable();
            $table->date('tmt_cpns')->nullable();
            $table->string('jabatan', 255)->nullable();
            $table->string('eselon', 10)->nullable();
            $table->integer('masa_kerja_tahun')->nullable();
            $table->integer('masa_kerja_bulan')->nullable();
            $table->string('pendidikan', 255)->nullable();
            $table->string('tahun_periode_data', 4)->nullable();
            $table->string('bulan_periode_data', 2)->nullable();
        });

        Schema::connection('bangkit')->create('sekre_bangkit_master_lokasi', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('lokasi', 255);
            $table->string('keterangan', 255)->nullable();
        });

        Schema::connection('bangkit')->create('sekre_bangkit_master_keadaan_barang', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('keadaan_barang', 255);
        });

        Schema::connection('bangkit')->create('sekre_bangkit_master_jenis_barang', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('jenis_barang', 255);
        });

        Schema::connection('bangkit')->create('sekre_bangkit_master_bahan_barang', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('bahan', 255);
            $table->string('bahan_bakar', 255)->nullable();
        });

        Schema::connection('bangkit')->create('sekre_bangkit_master_verifikasi_permohonan_perbaikan', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('verifikasi', 255);
        });

        Schema::connection('bangkit')->create('sekre_bangkit_data_barang', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('kode_barang', 255)->nullable();
            $table->string('register', 255)->nullable();
            $table->string('kode_barang_register', 255)->nullable();
            $table->string('nama_barang', 255);
            $table->string('merk_type', 255)->nullable();
            $table->unsignedInteger('Jenis')->nullable(); // verbatim capital J -> master_jenis_barang.Id
            $table->unsignedInteger('bahan')->nullable(); // -> master_bahan_barang.Id
            $table->unsignedInteger('keadaan_barang')->nullable(); // -> master_keadaan_barang.Id
            $table->unsignedInteger('lokasi')->nullable(); // -> master_lokasi.Id
            $table->unsignedInteger('pemegang')->nullable(); // ambiguous: user.Id or pegawai_kekuatan.Id
            $table->string('tahun_pembelian', 4)->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('link_foto', 255)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('modified_by', 255)->nullable(); // legacy isi teks "direct database"
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('modified_at')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->index('Jenis');
            $table->index('bahan');
            $table->index('keadaan_barang');
            $table->index('lokasi');
            $table->index('pemegang');
        });

        Schema::connection('bangkit')->create('sekre_bangkit_history_data_barang', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_barang'); // -> data_barang.Id
            $table->unsignedInteger('pemegang_lama')->nullable();
            $table->unsignedInteger('pemegang_baru')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unsignedInteger('modified_by')->nullable();
            $table->index('id_barang');
        });

        Schema::connection('bangkit')->create('sekre_bangkit_data_permohonan_perbaikan', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_barang'); // -> data_barang.Id
            $table->string('kode_barang_register', 255)->nullable();
            $table->text('uraian_kerusakan')->nullable();
            $table->date('tanggal_permohonan')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->boolean('is_aktif')->default(true);
            $table->unsignedInteger('modified_by')->nullable();
            $table->string('perbaikan_ke', 50)->nullable();
            // 3-stage approval chain: 0=belum, 1=disetujui, 2=ditolak, null/other=ditunggu
            $table->unsignedTinyInteger('verifikasi_b_barang')->nullable();
            $table->date('tanggal_verifikasi_b_barang')->nullable();
            $table->text('penjelasan_b_barang')->nullable();
            $table->unsignedTinyInteger('verifikasi_umpeg')->nullable();
            $table->date('tanggal_verifikasi_umpeg')->nullable();
            $table->text('penjelasan_umpeg')->nullable();
            $table->unsignedTinyInteger('verifikasi_sekdin')->nullable();
            $table->date('tanggal_verifikasi_sekdin')->nullable();
            $table->text('penjelasan_sekdin')->nullable();
            $table->decimal('pagu_anggaran', 15, 2)->nullable();
            $table->date('tanggal_pengerjaan')->nullable();
            $table->string('pengerjaan_oleh', 255)->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('serah_terima_oleh', 255)->nullable();
            $table->decimal('biaya', 15, 2)->nullable();
            $table->date('spj_tanggal')->nullable();
            $table->text('uraian_perbaikan')->nullable();
            $table->index('id_barang');
        });

        Schema::connection('bangkit')->create('sdia_kegiatan', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_katkit_bidang')->nullable(); // -> katkit_bidang.Id
            $table->integer('tahun')->nullable();
            $table->string('nama_kegiatan', 255);
            $table->boolean('is_aktif')->default(true);
            $table->unsignedInteger('modified_by')->nullable();
            $table->index('id_katkit_bidang');
        });

        Schema::connection('bangkit')->create('sdia_klas_persediaan', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('rek_klas', 255); // classification code string
            $table->string('nama_klas', 255);
            $table->boolean('is_aktif')->default(true);
        });

        Schema::connection('bangkit')->create('sdia_anggaran', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_katkit_bidang')->nullable();
            $table->unsignedInteger('id_sdia_kegiatan')->nullable(); // -> sdia_kegiatan.Id
            $table->unsignedInteger('rek_klas')->nullable(); // -> sdia_klas_persediaan.Id
            $table->decimal('anggaran', 15, 2)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('modified_at')->nullable();
            $table->string('modified_by', 255)->nullable(); // legacy stores username here (not id)
            $table->index('id_sdia_kegiatan');
            $table->index('rek_klas');
        });

        Schema::connection('bangkit')->create('sdia_data_dpa', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_katkit_bidang')->nullable();
            $table->integer('tahun')->nullable();
            $table->unsignedInteger('id_sdia_kegiatan')->nullable(); // -> sdia_kegiatan.Id
            $table->unsignedInteger('rek_klas')->nullable(); // -> sdia_klas_persediaan.Id
            $table->string('nama_barang', 255);
            $table->decimal('jumlah_barang', 15, 2)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->unsignedInteger('modified_by')->nullable();
            $table->index('id_sdia_kegiatan');
            $table->index('rek_klas');
        });

        Schema::connection('bangkit')->create('sdia_transaksi_barang', function (Blueprint $table) {
            $table->increments('Id');
            $table->date('tgl_transaksi')->nullable();
            $table->string('klas_transaksi', 255)->nullable(); // 'Barang Masuk' | 'Barang Keluar'
            $table->unsignedInteger('id_sdia_data_dpa')->nullable(); // -> sdia_data_dpa.Id
            $table->unsignedInteger('id_sdia_kegiatan')->nullable(); // -> sdia_kegiatan.Id
            $table->decimal('jumlah_transaksi', 15, 2)->nullable();
            $table->decimal('harga_satuan', 15, 2)->nullable();
            $table->unsignedInteger('id_katkit_bidang')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->index('id_sdia_data_dpa');
            $table->index('id_sdia_kegiatan');
        });

        Schema::connection('bangkit')->create('sdia_bulanan', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_katkit_bidang')->nullable();
            $table->integer('tahun')->nullable();
            $table->string('bulan', 2)->nullable(); // zero-padded '01'..'12'
            $table->unsignedInteger('id_transaksi_barang_masuk')->nullable(); // -> sdia_transaksi_barang.Id
            $table->unsignedInteger('id_transaksi_barang_keluar')->nullable();
            $table->decimal('saldo_awal', 15, 2)->nullable();
            $table->decimal('saldo_masuk', 15, 2)->nullable();
            $table->decimal('harga_satuan', 15, 2)->nullable();
            $table->decimal('saldo_keluar', 15, 2)->nullable();
            $table->decimal('saldo_akhir', 15, 2)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unsignedInteger('modified_by')->nullable();
            $table->index('id_transaksi_barang_masuk');
            $table->index('id_transaksi_barang_keluar');
        });

        Schema::connection('bangkit')->create('sdia_anggaran_bulanan', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('id_katkit_bidang')->nullable();
            $table->unsignedInteger('id_sdia_kegiatan')->nullable();
            $table->unsignedInteger('rek_klas')->nullable();
            $table->unsignedInteger('id_anggaran')->nullable(); // -> sdia_anggaran.Id
            $table->integer('tahun')->nullable();
            $table->string('bulan', 2)->nullable();
            $table->decimal('anggaran_awal', 15, 2)->nullable();
            $table->decimal('anggaran_terpakai', 15, 2)->nullable();
            $table->decimal('sisa_anggaran', 15, 2)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('modified_at')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->index('id_anggaran');
        });

        Schema::connection('bangkit')->create('sekre_bangkit_lambenan', function (Blueprint $table) {
            $table->increments('Id');
            $table->text('nglambe')->nullable();
            $table->string('lambene_sopo', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_lambenan');
        Schema::connection('bangkit')->dropIfExists('sdia_anggaran_bulanan');
        Schema::connection('bangkit')->dropIfExists('sdia_bulanan');
        Schema::connection('bangkit')->dropIfExists('sdia_transaksi_barang');
        Schema::connection('bangkit')->dropIfExists('sdia_data_dpa');
        Schema::connection('bangkit')->dropIfExists('sdia_anggaran');
        Schema::connection('bangkit')->dropIfExists('sdia_klas_persediaan');
        Schema::connection('bangkit')->dropIfExists('sdia_kegiatan');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_data_permohonan_perbaikan');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_history_data_barang');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_data_barang');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_master_verifikasi_permohonan_perbaikan');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_master_bahan_barang');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_master_jenis_barang');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_master_keadaan_barang');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_master_lokasi');
        Schema::connection('bangkit')->dropIfExists('sekre_pegawai_duk');
        Schema::connection('bangkit')->dropIfExists('sekre_pegawai_kekuatan');
        Schema::connection('bangkit')->dropIfExists('katkit_bidang');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_role');
        Schema::connection('bangkit')->dropIfExists('sekre_bangkit_user');
    }
};
