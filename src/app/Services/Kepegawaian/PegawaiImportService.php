<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\PegawaiAnak;
use App\Models\Kepegawaian\PegawaiKompetensi;
use App\Models\Kepegawaian\PegawaiPenghargaan;
use App\Models\Kepegawaian\PegawaiProfil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Imports the JSON shape scraped from https://sisdm.semarangkota.go.id/pegawai/{id}
 * (see pegawai_profil.sumber_url) into pegawai_profil / pegawai_anak /
 * pegawai_kompetensi. Each employee record is a full current snapshot from
 * the source system, not a diff — so anak/kompetensi rows for that NIP are
 * replaced wholesale on every import, and pegawai_profil is upserted by nip.
 */
class PegawaiImportService
{
    /** @return array{created:int,updated:int,errors:array<int,string>} */
    public function importMany(array $records): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $nip = $record['detail_profil']['nip']
                    ?? $record['data_tabel_utama']['nip_tabel_utama']
                    ?? null;

                if (blank($nip)) {
                    throw new \RuntimeException('nip tidak ditemukan pada record.');
                }

                $existed = PegawaiProfil::whereKey($nip)->exists();

                DB::connection('kepegawaian')->transaction(function () use ($record, $nip) {
                    $this->importOne($nip, $record);
                });

