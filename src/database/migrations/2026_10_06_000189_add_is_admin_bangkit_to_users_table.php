<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag admin modul "Bangkit" (Barang Kita + SDIA), mengikuti pola
     * is_admin_kepegawaian / is_admin_persediaan / is_admin_arsip — modul
     * scoped, bukan role global.
     *
     * Legacy CodeIgniter `bangkit` memakai role 1..6 sendiri:
     *   1 superadmin, 2 kadin, 3 sekdin, 4 kasubag, 5 bendahara, 6 user.
     * Nilai role di tabel `users` app ini kebetulan sama 1..6 (lihat
     * UserResource::ROLES), jadi bisa dipakai apa adanya; `is_admin_bangkit`
     * menambah jalur admin penuh tanpa harus role 1.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('is_admin_bangkit')->default(0)->after('is_admin_arsip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin_bangkit');
        });
    }
};
