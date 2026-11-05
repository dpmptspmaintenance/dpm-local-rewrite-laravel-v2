<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\Kepegawaian\RekapAbsen;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi + upsert rekap absensi bulanan dari payload Chrome extension.
 *
 * Kunci unik: nip + tahun + bulan. Unggahan ulang pada periode yang sama
 * MENGGANTI seluruh rincian harian + data dukung (bukan menumpuk), sesuai
 * permintaan "update data jika di upload ulang".
 *
 * Tidak ada penghapusan header lewat API maupun panel.
 */
class RekapAbsenService
{
    public const MAKS_ITEM = 12;

    public const MAKS_HARIAN = 31;

    public const MAKS_DATA_DUKUNG = 100;

    public const SUMBER_EXTENSION = 'extension';

    /**
     * @return array{model: RekapAbsen, aksi: string}
     */
    public function simpan(array $payload, ?User $pembuat = null, ?string $sumberLabel = null): array
    {
        $nip = trim((string) ($payload['nip'] ?? ''));
        $bulan = RekapAbsen::bulanAngka($payload['bulan'] ?? $payload['bulan_label'] ?? null);
        $tahun = (int) ($payload['tahun'] ?? 0);

        $profil = $nip === '' ? null : PegawaiProfil::query()->find($nip);

        $nama = trim((string) ($payload['nama'] ?? '')) ?: (string) ($profil?->nama ?? '');
        $jabatan = trim((string) ($payload['jabatan'] ?? '')) ?: $profil?->jabatan;
        $opd = trim((string) ($payload['opd'] ?? $payload['unit_kerja'] ?? '')) ?: $profil?->opd;

        $ringkasan = array_merge($payload, (array) ($payload['ringkasan'] ?? []));

        $harian = $this->normalisasiHarian($payload['harian'] ?? []);
        $dataDukung = $this->normalisasiDataDukung($payload['data_dukung'] ?? $payload['datadukung'] ?? []);
        $sumberLabel = mb_substr(trim((string) ($sumberLabel ?? $payload['sumber_label'] ?? '')), 0, 100);

        return DB::connection('kepegawaian')->transaction(function () use (
            $nip,
            $nama,
            $jabatan,
            $opd,
            $bulan,
            $tahun,
            $ringkasan,
            $harian,
            $dataDukung,
            $pembuat,
            $sumberLabel,
        ): array {
            $model = RekapAbsen::query()
                ->where('nip', $nip)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->lockForUpdate()
                ->first();

            $aksi = $model === null ? 'dibuat' : 'diperbarui';
            $model ??= new RekapAbsen;

            $model->fill([
                'nip' => $nip,
                'nama' => $nama,
                'jabatan' => $jabatan !== '' ? $jabatan : null,
                'opd' => $opd !== '' ? $opd : null,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'hari_kerja' => $this->angka($ringkasan, ['hari_kerja', 'hariKerja']),
                'jumlah_hadir' => $this->angka($ringkasan, ['hadir', 'jumlah_hadir', 'masuk', 'jumlah_masuk']),
                'jumlah_tidak_hadir' => $this->angka($ringkasan, ['tidak_hadir', 'jumlah_tidak_hadir', 'tidak_masuk', 'jumlah_tidak_masuk']),
                'jumlah_konfirmasi' => $this->angka($ringkasan, ['konfirmasi', 'jumlah_konfirmasi']),
                'total_keterlambatan' => $this->durasi($ringkasan['keterlambatan'] ?? $ringkasan['total_keterlambatan'] ?? null),
                'sumber' => self::SUMBER_EXTENSION,
                'sumber_label' => $sumberLabel !== '' ? $sumberLabel : null,
                'diunggah_oleh' => $pembuat?->getKey(),
                'diunggah_oleh_nama' => $pembuat !== null ? (string) ($pembuat->nama ?: $pembuat->name) : null,
                'diunggah_at' => now(),
            ])->save();

            $model->harian()->delete();
            $model->dataDukung()->delete();

            foreach ($harian as $baris) {
                $model->harian()->create($baris);
            }

            foreach ($dataDukung as $baris) {
                $model->dataDukung()->create($baris);
            }

            return ['model' => $model->refresh(), 'aksi' => $aksi];
        });
    }

