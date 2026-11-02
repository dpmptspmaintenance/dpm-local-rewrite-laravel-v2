<?php

namespace App\Services\Certificate;

use App\Models\Certificate\CertificateNumberActivity;
use App\Models\Certificate\CertificateNumberRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Logika penomoran sertifikat: saran nomor awal, penulisan ulang baris hasil
 * impor Excel, regenerate teks nomor surat, dan konversi ke dataset template
 * sertifikat.
 *
 * Format naskah: "{kode}{nomor}/{tahun}" — mis. kode "800.2/" + nomor 1 +
 * tahun 2026 => "800.2/1/2026".
 */
class CertificateNumberService
{
    public const DEFAULT_KODE = '800.2/';

    /** Header dataset hasil konversi nomor sertifikat. */
    public const DATASET_HEADERS = ['No', 'Kode', 'Tahun', 'Nomor Surat', 'Nama', 'Keterangan'];

    public const DATASET_KEYS = ['no', 'kode', 'tahun', 'nomor_surat', 'nama', 'keterangan'];

    /**
     * Nomor berikutnya untuk tahun tertentu = nomor terbesar + 1. Nomor
     * dihitung lintas kegiatan (semua kode) pada tahun yang sama.
     */
    public function nextNumber(int $tahun, ?int $ignoreActivityId = null): int
    {
        $query = CertificateNumberRow::query()->where('tahun', $tahun);

        if ($ignoreActivityId !== null) {
            $query->where('activity_id', '!=', $ignoreActivityId);
        }

        $max = (int) $query->max('nomor');

        return $max > 0 ? $max + 1 : 1;
    }

    /**
     * Ganti seluruh baris kegiatan dengan daftar nama/keterangan baru,
     * dinomori berurutan mulai `$activity->nomor_awal`.
     *
     * @param  list<array{nama: string, keterangan: string}>  $items
     */
    public function replaceRows(CertificateNumberActivity $activity, array $items): int
    {
        return DB::transaction(function () use ($activity, $items): int {
            $activity->rows()->delete();

            $nomor = (int) $activity->nomor_awal;
            $urutan = 0;

            foreach ($items as $item) {
                $nama = trim((string) ($item['nama'] ?? ''));

                if ($nama === '') {
                    continue;
                }

                $urutan++;

                $activity->rows()->create([
                    'urutan' => $urutan,
                    'nomor' => $nomor,
                    'kode' => $activity->kode,
                    'tahun' => $activity->tahun,
                    'nomor_surat' => $this->formatNomorSurat($activity->kode, $nomor, (int) $activity->tahun),
                    'nama' => $nama,
                    'keterangan' => trim((string) ($item['keterangan'] ?? '')) ?: null,
                ]);

                $nomor++;
            }

            $activity->forceFill(['nomor_akhir' => $urutan > 0 ? $nomor - 1 : null])->save();

            return $urutan;
        });
    }

    /**
     * Tambah satu baris manual di akhir kegiatan.
     */
    public function appendRow(CertificateNumberActivity $activity, string $nama, ?string $keterangan = null): CertificateNumberRow
    {
        // reorder() wajib: relasi rows() sudah punya orderBy('urutan'), jadi
        // orderByDesc() saja tetap menghasilkan urutan ascending.
        $last = $activity->rows()->reorder()->orderByDesc('urutan')->first();
        $nomor = $last !== null ? (int) $last->nomor + 1 : (int) $activity->nomor_awal;

        $row = $activity->rows()->create([
            'urutan' => (int) ($last->urutan ?? 0) + 1,
            'nomor' => $nomor,
            'kode' => $activity->kode,
            'tahun' => $activity->tahun,
            'nomor_surat' => $this->formatNomorSurat($activity->kode, $nomor, (int) $activity->tahun),
            'nama' => trim($nama),
            'keterangan' => trim((string) $keterangan) ?: null,
        ]);

        $activity->forceFill([
            'nomor_akhir' => max((int) $activity->nomor_awal, (int) $row->nomor),
        ])->save();

        return $row;
    }

    public function deleteRow(CertificateNumberActivity $activity, int $rowId): void
    {
        DB::transaction(function () use ($activity, $rowId): void {
            $activity->rows()->whereKey($rowId)->delete();

            // Nomor dirapatkan lagi supaya tidak ada celah setelah penghapusan.
            $this->renumber($activity);
        });
    }

    /**
     * Kosongkan seluruh baris kegiatan (empty data). Nomor akhir dikembalikan
     * ke null supaya baris berikutnya mulai lagi dari nomor_awal.
     *
     * @return int jumlah baris yang dihapus.
     */
    public function clearRows(CertificateNumberActivity $activity): int
    {
        return DB::transaction(function () use ($activity): int {
            $count = (int) $activity->rows()->count();
            $activity->rows()->delete();
            $activity->forceFill(['nomor_akhir' => null])->save();

            return $count;
        });
    }

