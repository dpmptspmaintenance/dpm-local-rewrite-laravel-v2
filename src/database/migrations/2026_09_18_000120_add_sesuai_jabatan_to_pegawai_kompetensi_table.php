<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag kesesuaian per baris kompetensi terhadap jabatan pegawai —
     * ditandai manual oleh admin (default TRUE / "Sesuai", sampai ada yang
     * meninjau dan menandainya "Tidak Sesuai"). Alasan manual, bukan
     * otomatis: `jenis` di pegawai_kompetensi adalah FORMAT penyampaian
     * (Seminar/Workshop/Kursus/...), bukan topik/bidang kompetensi — tak ada
     * kolom yang bisa dicocokkan otomatis ke jabatan secara andal.
     *
     * ALTER pada tabel `pegawai_kompetensi` yang sudah ada datanya (bukan
     * tabel baru) — pengecualian dari kebijakan "no migrations untuk skema
     * kepegawaian", diminta & dikonfirmasi eksplisit oleh user (lihat
     * CLAUDE.md), sama seperti pengecualian cuti_kuota_tahunan sebelumnya.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('pegawai_kompetensi', function (Blueprint $table) {
            $table->boolean('sesuai_jabatan')->default(true)->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('pegawai_kompetensi', function (Blueprint $table) {
            $table->dropColumn('sesuai_jabatan');
        });
    }
};
