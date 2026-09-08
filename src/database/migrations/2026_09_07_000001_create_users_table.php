<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('status_jabatan', 100);
            $table->string('nip', 100);
            $table->string('pangkat', 100)->nullable();
            $table->tinyInteger('role')->unsigned()->default(6);
            $table->string('bidang', 100)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->string('google_id')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->json('shared_pages')->nullable();
            $table->tinyInteger('is_bpp')->default(0);
            $table->tinyInteger('is_admin_persediaan')->default(0);
            $table->tinyInteger('is_admin_kepegawaian')->default(0);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
