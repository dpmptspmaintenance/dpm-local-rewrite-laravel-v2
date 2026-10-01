<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\Cuti;
use App\Models\Kepegawaian\PegawaiProfil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Import of the cuti table from an exported Excel file.
 * Expected header row (exactly, on the first row of the first sheet):
 *
 *   No | No Surat | Nip | Nama | Tanggal Mulai Diajukan | Tanggal Selesai
 *   Diajukan | Opd | Unit Kerja | Lokasi Kerja | Status | Keperluan | Jenis
 *
 * Upsert, not replace: rows are matched on `no_surat` and updated in place,
 * unmatched file rows insert, and existing rows absent from the file are left
 * untouched. Nothing is ever deleted. Only an unparseable date fails a row
 * (collected in `errors`). Baris tanpa no_surat DILEWATI (keputusan user) —
 * sebelumnya di-insert apa adanya dan menumpuk 18 baris sampah ber-no_surat
 * NULL di DB; hitungannya dikembalikan sebagai `tanpa_surat`.
 * The "No" column is ignored (source numbering, recomputed by the query).
 */
class CutiImportService
{
    /** Panjang prefix yang dicoba saat mencocokkan NIP: 16 digit dulu, lalu 14. */
    private const NIP_PREFIX_LENGTHS = [16, 14];

    /**
     * Prefix map per panjang (first N chars => full nip) dari pegawai_profil,
     * dibangun lazily. Nilai null bila prefix ambigu di antara beberapa pegawai.
     *
     * @var array<int, array<string, string|null>>
     */
    private array $nipPrefixes = [];

    /** NIP yang tak bisa dicocokkan ke pegawai_profil (disimpan apa adanya). */
    private array $nipFallbacks = [];

    /** Peringatan non-fatal dari baris yang sedang diproses. */
    private array $warnings = [];

    /** Expected headers, keyed by column letter (0-based index). */
    private const HEADERS = [
        'no surat',
        'nip',
        'nama',
        'tanggal mulai diajukan',
        'tanggal selesai diajukan',
        'opd',
        'unit kerja',
        'lokasi kerja',
        'status',
        'keperluan',
        'jenis',
    ];

