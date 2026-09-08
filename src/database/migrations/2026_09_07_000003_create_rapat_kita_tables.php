<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapat_kita_schedule_list', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_datetime')->index('idx_start');
            $table->dateTime('end_datetime')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('pelaksana', 100)->nullable();
            $table->string('dihadiri', 100)->nullable();
            $table->string('dispo')->nullable();
            $table->unsignedBigInteger('id_rapat_sebelumnya')->nullable();
            // Legacy column was int; users.bidang is a string name in this schema, so store the name.
            $table->string('bidang_pembuat_jadwal', 100);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->boolean('is_aktif')->default(true)->index('idx_aktif');
        });

        Schema::create('rapat_kita_notulen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kegiatan');
            $table->text('isi_notulen')->nullable();
            $table->text('hasil_keputusan')->nullable();
            $table->string('dokumentasi')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('nama_kegiatan', 50)->nullable();
            $table->date('tanggal')->nullable();
            $table->unsignedBigInteger('user_id_pembuat_notulen')->nullable();

            $table->foreign('id_kegiatan', 'fk_notulen_kegiatan')
                ->references('id')->on('rapat_kita_schedule_list')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapat_kita_notulen');
        Schema::dropIfExists('rapat_kita_schedule_list');
    }
};
