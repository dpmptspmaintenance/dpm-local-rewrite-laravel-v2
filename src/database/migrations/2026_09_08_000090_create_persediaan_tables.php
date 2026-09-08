<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tables live on the dedicated `persediaan` connection (separate physical
     * database, mirroring the legacy `dpmptsp-local/persediaan` module which
     * used its own mysqli connection). See config/database.php.
     */
    public function up(): void
    {
        Schema::connection('persediaan')->create('master_satuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_satuan', 50);
            $table->boolean('is_active')->default(true);
            $table->unique('nama_satuan');
        });

        Schema::connection('persediaan')->create('master_rekening', function (Blueprint $table) {
            $table->id();
            $table->string('kode_rekening', 50);
            $table->string('nama_rekening', 255);
            $table->string('parent_kode', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->unique('kode_rekening');
        });

        Schema::connection('persediaan')->create('master_barang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_rekening', 50);
            $table->string('nama_barang', 255);
            $table->unsignedInteger('id_satuan');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('id_satuan');
            $table->index('kode_rekening');
        });

        Schema::connection('persediaan')->create('penguncian_laporan', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun');
            $table->integer('bulan');
            $table->boolean('is_locked')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['bulan', 'tahun'], 'idx_bulan_tahun');
        });

        Schema::connection('persediaan')->create('transaksi_header', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 50);
            $table->string('no_bukti', 100)->nullable();
            $table->enum('jenis_mutasi', ['saldo_awal', 'masuk', 'keluar']);
            $table->enum('status', ['draft', 'menunggu', 'disetujui', 'ditolak'])->default('draft');
            $table->text('lampiran')->nullable();
            $table->date('tanggal_transaksi');
            $table->string('pihak_terkait', 255)->nullable();
            $table->text('alasan_pengambilan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('ttd_kiri', 255)->nullable();
            $table->string('ttd_tengah', 255)->nullable();
            $table->string('ttd_kanan', 255)->nullable();
            $table->string('ttd_sekretaris', 255)->default('Anton Siswartono, S.Sos, M.M');
            $table->string('ttd_bmd', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique('kode_transaksi');
        });

        Schema::connection('persediaan')->create('transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_header');
            $table->unsignedInteger('id_barang');
            $table->integer('qty');
            $table->decimal('harga_satuan', 15, 2);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index('id_barang');
            $table->index('id_header');
        });

        Schema::connection('persediaan')->create('stok_opname_header', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_opname', 50);
            $table->date('tanggal_opname');
            $table->string('nama_kegiatan', 255)->default('Stok Opname Insidental');
            $table->string('ttd_kiri', 255)->default('Naelu Shulhal Majid, A.Md.Ak');
            $table->string('ttd_tengah', 255)->default('Nany Marlina, SE');
            $table->string('ttd_kanan', 255)->default('');
            $table->string('ttd_sekretaris', 255)->default('Anton Siswartono, S.Sos, M.M');
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique('kode_opname');
        });

        Schema::connection('persediaan')->create('stok_opname_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_opname_header');
            $table->unsignedInteger('id_barang');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->integer('stok_sistem')->default(0);
            $table->integer('stok_fisik')->default(0);
            $table->integer('selisih')->default(0);
            $table->string('alasan_selisih', 255)->nullable();
            $table->index('id_opname_header');
            $table->foreign('id_opname_header')->references('id')->on('stok_opname_header')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('persediaan')->dropIfExists('stok_opname_detail');
        Schema::connection('persediaan')->dropIfExists('stok_opname_header');
        Schema::connection('persediaan')->dropIfExists('transaksi_detail');
        Schema::connection('persediaan')->dropIfExists('transaksi_header');
        Schema::connection('persediaan')->dropIfExists('penguncian_laporan');
        Schema::connection('persediaan')->dropIfExists('master_barang');
        Schema::connection('persediaan')->dropIfExists('master_rekening');
        Schema::connection('persediaan')->dropIfExists('master_satuan');
    }
};
