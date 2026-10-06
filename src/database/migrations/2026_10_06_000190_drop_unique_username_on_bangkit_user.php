<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data dump legacy punya username duplikat ("imam" x2), jadi unique index
     * pada sekre_bangkit_user.username harus dilepas. Koneksi terpisah.
     */
    public function up(): void
    {
        Schema::connection('bangkit')->table('sekre_bangkit_user', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });
    }

    public function down(): void
    {
        Schema::connection('bangkit')->table('sekre_bangkit_user', function (Blueprint $table) {
            $table->unique('username');
        });
    }
};
