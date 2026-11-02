<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koreksi per baris pegawai pada halaman "Dokumen" Surat Tugas.
     * override_* NULL = pakai snapshot asli (nama/jabatan/pangkat_golongan).
     * override_dihilangkan = baris tidak dicetak di dokumen (tanpa menghapus
     * data riwayat), dipakai saat user mengurangi daftar pegawai dari editor.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_tugas_pegawai', function (Blueprint $table) {
            $table->string('override_nama', 255)->nullable()->after('pangkat_golongan');
            $table->string('override_jabatan', 255)->nullable()->after('override_nama');
            $table->string('override_pangkat_golongan', 255)->nullable()->after('override_jabatan');
            $table->boolean('override_dihilangkan')->default(false)->after('override_pangkat_golongan');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_tugas_pegawai', function (Blueprint $table) {
            $table->dropColumn([
                'override_nama',
                'override_jabatan',
                'override_pangkat_golongan',
                'override_dihilangkan',
            ]);
        });
    }
};