    /**
     * @return array{inserted: int, updated: int, skipped: int, tanpa_surat: int, errors: list<string>}
     */
    public function replaceAll(string $path): array
    {
        // Read the first sheet without formatting: dates arrive as Excel serial floats.
        $rows = IOFactory::load($path)->getSheet(0)->toArray(null, false, false, false);

        if (count($rows) < 2) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'tanpa_surat' => 0, 'errors' => ['File tidak berisi data (hanya header atau kosong).']];
        }

        $headerError = $this->validateHeader($rows[0]);
        if ($headerError) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'tanpa_surat' => 0, 'errors' => [$headerError]];
        }

        $records = [];
        $errors = [];
        $tanpaSurat = 0;

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNumber = $i + 2; // 1-based, header occupies row 1
            try {
                $record = $this->mapRow($row);
                if ($record === null) {
                    continue; // fully blank row
                }

                // Peringatan non-fatal dari mapRow (mis. tanggal terbalik).
                foreach ($this->warnings as $warning) {
                    $errors[] = "Baris {$rowNumber}: {$warning}";
                }
                $this->warnings = [];

                // Baris tanpa no_surat dilewati (keputusan user) — tak ada
                // kunci upsert-nya; dulu di-insert dan menumpuk baris sampah
                // ber-no_surat NULL di DB.
                if ($record['no_surat'] === null) {
                    $tanpaSurat++;
                    continue;
                }

                $records[] = $record;
            } catch (Throwable $e) {
                $errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
            }
        }

        if ($tanpaSurat > 0) {
            $errors[] = "{$tanpaSurat} baris dilewati karena kolom No Surat kosong.";
        }

        if ($records === []) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'tanpa_surat' => $tanpaSurat, 'errors' => array_merge(['Tidak ada baris valid untuk diimpor. Tabel cuti tidak diubah.'], $errors)];
        }

        // Upsert per no_surat: yang sudah ada diperbarui, yang baru di-insert.
        // Baris lama yang tidak ada di file dibiarkan utuh (tidak dihapus).
        // Semua baris di sini sudah pasti punya no_surat (yang kosong dilewati di atas).
        $keyed = [];
        foreach ($records as $record) {
            $keyed[$record['no_surat']][] = $record;
        }

        $existing = Cuti::query()
            ->whereIn('no_surat', array_keys($keyed))
            ->get()
            ->groupBy('no_surat');

        $toInsert = [];
        $updated = 0;
        $skipped = 0;

        DB::connection('kepegawaian')->transaction(function () use ($keyed, $existing, &$toInsert, &$updated, &$skipped) {
            $columns = [
                'nip', 'nama', 'tanggal_mulai_diajukan', 'tanggal_selesai_diajukan',
                'durasi_hari', 'opd', 'unit_kerja', 'lokasi_kerja',
                'status', 'keperluan', 'jenis',
            ];

            foreach ($keyed as $noSurat => $rows) {
                $dbRows = $existing->get($noSurat, collect());
                foreach (array_values($rows) as $i => $record) {
                    /** @var Cuti|null $model */
                    $model = $dbRows->get($i);
                    if ($model) {
                        // Koreksi manual via UI menang atas file import.
                        if ($model->diedit_manual) {
                            $skipped++;
                            continue;
                        }
                        $model->fill(collect($record)->only($columns)->all());
                        $model->save();
                        $updated++;
                    } else {
                        $toInsert[] = $record; // duplikat ke-N di file, baris DB habis
                    }
                }
            }

            foreach (array_chunk($toInsert, 500) as $chunk) {
                Cuti::insert($chunk);
            }
        });

        if ($this->nipFallbacks) {
            $errors[] = 'NIP berikut terbaca sebagai angka Excel dan tidak cocok dengan pegawai_profil — disimpan apa adanya, 3 digit terakhir mungkin tidak akurat: '.implode(', ', array_unique($this->nipFallbacks));
        }

        if ($skipped > 0) {
            $errors[] = "{$skipped} baris dilewati karena pernah diedit manual lewat UI — isi DB dipertahankan.";
        }

        return ['inserted' => count($toInsert), 'updated' => $updated, 'skipped' => $skipped, 'tanpa_surat' => $tanpaSurat, 'errors' => $errors];
    }

    private function validateHeader(array $row): ?string
    {
        // Column 0 = "No" (ignored), columns 1..11 must match HEADERS in order.
        foreach (self::HEADERS as $offset => $expected) {
            $actual = strtolower(trim((string) ($row[$offset + 1] ?? '')));
            if ($actual !== $expected) {
                return "Header kolom ".($offset + 2)." seharusnya \"{$expected}\", ditemukan \"{$actual}\". Gunakan template: No, No Surat, Nip, Nama, Tanggal Mulai Diajukan, Tanggal Selesai Diajukan, Opd, Unit Kerja, Lokasi Kerja, Status, Keperluan, Jenis.";
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null null for a fully blank row
     */
    private function mapRow(array $row): ?array
    {
        $cell = fn (int $i) => $row[$i] ?? null;

        // A row counts as blank only when every mapped column is empty. Don't
        // key this off no_surat/nip/nama alone — those are explicitly allowed
        // to be empty, so a row carrying only dates/keperluan is still data.
        $isBlankRow = true;
        for ($i = 1; $i <= 11; $i++) {
            if (! $this->isBlank($cell($i))) {
                $isBlankRow = false;
                break;
            }
        }

        if ($isBlankRow) {
            return null;
        }

        // no_surat/nip/nama boleh kosong — disimpan NULL (kolom sudah longgar).
        $noSurat = $this->nullIfEmpty($this->string($cell(1)));
        $nip = $this->nullIfEmpty($this->identifier($cell(2)));
        $nama = $this->nullIfEmpty($this->string($cell(3)));

        $mulai = $this->date($cell(4));
        $selesai = $this->date($cell(5));

        if (! $mulai || ! $selesai) {
            throw new \RuntimeException('Tanggal Mulai/Selesai Diajukan wajib diisi dengan tanggal valid.');
        }

        // Tanggal terbalik (selesai < mulai) tetap disimpan apa adanya —
        // data sumber, bukan untuk dikoreksi di sini.
        if ($selesai->lt($mulai)) {
            $this->warnings[] = 'tanggal selesai lebih awal dari tanggal mulai — disimpan apa adanya.';
        }

        return [
            'no_surat' => $noSurat,
            'nip' => $nip,
            'nama' => $nama,
            'tanggal_mulai_diajukan' => $mulai->format('Y-m-d'),
            'tanggal_selesai_diajukan' => $selesai->format('Y-m-d'),
            'durasi_hari' => $mulai->diffInDays($selesai) + 1,
            'opd' => $this->nullIfEmpty($this->string($cell(6))),
            'unit_kerja' => $this->nullIfEmpty($this->string($cell(7))),
            'lokasi_kerja' => $this->nullIfEmpty($this->string($cell(8))),
            'status' => $this->nullIfEmpty($this->string($cell(9))),
            'keperluan' => $this->nullIfEmpty($this->string($cell(10))),
            'jenis' => $this->nullIfEmpty($this->string($cell(11))),
        ];
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '' || trim((string) $value) === '-';
    }

    private function string(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    /**
     * NIP/no_surat-style value: a numeric Excel cell arrives as float and a
     * plain (string) cast turns 18-digit NIPs into "1.9940508202506E+17".
     * Format large integral floats without decimals instead.
     */
    private function identifier(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (fmod($value, 1.0) !== 0.0) {
                return trim((string) $value); // genuine decimal — keep as-is
            }
            $formatted = sprintf('%.0f', $value);

            // 18-digit NIPs stored as Excel numbers lose digits past 15 —
            // recover the full NIP by matching an intact prefix against
            // pegawai_profil. Ambiguous/unknown prefixes still fail.
            if (strlen($formatted) > 15) {
                $match = $this->matchNip($formatted);
                if ($match) {
                    return $match;
                }

                // Tidak ketemu/ambigu — simpan apa adanya daripada melewati baris;
                // data tetap bisa dicari dan diperbaiki manual via UI.
                $this->nipFallbacks[] = $formatted;
            }

            return $formatted;
        }

        return $this->string($value);
    }

    /**
     * Cocokkan NIP float ke pegawai_profil. Excel menyimpan NIP 18-digit
     * sebagai angka sehingga digit ke-16..18 hancur jadi 000/992 — dua digit
     * belakang bisa berubah. Coba prefix 16 digit dulu, lalu 14 digit bila
     * tidak ketemu (korupsi bisa menyentuh digit ke-15).
     */
    private function matchNip(string $formatted): ?string
    {
        foreach (self::NIP_PREFIX_LENGTHS as $length) {
            $prefix = substr($formatted, 0, $length);
            $map = $this->nipPrefixMap($length);

            if (array_key_exists($prefix, $map)) {
                return $map[$prefix]; // null = ambigu, berhenti cari.
            }
        }

        return null;
    }

    /**
     * @return array<string, string|null> prefix NIP (N char) => full nip
     *         (null when the prefix is ambiguous across known employees)
     */
    private function nipPrefixMap(int $length): array
    {
        if (! array_key_exists($length, $this->nipPrefixes)) {
            $map = [];
            foreach (PegawaiProfil::query()->pluck('nip') as $nip) {
                $prefix = substr(preg_replace('/\D+/', '', (string) $nip), 0, $length);
                if ($prefix === '') {
                    continue;
                }
                // First writer wins the slot; a second distinct nip nulls it out.
                if (! array_key_exists($prefix, $map)) {
                    $map[$prefix] = $nip;
                } elseif ($map[$prefix] !== $nip) {
                    // Two different employees share the prefix — can't disambiguate.
                    $map[$prefix] = null;
                }
            }
            $this->nipPrefixes[$length] = $map;
        }

        return $this->nipPrefixes[$length];
    }

    private function nullIfEmpty(string $value): ?string
    {
        return $value === '' || $value === '-' ? null : $value;
    }

    /**
     * Accept Excel serial numbers and common text formats (d-m-Y, d/m/Y, Y-m-d).
     */
    private function date(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '' || trim((string) $value) === '-') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'd.m.Y', 'j-n-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $text);
                if ($parsed instanceof Carbon && $parsed->format('Y-m-d') !== '-0001-11-30') {
                    return $parsed;
                }
            } catch (Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($text);
        } catch (Throwable) {
            return null;
        }
    }
}
