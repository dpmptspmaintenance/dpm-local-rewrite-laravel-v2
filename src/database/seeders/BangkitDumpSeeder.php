<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor data nyata dari dump `.xls` legacy (old/.../bangkit/sql/).
 * Idempotent: truncate dulu tabel yang diisi (khusus data dump, bukan data UI).
 * HANYA dijalankan manual untuk mengisi DB baru; bukan bagian DatabaseSeeder.
 */
class BangkitDumpSeeder extends Seeder
{
    public function run(): void
    {
        $dir = env('BANGKIT_DUMP_DIR', base_path('../old/dpm-local/www/dpmptsp-local/bangkit/sql'));
        if (! is_dir($dir)) {
            $this->command?->warn("Folder dump tak ditemukan: $dir");

            return;
        }
        $conn = DB::connection('bangkit');

        // --- sekre_bangkit_role ---
        $rows = $this->read($dir.'/sekre_bangkit_role.xls');
        if ($rows) {
            $conn->table('sekre_bangkit_role')->truncate();
            foreach ($rows as $r) {
                $conn->table('sekre_bangkit_role')->insert([
                    'Id' => $r['Id'],
                    'role_name' => $r['role_name'],
                ]);
            }
            $this->command?->info('role: '.count($rows));
        }

        // --- sekre_bangkit_user ---
        $rows = $this->read($dir.'/sekre_bangkit_user.xls');
        if ($rows) {
            $conn->table('sekre_bangkit_user')->truncate();
            foreach ($rows as $r) {
                $conn->table('sekre_bangkit_user')->insert([
                    'Id' => $r['Id'],
                    'username' => $r['username'],
                    'password' => $r['password'],
                    'role' => $r['role'],
                    'isaktif' => (int) ($r['is_aktif'] ?? 1),
                    'created_at' => $this->date($r['created_at'] ?? null),
                    'modified_at' => $this->date($r['modified_at'] ?? null),
                ]);
            }
            $this->command?->info('user: '.count($rows));
        }

        // --- sekre_bangkit_data_barang ---
        $rows = $this->read($dir.'/sekre_bangkit_data_barang.xls');
        if ($rows) {
            $conn->table('sekre_bangkit_data_barang')->truncate();
            foreach ($rows as $r) {
                $conn->table('sekre_bangkit_data_barang')->insert([
                    'Id' => $r['Id'],
                    'kode_barang' => $r['kode_barang'],
                    'register' => $r['register'],
                    'kode_barang_register' => $r['kode_barang_register'],
                    'nama_barang' => $r['nama_barang'],
                    'merk_type' => $r['merk_type'],
                    'Jenis' => (int) $r['Jenis'],
                    'bahan' => (int) $r['bahan'],
                    'keadaan_barang' => (int) $r['keadaan_barang'],
                    'lokasi' => (int) $r['lokasi'],
                    'pemegang' => (int) $r['pemegang'],
                    'tahun_pembelian' => (string) $r['tahun_pembelian'],
                    'harga' => (float) $r['harga'],
                    'keterangan' => $r['keterangan'],
                    'link_foto' => $r['link_foto'],
                    'is_aktif' => (int) ($r['is_aktif'] ?? 1),
                    'created_at' => $this->date($r['created_at'] ?? null),
                    'modified_at' => $this->date($r['modified_at'] ?? null),
                    'modified_by' => $r['modified_by'],
                ]);
            }
            $this->command?->info('barang: '.count($rows));
        }

        // --- sekre_bangkit_data_permohonan_perbaikan ---
        $rows = $this->read($dir.'/sekre_bangkit_data_permohonan_perbaikan.xls');
        if ($rows) {
            $conn->table('sekre_bangkit_data_permohonan_perbaikan')->truncate();
            foreach ($rows as $r) {
                $conn->table('sekre_bangkit_data_permohonan_perbaikan')->insert([
                    'Id' => $r['Id'],
                    'id_barang' => (int) $r['id_barang'],
                    'kode_barang_register' => $r['kode_barang_register'],
                    'uraian_kerusakan' => $r['uraian_kerusakan'],
                    'tanggal_permohonan' => $this->date($r['tanggal_permohonan']),
                    'verifikasi_b_barang' => $r['verifikasi_b_barang'] === null ? null : (int) $r['verifikasi_b_barang'],
                    'perbaikan_ke' => $r['perbaikan_ke'],
                    'penjelasan_b_barang' => $r['penjelasan_b_barang'],
                    'pagu_anggaran' => $r['pagu_anggaran'],
                    'tanggal_verifikasi_b_barang' => $this->date($r['tanggal_verifikasi_b_barang']),
                    'verifikasi_umpeg' => $r['verifikasi_umpeg'] === null ? null : (int) $r['verifikasi_umpeg'],
                    'penjelasan_umpeg' => $r['penjelasan_umpeg'],
                    'tanggal_verifikasi_umpeg' => $this->date($r['tanggal_verifikasi_umpeg']),
                    'verifikasi_sekdin' => $r['verifikasi_sekdin'] === null ? null : (int) $r['verifikasi_sekdin'],
                    'penjelasan_sekdin' => $r['penjelasan_sekdin'],
                    'tanggal_verifikasi_sekdin' => $this->date($r['tanggal_verifikasi_sekdin']),
                    'tanggal_pengerjaan' => $this->date($r['tanggal_pengerjaan']),
                    'pengerjaan_oleh' => $r['pengerjaan_oleh'],
                    'tanggal_selesai' => $this->date($r['tanggal_selesai']),
                    'serah_terima_oleh' => $r['serah_terima_oleh'],
                    'uraian_perbaikan' => $r['uraian_perbaikan'],
                    'biaya' => $r['biaya'],
                    'spj_tanggal' => $this->date($r['spj_tanggal']),
                    'is_aktif' => (int) ($r['is_aktif'] ?? 1),
                    'created_at' => $this->date($r['created_at'] ?? null),
                    'modified_by' => $r['modified_by'],
                ]);
            }
            $this->command?->info('permohonan: '.count($rows));
        }
    }

    private function read(string $file): array
    {
        $ws = IOFactory::load($file)->getActiveSheet();
        $all = $ws->toArray(null, true, false, false);
        if (! $all) {
            return [];
        }
        $head = array_map(fn ($h) => trim((string) $h), $all[0]);
        $out = [];
        foreach (array_slice($all, 1) as $row) {
            $assoc = [];
            foreach ($head as $i => $k) {
                $assoc[$k] = $row[$i] ?? null;
            }
            $out[] = $assoc;
        }

        return $out;
    }

    private function date($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d H:i:s');
        }

        return \Illuminate\Support\Carbon::parse($v)->format('Y-m-d H:i:s');
    }
}
