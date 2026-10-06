<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penandatangan kiri DRH = atasan langsung (default Kepala Dinas).
     *  - atasan_nip: NIP atasan bila ia pegawai terdaftar (untuk memilih);
     *    boleh kosong bila atasan di luar daftar pegawai.
     *  - ttd_kiri_jabatan: teks jabatan blok TTD kiri (multi-baris, mis.
     *    "Kepala Dinas\nPenanaman Modal Dan Pelayanan\nTerpadu Satu Pintu"),
     *    editable per-DRH.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->string('atasan_nip', 100)->nullable()->after('nip');
            $table->text('ttd_kiri_jabatan')->nullable()->after('ttd_kiri_nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->dropColumn(['atasan_nip', 'ttd_kiri_jabatan']);
        });
    }
};
