<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DRH (Daftar Riwayat Hidup) untuk usulan Tanda Kehormatan Satya Lancana
     * Karya Satya — satu baris per dokumen yang pernah digenerate, pola sama
     * `surat_tugas` (riwayat disimpan di DB, bukan stateless).
     *
     * Lives on the `kepegawaian` connection — no FK ke database `mysql`
     * (dibuat_oleh cuma snapshot id+nama user, bukan constraint lintas database).
     *
     * Kolom identitas (nama/nip/tempat-tanggal lahir/golongan/jabatan/gender)
     * di-snapshot dari pegawai_profil SAAT dibuat — supaya DRH lama tak ikut
     * berubah kalau data pegawai di-update belakangan (pola sama
     * surat_tugas_pegawai). Field yang tak ada di pegawai_profil (nomor/tanggal
     * SK CPNS, nomor/tanggal SK jabatan terakhir) diisi manual per-DRH dan
     * ikut tersimpan di sini.
     *
     * Kolom `tanda_kehormatan_dimiliki` di-snapshot dari pegawai_penghargaan
     * saat dibuat (bukan live-join) — per keputusan user, isinya otomatis dari
     * riwayat penghargaan tercatat, tapi tetap dibekukan di dokumen hasil.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('drh_satya_lancana', function (Blueprint $table) {
            $table->id();

            $table->string('nip', 100);

            // Snapshot identitas dari pegawai_profil (lihat catatan class).
            $table->string('nama', 255);
            $table->string('nip_lama', 100)->nullable();
            $table->string('pendidikan_terakhir', 255)->nullable();
            $table->string('pangkat_golongan', 255)->nullable();
            $table->string('tmt_pangkat_golongan', 255)->nullable();
            $table->string('jabatan_terakhir', 255)->nullable();
            $table->string('jenis_kelamin', 50)->nullable();
            $table->string('tempat_lahir', 150)->nullable();
            $table->string('tanggal_lahir', 150)->nullable();

            // Field yang tak ada sumbernya di pegawai_profil — diisi manual
            // per-DRH oleh staf (lihat SuratTugasGeneratorService untuk pola
            // field manual lain, mis. tanggal_naskah).
            $table->string('sk_cpns_nomor', 255)->nullable();
            $table->string('sk_cpns_tanggal', 150)->nullable();
            $table->string('sk_cpns_tmt', 150)->nullable();
            $table->string('sk_jabatan_nomor', 255)->nullable();
            $table->string('sk_jabatan_tanggal', 150)->nullable();
            $table->string('sk_jabatan_tmt', 150)->nullable();

            // Snapshot ringkas riwayat SLKS yang sudah dimiliki (multi-baris),
            // dari pegawai_penghargaan saat DRH digenerate. Strip '-' bila kosong.
            $table->text('tanda_kehormatan_dimiliki')->nullable();

            // Teks baku yang bisa diedit staf per-DRH (default diisi dari
            // konstanta di form) — kalimat hukuman disiplin & CLTN.
            $table->text('hukuman_disiplin')->nullable();
            $table->text('cltn')->nullable();

            // Tempat/tanggal penetapan (mis. "SEMARANG" / "30 September 2026").
            $table->string('ditetapkan_di', 150)->nullable();
            $table->string('tanggal_ditetapkan', 150)->nullable();

            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->string('dibuat_oleh_nama', 150)->nullable();
            $table->timestamps();

            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('drh_satya_lancana');
    }
};
