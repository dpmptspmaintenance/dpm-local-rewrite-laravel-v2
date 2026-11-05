<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data dukung TPP: daftar "Data Pegawai Tidak Masuk Kerja" dari
     * tpp.semarangkota.go.id/izin. Data masuk lewat API (Chrome extension);
     * tidak ada impor PDF dan tidak ada berkas yang disimpan di server.
     *
     * Terpisah dari data dukung absen (rekap_absen_data_dukung). Hanya baris
     * yang file pendukungnya sudah diunggah (file_pendukung_status = 'ada')
     * yang menutup baris KONFIRMASI di rekap absen.
     *
     * Lives on the `kepegawaian` connection.
     */
    public function up(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('rekap_absen_tpp')) {
            $schema->create('rekap_absen_tpp', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('id_tpp')->nullable();
                $table->string('nip', 30);
                $table->string('nama', 150);
                $table->string('golongan', 50)->nullable();
                $table->string('eselon', 50)->nullable();
                $table->string('skpd', 255)->nullable();
                $table->string('unit_kerja', 20)->nullable();
                $table->string('jenis', 100)->nullable();
                $table->text('keterangan')->nullable();
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->unsignedSmallInteger('jumlah_hari')->default(0);
                $table->unsignedTinyInteger('bulan');
                $table->unsignedSmallInteger('tahun');
                $table->string('file_pendukung_status', 10)->nullable();
                $table->string('file_pendukung_nama', 255)->nullable();
                $table->string('file_pendukung_url', 500)->nullable();
                $table->string('sumber', 20)->nullable();
                $table->string('sumber_label', 100)->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->unsignedBigInteger('imported_by')->nullable();
                $table->string('imported_by_nama', 150)->nullable();
                $table->timestamps();

                $table->index(['nip', 'tahun', 'bulan']);
                $table->index(['tahun', 'bulan']);
                $table->index('id_tpp');
            });
        }

        if (! $schema->hasTable('rekap_absen_tpp_tanggal')) {
            $schema->create('rekap_absen_tpp_tanggal', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('rekap_absen_tpp_id')->constrained('rekap_absen_tpp')->cascadeOnDelete();
                $table->date('tanggal');
                $table->timestamps();

                $table->unique(['rekap_absen_tpp_id', 'tanggal']);
                $table->index('tanggal');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('kepegawaian');

        $schema->dropIfExists('rekap_absen_tpp_tanggal');
        $schema->dropIfExists('rekap_absen_tpp');
    }
};
