<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci API untuk integrasi eksternal (Chrome extension Generator Surat
     * Cuti). Disimpan di database `kepegawaian` karena hanya melayani modul
     * kepegawaian.
     *
     * Kunci plaintext TIDAK pernah disimpan — hanya hash SHA-256 + prefix
     * untuk ditampilkan. Plaintext diperlihatkan sekali saat dibuat.
     */
    public function up(): void
    {
        if (Schema::connection('kepegawaian')->hasTable('api_keys')) {
            return;
        }

        Schema::connection('kepegawaian')->create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('key_prefix', 20);
            $table->string('key_hash', 64)->unique();
            $table->json('scopes');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_nama', 150)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::connection('kepegawaian')->dropIfExists('api_keys');
    }
};