                $existed ? $updated++ : $created++;
            } catch (Throwable $e) {
                $label = $record['detail_profil']['nip']
                    ?? $record['data_tabel_utama']['nama_tabel_utama']
                    ?? "record #{$index}";
                $errors[] = "{$label}: {$e->getMessage()}";
            }
        }

        return compact('created', 'updated', 'errors');
    }

    private function importOne(string $nip, array $record): void
    {
        $tabel = $record['data_tabel_utama'] ?? [];
        $detail = $record['detail_profil'] ?? [];
        $alamat = $record['data_alamat'] ?? [];

        PegawaiProfil::updateOrCreate(['nip' => $nip], [
            'nama' => $tabel['nama_tabel_utama'] ?? $detail['nama'] ?? '',
            'pangkat' => $tabel['pangkat_tabel_utama'] ?? null,
            'golongan' => $detail['golongan'] ?? null,
            'jabatan' => $detail['jabatan'] ?? null,
            'status_pegawai' => $detail['status_pegawai'] ?? null,
            'gender' => $detail['gender'] ?? null,
            'agama' => $detail['agama'] ?? null,
            'tempat_lahir' => $detail['tempat_lahir'] ?? null,
            'tanggal_lahir' => $this->parseDate($detail['tanggal_lahir'] ?? null),
            'usia_keterangan' => $detail['usia'] ?? null,
            'status_perkawinan' => $detail['status_perkawinan'] ?? null,
            'pendidikan' => $detail['pendidikan'] ?? null,
            'kelas_jabatan' => $detail['kelas_jabatan'] ?? null,
            'capaian_bangkom' => $detail['capaian_bangkom'] ?? null,
            'bup_but' => $detail['bup_but'] ?? null,
            'tmt_bup_but' => $this->parseDate($detail['tmt_bup_but'] ?? null),
            'tmt_golongan' => $this->parseDate($detail['tmt_golongan'] ?? null),
            'kgb_selanjutnya' => $this->parseDate($detail['kgb_selanjutnya'] ?? null),
            'nip_lama' => $detail['nip_lama'] ?? null,
            'alamat_ktp' => $alamat['alamat_ktp'] ?? null,
            'rt_ktp' => $alamat['rt_ktp'] ?? null,
            'rw_ktp' => $alamat['rw_ktp'] ?? null,
            'kelurahan_ktp' => $alamat['kelurahan_ktp'] ?? null,
            'kecamatan_ktp' => $alamat['kecamatan_ktp'] ?? null,
            'kota_ktp' => $alamat['kota_ktp'] ?? null,
            'provinsi_ktp' => $alamat['provinsi_ktp'] ?? null,
            'kode_pos_ktp' => $alamat['kode_pos_ktp'] ?? null,
            'alamat_domisili' => $alamat['alamat_domisili'] ?? null,
            'rt_domisili' => $alamat['rt_domisili'] ?? null,
            'rw_domisili' => $alamat['rw_domisili'] ?? null,
            'kelurahan_domisili' => $alamat['kelurahan_domisili'] ?? null,
            'kecamatan_domisili' => $alamat['kecamatan_domisili'] ?? null,
            'kota_domisili' => $alamat['kota_domisili'] ?? null,
            'provinsi_domisili' => $alamat['provinsi_domisili'] ?? null,
            'kode_pos_domisili' => $alamat['kode_pos_domisili'] ?? null,
            'jenis_domisili' => $alamat['jenis_domisili'] ?? null,
            'sumber_url' => $record['sumber_url'] ?? null,
        ]);

        // Full-snapshot replace: the source system always sends the complete
        // current list, so stale rows from a previous import must go.
        PegawaiAnak::where('nip', $nip)->delete();
        foreach ($record['riwayat_anak'] ?? [] as $anak) {
            PegawaiAnak::create([
                'nip' => $nip,
                'no_urut' => $this->toIntOrNull($anak['no'] ?? null),
                'nama_anak' => $anak['nama_anak'] ?? null,
                'gender_anak' => $anak['gender_anak'] ?? null,
                'tempat_lahir_anak' => $anak['tempat_lahir_anak'] ?? null,
                'tanggal_lahir_anak' => $this->parseDate($anak['tanggal_lahir_anak'] ?? null),
                'usia_anak' => $anak['usia_anak'] ?? null,
                'tingkat_pendidikan_anak' => $anak['tingkat_pendidikan_anak'] ?? null,
                'tunjangan_anak' => $anak['tunjangan_anak'] ?? null,
                'hubungan_keluarga_anak' => $anak['hubungan_keluarga_anak'] ?? null,
            ]);
        }

        PegawaiKompetensi::where('nip', $nip)->delete();
        foreach ($record['riwayat_kompetensi'] ?? [] as $k) {
            $jenis = $k['jenis'] ?? null;
            PegawaiKompetensi::create([
                'nip' => $nip,
                // The source system leaves this as its unselected dropdown
                // placeholder when nobody categorized the entry.
                'jenis' => $jenis === '-- Pilih --' ? null : $jenis,
                'jumlah_jam' => $this->toIntOrNull($k['jumlah_jam'] ?? null) ?? 0,
                'nama_kompetensi' => $k['nama_kompetensi'] ?? null,
                'nomor_sertifikat' => $k['nomor_sertifikat'] ?? null,
                'penyelenggara' => $k['penyelenggara'] ?? null,
                'tanggal_mulai' => $this->parseDate($k['tanggal_mulai'] ?? null),
                'tanggal_selesai' => $this->parseDate($k['tanggal_selesai'] ?? null),
                'tanggal_sertifikat' => $this->parseDate($k['tanggal_sertifikat'] ?? null),
            ]);
        }

        // Cuma hapus-ganti baris hasil impor sebelumnya — baris 'manual'
        // (ditambah lewat halaman Daftar Penghargaan) dibiarkan, tidak ikut
        // full-snapshot replace ini. Lihat catatan di migration kolom sumber.
        PegawaiPenghargaan::where('nip', $nip)
            ->where('sumber', PegawaiPenghargaan::SUMBER_IMPOR)
            ->delete();
        foreach ($record['riwayat_penghargaan'] ?? [] as $p) {
            PegawaiPenghargaan::create([
                'nip' => $nip,
                'sumber' => PegawaiPenghargaan::SUMBER_IMPOR,
                'no_urut' => $this->toIntOrNull($p['no'] ?? null),
                'jenis_penghargaan' => $p['jenis_penghargaan'] ?? null,
                'nama_penghargaan' => $p['nama_penghargaan'] ?? null,
                'asal_perolehan_penghargaan' => $p['asal_perolehan_penghargaan'] ?? null,
                'peringkat_penghargaan' => $p['peringkat_penghargaan'] ?? null,
                'nomor_sk_penghargaan' => $p['nomor_sk_penghargaan'] ?? null,
                'tanggal_sk_penghargaan' => $this->parseDate($p['tanggal_sk_penghargaan'] ?? null),
                'file_penghargaan_url' => $p['file_penghargaan_url'] ?? null,
            ]);
        }
    }

    private function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
