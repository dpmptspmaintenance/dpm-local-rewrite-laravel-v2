<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenTpp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi + upsert data dukung TPP yang dikirim Chrome extension
 * (hasil scrape halaman tpp.semarangkota.go.id/izin).
 *
 * Baris dengan `file_pendukung_status = 'belum'` tetap disimpan sebagai
 * catatan, tetapi TIDAK menutup konfirmasi di rekap absen.
 */
class TppDataDukungService
{
    public const MAKS_ITEM = 200;

    public const SUMBER_API = 'api';

    /**
     * @return array{model: RekapAbsenTpp, aksi: string}
     */
    public function simpan(array $payload, ?User $pembuat = null, ?string $sumberLabel = null): array
    {
        $tanggalList = $this->tanggalList($payload);
        $bulan = RekapAbsen::bulanAngka($payload['bulan'] ?? $payload['bulan_label'] ?? null) ?? 0;
        $tahun = (int) ($payload['tahun'] ?? 0);
        $keterangan = $this->rapikan($payload['keterangan'] ?? '');

        $fileNama = $this->rapikan($payload['file_pendukung_nama'] ?? $payload['file_nama'] ?? '');
        $fileUrl = $this->rapikan($payload['file_pendukung_url'] ?? $payload['file_url'] ?? '');

        $statusMentah = strtolower($this->rapikan($payload['file_pendukung_status'] ?? $payload['file_status'] ?? ''));
        $statusFile = match (true) {
            in_array($statusMentah, ['ada', 'valid', 'upload'], true) => RekapAbsenTpp::FILE_ADA,
            in_array($statusMentah, ['belum', 'tidak', 'invalid', 'belum_upload'], true) => RekapAbsenTpp::FILE_BELUM,
            $fileUrl !== '' || $fileNama !== '' => RekapAbsenTpp::FILE_ADA,
            default => RekapAbsenTpp::FILE_BELUM,
        };

        $jumlahHari = (int) ($payload['jumlah_hari'] ?? 0);
        $jumlahHari = $jumlahHari > 0 ? $jumlahHari : count($tanggalList);
        $label = mb_substr($this->rapikan($sumberLabel ?? $payload['sumber_label'] ?? ''), 0, 100);

        return DB::connection('kepegawaian')->transaction(function () use (
            $payload,
            $tanggalList,
            $bulan,
            $tahun,
            $keterangan,
            $fileNama,
            $fileUrl,
            $statusFile,
            $jumlahHari,
            $pembuat,
            $label,
        ): array {
            $idTpp = isset($payload['id_tpp']) && $payload['id_tpp'] !== ''
                ? (int) $payload['id_tpp']
                : null;

            $kunci = $idTpp !== null
                ? ['id_tpp' => $idTpp]
                : [
                    'nip' => $this->rapikan($payload['nip'] ?? ''),
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'keterangan' => $keterangan,
                    'tanggal_mulai' => $tanggalList[0] ?? null,
                    'jumlah_hari' => $jumlahHari,
                ];

            $model = RekapAbsenTpp::query()->lockForUpdate()->firstOrNew($kunci);
            $aksi = $model->exists ? 'diperbarui' : 'dibuat';

            $model->fill([
                'id_tpp' => $idTpp,
                'nip' => $this->rapikan($payload['nip'] ?? ''),
                'nama' => $this->rapikan($payload['nama'] ?? ''),
                'golongan' => $this->rapikan($payload['golongan'] ?? '') ?: null,
                'eselon' => $this->rapikan($payload['eselon'] ?? '') ?: null,
                'skpd' => $this->rapikan($payload['skpd'] ?? '') ?: null,
                'unit_kerja' => $this->rapikan($payload['unit_kerja'] ?? '') ?: null,
                'jenis' => $this->jenis($keterangan),
                'keterangan' => $keterangan,
                'tanggal_mulai' => $tanggalList[0] ?? null,
                'tanggal_selesai' => $tanggalList === [] ? null : $tanggalList[count($tanggalList) - 1],
                'jumlah_hari' => $jumlahHari,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'file_pendukung_status' => $statusFile,
                'file_pendukung_nama' => $fileNama !== '' ? mb_substr($fileNama, 0, 255) : null,
                'file_pendukung_url' => $fileUrl !== '' ? mb_substr($fileUrl, 0, 500) : null,
                'sumber' => self::SUMBER_API,
                'sumber_label' => $label !== '' ? $label : null,
                'imported_at' => now(),
                'imported_by' => $pembuat?->getKey(),
                'imported_by_nama' => $pembuat !== null ? (string) ($pembuat->nama ?: $pembuat->name) : null,
            ])->save();

            $model->tanggal()->delete();

            foreach ($tanggalList as $tanggal) {
                $model->tanggal()->create(['tanggal' => $tanggal]);
            }

            return ['model' => $model->refresh(), 'aksi' => $aksi];
        });
    }

