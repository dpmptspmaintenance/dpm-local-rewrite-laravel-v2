<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag admin Arsip Digital, mengikuti pola is_admin_kepegawaian /
     * is_admin_persediaan — modul scoped, bukan role global.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('is_admin_arsip')->default(0)->after('is_admin_kepegawaian');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin_arsip');
        });
    }
};
