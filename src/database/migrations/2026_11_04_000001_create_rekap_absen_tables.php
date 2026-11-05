<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekap absensi bulanan per pegawai, hasil unggahan Chrome extension
     * dari aplikasi presensi (secure-sadewa). Tiga tabel:
     *  - rekap_absen            : header per pegawai + bulan (unik nip+tahun+bulan)
     *  - rekap_absen_harian     : rincian presensi harian
     *  - rekap_absen_data_dukung: lampiran konfirmasi (token file secure-sadewa)
     *
     * Data HANYA ditulis lewat API (scope rekap-absen); panel Kepegawaian
     * bersifat baca saja — tidak ada create/edit/delete.
     *
     * Lives on the `kepegawaian` connection (its own database) — tidak ada FK
     * ke users di database `mysql`, diunggah_oleh cuma snapshot id+nama.
     */
    public function up(): void
    {
        $schema = Schema::connection('kepegawaian');

        if (! $schema->hasTable('rekap_absen')) {
            $schema->create('rekap_absen', function (Blueprint $table): void {
                $table->id();
                $table->string('nip', 30);
                $table->string('nama', 150);
                $table->string('jabatan', 255)->nullable();
                $table->string('opd', 255)->nullable();
                $table->unsignedSmallInteger('tahun');
                $table->unsignedTinyInteger('bulan');
                $table->unsignedSmallInteger('hari_kerja')->default(0);
                $table->unsignedSmallInteger('jumlah_hadir')->default(0);
                $table->unsignedSmallInteger('jumlah_tidak_hadir')->default(0);
                $table->unsignedSmallInteger('jumlah_konfirmasi')->default(0);
                $table->string('total_keterlambatan', 20)->nullable();
                $table->string('sumber', 50)->nullable();
                $table->string('sumber_label', 100)->nullable();
                $table->unsignedBigInteger('diunggah_oleh')->nullable();
                $table->string('diunggah_oleh_nama', 150)->nullable();
                $table->timestamp('diunggah_at')->nullable();
                $table->timestamps();

                $table->unique(['nip', 'tahun', 'bulan']);
                $table->index(['tahun', 'bulan']);
                $table->index('nama');
            });
        }

        if (! $schema->hasTable('rekap_absen_harian')) {
            $schema->create('rekap_absen_harian', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('rekap_absen_id')->constrained('rekap_absen')->cascadeOnDelete();
                $table->unsignedSmallInteger('urutan')->default(0);
                $table->date('tanggal')->nullable();
                $table->string('hari', 20)->nullable();
                $table->dateTime('jam_hadir')->nullable();
                $table->string('hadir_selisih', 20)->nullable();
                $table->dateTime('jam_pulang')->nullable();
                $table->string('pulang_selisih', 20)->nullable();
                $table->string('status', 50)->nullable();
                $table->timestamps();

                $table->index(['rekap_absen_id', 'tanggal']);
            });
        }

        if (! $schema->hasTable('rekap_absen_data_dukung')) {
            $schema->create('rekap_absen_data_dukung', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('rekap_absen_id')->constrained('rekap_absen')->cascadeOnDelete();
                $table->unsignedSmallInteger('urutan')->default(0);
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->string('keterangan', 255)->nullable();
                $table->string('jenis', 100)->nullable();
                $table->text('token')->nullable();
                $table->timestamps();

                $table->index('rekap_absen_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('kepegawaian');

        $schema->dropIfExists('rekap_absen_data_dukung');
        $schema->dropIfExists('rekap_absen_harian');
        $schema->dropIfExists('rekap_absen');
    }
};
