<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kuota cuti tahunan per pegawai per tahun — override jatah yang diberikan
     * (mis. PPPK/CPNS yang mulai 2025 dapat 0 hari di 2025, dan hutangnya
     * mengurangi jatah 2026).
     *
     * Lives on the `kepegawaian` connection (its own database), same pattern as
     * the bangkit migration. Baris di sini adalah OVERRIDE: kalau tidak ada
     * baris untuk (nip, tahun), jatahnya dianggap Cuti::KUOTA_TAHUNAN (12) —
     * jadi data lama tak berubah sampai user mengisi.
     *
     * Tidak ada FK ke pegawai_profil: sama seperti cuti.nip, NIP di sini boleh
     * merujuk pegawai yang belum ada di profil (mis. input lebih dulu).
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->create('cuti_kuota_tahunan', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 100);
            $table->unsignedSmallInteger('tahun');
            $table->unsignedSmallInteger('kuota_hari')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Satu baris per pegawai per tahun; ini yang bikin rekap bisa
            // memetakan nip+tahun ke kuota dengan aman.
            $table->unique(['nip', 'tahun']);
            $table->index('tahun');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('cuti_kuota_tahunan');
    }
};
