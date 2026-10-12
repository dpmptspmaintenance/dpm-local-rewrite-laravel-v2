<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyesuaian kolom surat_pengantar_kp dengan template final (mengikuti
     * contoh dokumen): baris kedua alamat tujuan, lampiran, dan baris jabatan
     * blok tanda tangan (An. Kepala Dinas → Sekretaris).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar_kp', function (Blueprint $table) {
            $table->string('tujuan_sambungan', 500)->nullable()->after('tujuan');
            $table->string('lampiran', 255)->nullable()->default('-')->after('tujuan_sambungan');
            $table->string('ttd_jabatan', 255)->nullable()->after('ttd_baris_3');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar_kp', function (Blueprint $table) {
            $table->dropColumn(['tujuan_sambungan', 'lampiran', 'ttd_jabatan']);
        });
    }
};
