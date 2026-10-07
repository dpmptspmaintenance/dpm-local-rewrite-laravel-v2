<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konsep Ownership Akses pada Modul Arsip Digital:
     * - Admin dapat membuat ownership bebas (misal: Kepegawaian, Keuangan, IT, dll).
     * - User dapat dipetakan ke satu atau lebih ownership lewat tabel pivot ownership_user.
     * - Dokumen dapat diasosiasikan ke satu ownership (ownership_id).
     * - Akses dokumen dibatasi: hanya user di bawah ownership tersebut dan admin/superadmin yang bisa akses.
     * - Dokumen tanpa ownership (ownership_id = null) bersifat umum/semua unit.
     */
    public function up(): void
    {
        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ownership_user', function (Blueprint $table) {
            $table->foreignId('ownership_id')->constrained('ownerships')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['ownership_id', 'user_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('ownership_id')
                ->nullable()
                ->after('category_id')
                ->constrained('ownerships')
                ->nullOnDelete();

            $table->index('ownership_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['ownership_id']);
            $table->dropIndex(['ownership_id']);
            $table->dropColumn('ownership_id');
        });

        Schema::dropIfExists('ownership_user');
        Schema::dropIfExists('ownerships');
    }
};
