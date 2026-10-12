<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surat Pengantar Kenaikan Pangkat — generator .docx
     * (resources/templates/kepegawaian/surat-pengantar-kp.docx, kop di-reuse
     * dari template surat-tugas). Satu tujuan + satu/lebih pegawai (blok
     * detail pegawai di-clone otomatis), pola sama surat_pengantar.
     *
     * Connection `kepegawaian`, tanpa FK lintas database.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('surat_pengantar_kp', function (Blueprint $table) {
            $table->id();
            $table->string('tujuan', 500);
            $table->string('tujuan_kota', 255)->nullable();
            $table->string('tempat_tanggal', 255)->nullable();

            $table->text('paragraf_pembuka')->nullable();
            $table->text('paragraf_penutup')->nullable();

            $table->text('tembusan')->nullable();

            $table->string('ttd_baris_1', 255)->nullable();
            $table->string('ttd_baris_2', 255)->nullable();
            $table->string('ttd_baris_3', 255)->nullable();
            $table->string('ttd_nama', 255)->nullable();
            $table->string('ttd_nip', 100)->nullable();

            $table->text('dasar_hukum_tambahan')->nullable();

            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->string('dibuat_oleh_nama', 150)->nullable();
            $table->timestamps();
        });

        Schema::connection('kepegawaian')->create('surat_pengantar_kp_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_pengantar_kp_id')->constrained('surat_pengantar_kp')->cascadeOnDelete();
            $table->string('nip', 100);
            $table->string('nama', 255);
            $table->string('tempat_lahir', 255)->nullable();
            $table->string('tanggal_lahir', 255)->nullable();
            $table->string('jabatan', 255)->nullable();
            $table->string('pangkat_golongan', 255)->nullable();
            $table->string('instansi', 255)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('surat_pengantar_kp_pegawai');
        Schema::connection('kepegawaian')->dropIfExists('surat_pengantar_kp');
    }
};
