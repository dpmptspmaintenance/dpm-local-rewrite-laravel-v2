<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Kapan Diusulkan" — tahun pengajuan DRH. Default tahun berjalan
     * (year(now())), bisa dikoreksi manual per-DRH (mis. usulan tahun lalu
     * yang baru diinput sekarang → set 2025).
     *
     * Idempotent: kolom bisa saja sudah ada di DB (alter manual sebelumnya)
     * sementara baris migration belum tercatat — jangan gagal duplicate column.
     */
    public function up(): void
    {
        if (Schema::connection('kepegawaian')->hasColumn('drh_satya_lancana', 'tahun_diusulkan')) {
            return;
        }

        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->year('tahun_diusulkan')
                ->nullable()
                ->default(now()->year)
                ->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::connection('kepegawaian')->hasColumn('drh_satya_lancana', 'tahun_diusulkan')) {
            return;
        }

        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->dropColumn('tahun_diusulkan');
        });
    }
};