    /**
     * Hitung ulang ringkasan setelah ada baris harian yang dihapus.
     * Hadir = baris dengan jam_hadir; konfirmasi = baris berstatus;
     * tidak hadir = hari_kerja - hadir (minimal 0). Hari kerja dan total
     * keterlambatan tetap memakai nilai hasil unggahan.
     */
    public function hitungUlangRingkasan(RekapAbsen $model): void
    {
        $harian = $model->harian();

        $hadir = (clone $harian)->whereNotNull('jam_hadir')->count();
        $konfirmasi = (clone $harian)->whereNotNull('status')->count();

        $model->forceFill([
            'jumlah_hadir' => $hadir,
            'jumlah_konfirmasi' => $konfirmasi,
            'jumlah_tidak_hadir' => max(0, (int) $model->hari_kerja - $hadir),
        ])->save();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalisasiHarian(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $hasil = [];

        foreach (array_values($rows) as $indeks => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (count($hasil) >= self::MAKS_HARIAN) {
                break;
            }

            $status = trim((string) ($row['status'] ?? ''));

            $hasil[] = [
                'urutan' => $indeks + 1,
                'tanggal' => $this->tanggal($row['tanggal'] ?? $row['tgl'] ?? null),
                'hari' => mb_substr(trim((string) ($row['hari'] ?? '')), 0, 20) ?: null,
                'jam_hadir' => $this->waktu($row['jam_hadir'] ?? $row['hadir'] ?? null),
                'hadir_selisih' => $this->durasi($row['hadir_selisih'] ?? $row['selisih_hadir'] ?? null),
                'jam_pulang' => $this->waktu($row['jam_pulang'] ?? $row['pulang'] ?? null),
                'pulang_selisih' => $this->durasi($row['pulang_selisih'] ?? $row['selisih_pulang'] ?? null),
                'status' => $status === '' || $status === '-' ? null : mb_substr($status, 0, 50),
            ];
        }

        return $hasil;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalisasiDataDukung(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $hasil = [];

        foreach (array_values($rows) as $indeks => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (count($hasil) >= self::MAKS_DATA_DUKUNG) {
                break;
            }

            $token = trim((string) ($row['token'] ?? $row['kode'] ?? ''));

            if ($token === '' && ! empty($row['url'])) {
                $token = trim((string) preg_replace('#^.*/data-dukung/#', '', (string) $row['url']));
            }

            $hasil[] = [
                'urutan' => $indeks + 1,
                'tanggal_mulai' => $this->tanggal($row['tanggal_mulai'] ?? null),
                'tanggal_selesai' => $this->tanggal($row['tanggal_selesai'] ?? null),
                'keterangan' => mb_substr(trim((string) ($row['keterangan'] ?? '')), 0, 255) ?: null,
                'jenis' => mb_substr(trim((string) ($row['jenis'] ?? '')), 0, 100) ?: null,
                'token' => $token !== '' ? $token : null,
            ];
        }

        return $hasil;
    }

    private function angka(array $sumber, array $kunci): int
    {
        foreach ($kunci as $kunciSatu) {
            if (! array_key_exists($kunciSatu, $sumber)) {
                continue;
            }

            $nilai = $sumber[$kunciSatu];

            if ($nilai === null || $nilai === '' || $nilai === '-') {
                continue;
            }

            return max(0, (int) $nilai);
        }

        return 0;
    }

    private function tanggal(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        if ($teks === '' || $teks === '-') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
            try {
                $tanggal = Carbon::createFromFormat('!'.$format, $teks);
            } catch (\Throwable) {
                continue;
            }

            if ($tanggal !== false && $tanggal->format($format) === $teks) {
                return $tanggal->toDateString();
            }
        }

        return null;
    }

    private function waktu(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        if ($teks === '' || $teks === '-') {
            return null;
        }

        foreach (['Y-m-d H:i:s', 'd-m-Y H:i:s', 'd/m/Y H:i:s'] as $format) {
            try {
                $waktu = Carbon::createFromFormat('!'.$format, $teks);
            } catch (\Throwable) {
                continue;
            }

            if ($waktu !== false && $waktu->format($format) === $teks) {
                return $waktu->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function durasi(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = trim($nilai);

        if ($teks === '' || $teks === '-') {
            return null;
        }

        return mb_substr($teks, 0, 20);
    }
}
