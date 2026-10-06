<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penandatangan kiri DRH (Kepala Dinas) — default dari konstanta model,
     * tapi bisa diubah per-DRH (mis. saat pejabatnya berganti). Kolom
     * terpisah supaya snapshot dokumen lama tetap utuh.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->string('ttd_kiri_nama', 255)->nullable()->after('tanggal_ditetapkan');
            $table->string('ttd_kiri_nip', 100)->nullable()->after('ttd_kiri_nama');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->dropColumn(['ttd_kiri_nama', 'ttd_kiri_nip']);
        });
    }
};