    /**
     * @return list<string>
     */
    private function tanggalList(array $payload): array
    {
        $mentah = $payload['tanggal_list'] ?? $payload['tanggal'] ?? null;
        $hasil = [];

        foreach ((array) $mentah as $nilai) {
            $tanggal = $this->tanggal($nilai);

            if ($tanggal !== null) {
                $hasil[] = $tanggal;
            }
        }

        if ($hasil !== []) {
            $hasil = array_values(array_unique($hasil));
            sort($hasil);

            return $hasil;
        }

        $bulan = RekapAbsen::bulanAngka($payload['bulan'] ?? null) ?? 0;
        $tahun = (int) ($payload['tahun'] ?? 0);

        return $bulan > 0 && $tahun > 0
            ? $this->tanggalDariKeterangan($this->rapikan($payload['keterangan'] ?? ''), $bulan, $tahun)
            : [];
    }

    /**
     * @return list<string>
     */
    private function tanggalDariKeterangan(string $keterangan, int $bulan, int $tahun): array
    {
        if (! preg_match('/\(tgl\s+([^)]+)\)/iu', $keterangan, $m)) {
            return [];
        }

        $isi = mb_strtolower(trim($m[1]));
        $tanggal = [];

        if (preg_match('/(\d{1,2})\s*-\s*(\d{1,2})\s+([a-z]+)\s+(\d{4})/u', $isi, $r)) {
            $bulanRentang = RekapAbsen::bulanAngka($r[3]) ?? $bulan;
            $tahunRentang = (int) $r[4];

            for ($hari = min((int) $r[1], (int) $r[2]); $hari <= max((int) $r[1], (int) $r[2]); $hari++) {
                if (checkdate($bulanRentang, $hari, $tahunRentang)) {
                    $tanggal[] = Carbon::create($tahunRentang, $bulanRentang, $hari)->toDateString();
                }
            }

            return array_values(array_unique($tanggal));
        }

        if (! preg_match('/([a-z]+)\s+(\d{4})\s*$/u', $isi, $my)) {
            return [];
        }

        $bulanBaris = RekapAbsen::bulanAngka($my[1]) ?? $bulan;
        $tahunBaris = (int) $my[2];
        $angka = preg_replace('/[a-z]+\s+\d{4}/u', '', $isi) ?? $isi;

        preg_match_all('/\d{1,2}/', $angka, $hariList);

        foreach ($hariList[0] as $hari) {
            $hari = (int) $hari;

            if (checkdate($bulanBaris, $hari, $tahunBaris)) {
                $tanggal[] = Carbon::create($tahunBaris, $bulanBaris, $hari)->toDateString();
            }
        }

        $tanggal = array_values(array_unique($tanggal));
        sort($tanggal);

        return $tanggal;
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

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
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

    private function jenis(string $keterangan): string
    {
        $teks = mb_strtolower($keterangan);

        return match (true) {
            str_starts_with($teks, 'presensi tidak terekam') => 'Presensi Tidak Terekam',
            str_contains($teks, 'tugas kedinasan') => 'Tugas Kedinasan',
            str_contains($teks, 'perjalanan dinas') => 'Perjalanan Dinas',
            str_contains($teks, 'pendidikan') || str_contains($teks, 'pelatihan') => 'Pendidikan/Pelatihan',
            str_contains($teks, 'cuti tahunan') => 'Cuti Tahunan',
            str_contains($teks, 'cuti bersalin') => 'Cuti Bersalin',
            str_contains($teks, 'cuti sakit') => 'Cuti Sakit',
            default => 'Lainnya',
        };
    }

    private function rapikan(mixed $nilai): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $nilai) ?? '');
    }
}
