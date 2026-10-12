<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generator Surat Pengantar (template .docx berplaceholder ${...},
     * resources/templates/kepegawaian/surat-pengantar.docx). Satu surat =
     * satu tujuan + satu/lebih pegawai (daftar detail pegawai di-clone otomatis).
     *
     * Connection `kepegawaian`, pola sama surat_tugas: no FK lintas database,
     * dibuat_oleh cuma snapshot id+nama. Dasar hukum disimpan sebagai snapshot
     * (default Setting + tambahan per-surat) supaya riwayat tetap akurat.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('surat_pengantar', function (Blueprint $table) {
            $table->id();
            $table->string('perihal', 500);
            $table->string('tujuan', 500);
            $table->string('tujuan_kota', 255)->nullable();
            $table->string('lampiran', 255)->nullable();

            // Paragraf bebas yang mengapit daftar pegawai & menutup surat.
            $table->text('paragraf_pembuka')->nullable();
            $table->text('paragraf_penutup')->nullable();

            // Tembusan (satu baris per tembusan), snapshot.
            $table->text('tembusan_snapshot')->nullable();

            // Blok tanda tangan di dokumen (baris jabatan + nama + NIP).
            $table->string('ttd_baris_1', 255)->nullable();
            $table->string('ttd_baris_2', 255)->nullable();
            $table->string('ttd_baris_3', 255)->nullable();
            $table->string('ttd_nama', 255)->nullable();
            $table->string('ttd_nip', 100)->nullable();

            // Snapshot dasar hukum final (setting default + tambahan per-surat).
            $table->text('dasar_hukum_snapshot')->nullable();
            $table->text('dasar_hukum_tambahan')->nullable();

            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->string('dibuat_oleh_nama', 150)->nullable();
            $table->timestamps();
        });

        Schema::connection('kepegawaian')->create('surat_pengantar_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_pengantar_id')->constrained('surat_pengantar')->cascadeOnDelete();
            $table->string('nip', 100);
            // Snapshot nama/jabatan/pangkat-golongan saat surat dibuat.
            $table->string('nama', 255);
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
        Schema::connection('kepegawaian')->dropIfExists('surat_pengantar_pegawai');
        Schema::connection('kepegawaian')->dropIfExists('surat_pengantar');
    }
};
