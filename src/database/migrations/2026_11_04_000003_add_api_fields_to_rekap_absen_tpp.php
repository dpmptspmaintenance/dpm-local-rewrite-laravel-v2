<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyesuaian tabel TPP untuk sumber API (bukan impor PDF):
     * tambah identitas TPP + status file pendukung, buang kolom berkas PDF.
     *
     * Guarded: aman dijalankan pada tabel yang sudah ada. APP_ENV=production
     * → jangan `migrate`, berikan SQL ke user.
     */
    public function up(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('rekap_absen_tpp')) {
            return;
        }

        $schema->table('rekap_absen_tpp', function (Blueprint $table) use ($schema): void {
            foreach ([
                'id_tpp' => fn (Blueprint $table) => $table->unsignedBigInteger('id_tpp')->nullable()->after('id'),
                'golongan' => fn (Blueprint $table) => $table->string('golongan', 50)->nullable()->after('nama'),
                'eselon' => fn (Blueprint $table) => $table->string('eselon', 50)->nullable()->after('golongan'),
                'skpd' => fn (Blueprint $table) => $table->string('skpd', 255)->nullable()->after('eselon'),
                'unit_kerja' => fn (Blueprint $table) => $table->string('unit_kerja', 20)->nullable()->after('skpd'),
                'file_pendukung_status' => fn (Blueprint $table) => $table->string('file_pendukung_status', 10)->nullable()->after('tahun'),
                'file_pendukung_nama' => fn (Blueprint $table) => $table->string('file_pendukung_nama', 255)->nullable()->after('file_pendukung_status'),
                'file_pendukung_url' => fn (Blueprint $table) => $table->string('file_pendukung_url', 500)->nullable()->after('file_pendukung_nama'),
                'sumber' => fn (Blueprint $table) => $table->string('sumber', 20)->nullable()->after('file_pendukung_url'),
                'sumber_label' => fn (Blueprint $table) => $table->string('sumber_label', 100)->nullable()->after('sumber'),
            ] as $kolom => $definisi) {
                if (! $schema->hasColumn('rekap_absen_tpp', $kolom)) {
                    $definisi($table);
                }
            }

            if ($schema->hasColumn('rekap_absen_tpp', 'file_path')) {
                $table->dropColumn('file_path');
            }

            if ($schema->hasColumn('rekap_absen_tpp', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan: kolom berkas PDF sudah tidak dipakai.
    }
};
