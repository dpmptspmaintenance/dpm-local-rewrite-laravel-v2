<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surat Pengantar diselaraskan dengan contoh naskah dinas
     * (contoh/contoh_surat_pengantar.pdf):
     *   - nomor_naskah: nomor naskah dinas (diisi manual / saat registrasi
     *     Srikandi), dicetak di baris "Nomor:".
     *   - tujuan_sambungan: baris lanjutan tujuan setelah "Yth." (mis.
     *     "dan Pelatihan Kota Semarang").
     *   - ttd_jabatan: jabatan penandatangan yang dicetak setelah baris
     *     "An. ... Kota Semarang" (mis. "Sekretaris").
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar', function (Blueprint $table) {
            // Kolom bisa saja sudah ada (ditambah manual saat pengembangan) —
            // tambahkan hanya yang belum ada supaya migration idempoten.
            if (! Schema::connection('kepegawaian')->hasColumn('surat_pengantar', 'nomor_naskah')) {
                $table->string('nomor_naskah', 255)->nullable()->after('id');
            }
            if (! Schema::connection('kepegawaian')->hasColumn('surat_pengantar', 'tujuan_sambungan')) {
                $table->string('tujuan_sambungan', 500)->nullable()->after('tujuan');
            }
            if (! Schema::connection('kepegawaian')->hasColumn('surat_pengantar', 'ttd_jabatan')) {
                $table->string('ttd_jabatan', 255)->nullable()->after('ttd_baris_3');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('surat_pengantar', function (Blueprint $table) {
            $table->dropColumn(['nomor_naskah', 'tujuan_sambungan', 'ttd_jabatan']);
        });
    }
};
