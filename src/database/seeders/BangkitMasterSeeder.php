<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Isi master dropdown Bangkit. Nama asli master tidak ada di dump mana pun
 * (dump cuma menyimpan id referensi), jadi dipakai placeholder yang
 * menyesuaikan id yang benar-benar dipakai data barang + bidang pengguna.
 *
 * Idempotent (updateOrInsert) — aman dijalankan ulang & tidak menimpa nama
 * yang sudah dirapikan manual admin.
 */
class BangkitMasterSeeder extends Seeder
{
    public function run(): void
    {
        $conn = DB::connection('bangkit');

        // Jenis & keadaan dari id yang dipakai data.
        foreach ($this->usedIds('Jenis') as $id) {
            $conn->table('sekre_bangkit_master_jenis_barang')->updateOrInsert(['Id' => $id], ['jenis_barang' => "Jenis $id"]);
        }
        foreach ($this->usedIds('keadaan_barang') as $id) {
            $nama = match ((int) $id) { 1 => 'Baik', 2 => 'Rusak', default => "Keadaan $id" };
            $conn->table('sekre_bangkit_master_keadaan_barang')->updateOrInsert(['Id' => $id], ['keadaan_barang' => $nama]);
        }
        foreach ($this->usedIds('bahan') as $id) {
            $conn->table('sekre_bangkit_master_bahan_barang')->updateOrInsert(['Id' => $id], ['bahan' => "Bahan $id"]);
        }
        foreach ($this->usedIds('lokasi') as $id) {
            $conn->table('sekre_bangkit_master_lokasi')->updateOrInsert(['Id' => $id], ['lokasi' => "Lokasi $id"]);
        }

        // Master verifikasi (0=belum,1=disetujui,2=ditolak).
        foreach ([0 => 'Belum Diverifikasi', 1 => 'Disetujui', 2 => 'Ditolak'] as $id => $label) {
            $conn->table('sekre_bangkit_master_verifikasi_permohonan_perbaikan')->updateOrInsert(['Id' => $id], ['verifikasi' => $label]);
        }

        // katkit_bidang: petakan tiap nama bidang pengguna app (kolom string)
        // ke satu baris master, supaya scoping SDIA resolve.
        $namaBidang = collect();
        try {
            $namaBidang = DB::table('users')->whereNotNull('bidang')->where('bidang', '!=', '')
                ->distinct()->orderBy('bidang')->pluck('bidang');
        } catch (\Throwable $e) {
            // koneksi mysql mungkin tak tersedia; lanjut dengan daftar kosong
        }

        $nextId = (int) ($conn->table('katkit_bidang')->max('Id') ?? 0) + 1;
        foreach ($namaBidang as $nama) {
            $exists = $conn->table('katkit_bidang')->where('nama_bidang', $nama)->exists();
            if (! $exists) {
                $conn->table('katkit_bidang')->insert(['Id' => $nextId++, 'nama_bidang' => $nama]);
            }
        }

        $this->command?->info('master jenis='.$conn->table('sekre_bangkit_master_jenis_barang')->count()
            .' keadaan='.$conn->table('sekre_bangkit_master_keadaan_barang')->count()
            .' bahan='.$conn->table('sekre_bangkit_master_bahan_barang')->count()
            .' lokasi='.$conn->table('sekre_bangkit_master_lokasi')->count()
            .' bidang='.$conn->table('katkit_bidang')->count());
    }

    private function usedIds(string $col): array
    {
        return DB::connection('bangkit')->table('sekre_bangkit_data_barang')
            ->whereNotNull($col)->distinct()->orderBy($col)->pluck($col)->all();
    }
}
