<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status alur usulan DRH Satya Lancana:
     * draft (default) → diusulkan → ditolak / sukses.
     * Bebas diubah staf kapan saja; bukan gate otomatis apa pun.
     */
    public function up(): void
    {
        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('atasan_nip');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->table('drh_satya_lancana', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
