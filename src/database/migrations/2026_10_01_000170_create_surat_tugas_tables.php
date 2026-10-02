<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generator Surat Tugas (template .docx dengan placeholder ${...},
     * resources/templates/kepegawaian/surat-tugas.docx — lihat progress.md
     * untuk asal-usul & konversi MERGEFIELD→${...} dari file contoh asli).
     *
     * Lives on the `kepegawaian` connection, pola sama pegawai_arsip/
     * pegawai_penghargaan — no FK ke database `mysql` (uploaded_by/dibuat_oleh
     * cuma snapshot id+nama user, bukan constraint lintas database).
     *
     * nomor_naskah/tanggal_naskah/ttd_pengirim SENGAJA tidak ada kolomnya di
     * sini — field itu dibiarkan sebagai placeholder literal "${...}" di
     * dokumen hasil (diisi nanti di aplikasi Srikandi saat registrasi naskah
     * dinas resmi), bukan tanggung jawab tool ini.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('surat_tugas_dasar_hukum_setting', function (Blueprint $table) {
            $table->id();
            $table->text('teks');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::connection('kepegawaian')->create('surat_tugas', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 500);
            $table->string('hari_tanggal', 255);
            $table->string('waktu', 255);
            $table->string('tempat', 255);
            // Snapshot dasar hukum final (setting default + tambahan per-surat
            // yang berlaku SAAT digenerate) — satu teks gabungan multi-baris,
            // supaya riwayat tetap akurat walau Setting default diedit belakangan.
            $table->text('dasar_hukum_snapshot')->nullable();
            $table->text('dasar_hukum_tambahan')->nullable();
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->string('dibuat_oleh_nama', 150)->nullable();
            $table->timestamps();
        });

        Schema::connection('kepegawaian')->create('surat_tugas_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_tugas_id')->constrained('surat_tugas')->cascadeOnDelete();
            $table->string('nip', 100);
            // Snapshot nama/jabatan/pangkat-golongan pada saat surat dibuat —
            // supaya riwayat lama tidak berubah kalau data pegawai di-update
            // belakangan (pola sama uploaded_by_nama di pegawai_arsip).
            $table->string('nama', 255);
            $table->string('jabatan', 255)->nullable();
            $table->string('pangkat_golongan', 255)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('surat_tugas_pegawai');
        Schema::connection('kepegawaian')->dropIfExists('surat_tugas');
        Schema::connection('kepegawaian')->dropIfExists('surat_tugas_dasar_hukum_setting');
    }
};