    /**
     * Nomori ulang seluruh baris mulai dari nomor_awal kegiatan (tanpa celah),
     * termasuk teks nomor suratnya.
     */
    public function renumber(CertificateNumberActivity $activity): int
    {
        $nomor = (int) $activity->nomor_awal;
        $urutan = 0;

        foreach ($activity->rows()->get() as $row) {
            $urutan++;

            $row->forceFill([
                'urutan' => $urutan,
                'nomor' => $nomor,
                'kode' => $activity->kode,
                'tahun' => $activity->tahun,
                'nomor_surat' => $this->formatNomorSurat($activity->kode, $nomor, (int) $activity->tahun),
            ])->save();

            $nomor++;
        }

        $activity->forceFill(['nomor_akhir' => $urutan > 0 ? $nomor - 1 : null])->save();

        return $urutan;
    }

    public function refreshNomorAkhir(CertificateNumberActivity $activity): void
    {
        $max = (int) $activity->rows()->max('nomor');

        $activity->forceFill(['nomor_akhir' => $max > 0 ? $max : null])->save();
    }

    /**
     * Tulis ulang teks nomor surat seluruh baris memakai kode/tahun terkini.
     */
    public function regenerateNomorSurat(CertificateNumberActivity $activity): int
    {
        $count = 0;

        DB::transaction(function () use ($activity, &$count): void {
            foreach ($activity->rows()->get() as $row) {
                $row->forceFill([
                    'kode' => $activity->kode,
                    'tahun' => $activity->tahun,
                    'nomor_surat' => $this->formatNomorSurat($activity->kode, (int) $row->nomor, (int) $activity->tahun),
                ])->save();

                $count++;
            }
        });

        return $count;
    }

    /**
     * Dataset nomor sertifikat dari daftar nama hasil impor Excel langsung
     * (tanpa menyimpan kegiatan/berkas). Nomor surat dibuat dari kode, tahun,
     * dan nomor awal yang diisi di dialog.
     *
     * @param  list<array{nama: string, keterangan: string}>  $items
     * @return array{headers: list<string>, keys: list<string>, rows: list<array<string, string>>}
     */
    public function datasetFromItems(array $items, string $kode, int $tahun, int $nomorAwal): array
    {
        $rows = [];
        $nomor = max(1, $nomorAwal);

        foreach ($items as $item) {
            $nama = trim((string) ($item['nama'] ?? ''));

            if ($nama === '') {
                continue;
            }

            $rows[] = [
                'no' => (string) $nomor,
                'kode' => $kode,
                'tahun' => (string) $tahun,
                'nomor_surat' => $this->formatNomorSurat($kode, $nomor, $tahun),
                'nama' => $nama,
                'keterangan' => trim((string) ($item['keterangan'] ?? '')),
            ];

            $nomor++;
        }

        return [
            'headers' => self::DATASET_HEADERS,
            'keys' => self::DATASET_KEYS,
            'rows' => $rows,
        ];
    }

    public function formatNomorSurat(string $kode, int $nomor, int $tahun): string
    {
        $kode = trim($kode);

        if ($kode !== '' && ! Str::endsWith($kode, '/')) {
            $kode .= '/';
        }

        return $kode.$nomor.'/'.$tahun;
    }

    /**
     * Ambil baris Excel hasil CertificateExcelParser + pemetaan kolom pilihan
     * user menjadi daftar nama/keterangan.
     *
     * @param  array{headers: list<string>, keys: list<string>, rows: list<array<string, string>>}  $parsed
     * @param  array{nama?: string|null, keterangan?: string|null}  $mapping  key kolom dataset
     * @return list<array{nama: string, keterangan: string}>
     */
    public function itemsFromParsedExcel(array $parsed, array $mapping): array
    {
        $namaKey = trim((string) ($mapping['nama'] ?? ''));
        $keteranganKey = trim((string) ($mapping['keterangan'] ?? ''));

        if ($namaKey === '') {
            return [];
        }

        $items = [];

        foreach ($parsed['rows'] as $row) {
            $nama = trim((string) ($row[$namaKey] ?? ''));
            $keterangan = $keteranganKey !== '' ? trim((string) ($row[$keteranganKey] ?? '')) : '';

            if ($nama === '') {
                continue;
            }

            $items[] = ['nama' => $nama, 'keterangan' => $keterangan];
        }

        return $items;
    }

    /**
     * Dataset siap pakai untuk Generator Sertifikat. Meta kegiatan ikut
     * disalin ke tiap baris supaya placeholder <<kegiatan>> bisa dicetak.
     *
     * @return array{headers: list<string>, keys: list<string>, rows: list<array<string, string>>}
     */
    public function toDataset(CertificateNumberActivity $activity): array
    {
        $kegiatan = trim((string) $activity->kegiatan);
        $tempat = trim((string) $activity->tempat);
        $tanggal = $activity->tanggal?->format('d-m-Y') ?? '';

        $rows = [];

        foreach ($activity->rows()->get() as $row) {
            $rows[] = [
                'no' => (string) $row->nomor,
                'kode' => (string) $row->kode,
                'tahun' => (string) $row->tahun,
                'nomor_surat' => (string) $row->nomor_surat,
                'nama' => (string) $row->nama,
                'keterangan' => (string) ($row->keterangan ?? ''),
                'kegiatan' => $kegiatan,
                'tempat' => $tempat,
                'tanggal_kegiatan' => $tanggal,
                'nama_kegiatan' => $kegiatan,
            ];
        }

        return [
            'headers' => self::DATASET_HEADERS,
            'keys' => self::DATASET_KEYS,
            'rows' => $rows,
        ];
    }
}
