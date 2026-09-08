<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapat_kita_notulen', function (Blueprint $table) {
            $table->time('jam_mulai')->nullable()->after('tanggal');
            $table->string('ketua')->nullable()->after('nama_kegiatan');
            $table->string('sekretaris')->nullable()->after('ketua');
            $table->text('anggota')->nullable()->after('sekretaris');
            $table->text('susunan')->nullable()->after('anggota');
            $table->text('pembahasan')->nullable()->after('susunan');
            $table->text('hasil')->nullable()->after('pembahasan');
            $table->string('nama_pimpinan')->nullable()->after('hasil');
            $table->string('jabatan_pimpinan')->nullable()->after('nama_pimpinan');
            $table->string('nama_notulis')->nullable()->after('jabatan_pimpinan');
            $table->string('jabatan_notulis')->nullable()->after('nama_notulis');
            $table->string('foto_1')->nullable()->after('jabatan_notulis');
            $table->string('foto_2')->nullable()->after('foto_1');
            $table->string('foto_3')->nullable()->after('foto_2');
            $table->string('foto_surat')->nullable()->after('foto_3');
            $table->string('created_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rapat_kita_notulen', function (Blueprint $table) {
            $table->dropColumn([
                'jam_mulai', 'ketua', 'sekretaris', 'anggota', 'susunan',
                'pembahasan', 'hasil', 'nama_pimpinan', 'jabatan_pimpinan',
                'nama_notulis', 'jabatan_notulis', 'foto_1', 'foto_2', 'foto_3', 'foto_surat',
            ]);
        });
    }
};
