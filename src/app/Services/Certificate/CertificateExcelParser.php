<?php

namespace App\Services\Certificate;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Baca berkas Excel/CSV untuk dataset sertifikat. Baris pertama sheet aktif
 * dianggap HEADER — apa pun judul kolomnya, sesuai apa yang diunggah user.
 * Nilai sel diambil sebagai string apa adanya (angka tidak diformat ribuan,
 * tanggal diformat dd-mm-yyyy) supaya tampil sama dengan yang user lihat.
 */
class CertificateExcelParser
{
    /**
     * @return array{headers: list<string>, keys: list<string>, rows: list<array<string, string>>}
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        // nullValue string kosong: sel kosong jadi '' (bukan null), formatData
        // false supaya angka mentah & rumus tidak berubah jadi string berformat.
        $matrix = $sheet->toArray('', true, false, false);

        $matrix = array_values(array_filter(
            $matrix,
            fn (array $row): bool => collect($row)->contains(fn ($cell): bool => filled($cell) && trim((string) $cell) !== ''),
        ));

        if ($matrix === []) {
            return ['headers' => [], 'keys' => [], 'rows' => []];
        }

        $rawHeaders = array_shift($matrix);
        $headers = [];
        $keys = [];

        foreach ($rawHeaders as $index => $header) {
            $label = trim((string) ($header ?? ''));

            if ($label === '') {
                $label = 'Kolom '.($index + 1);
            }

            // Header duplikat diberi suffix supaya key unik — kunci kolom
            // menentukan placeholder <<key>> yang dipakai di template.
            $baseLabel = $label;
            $suffix = 2;

            while (in_array($label, $headers, true)) {
                $label = $baseLabel.' ('.$suffix.')';
                $suffix++;
            }

            $headers[] = $label;

            $baseKey = Str::slug($label, '_') ?: 'kolom_'.($index + 1);
            $key = $baseKey;
            $keySuffix = 2;

            while (in_array($key, $keys, true)) {
                $key = $baseKey.'_'.$keySuffix;
                $keySuffix++;
            }

            $keys[] = $key;
        }

        $rows = [];

        foreach ($matrix as $rawRow) {
            $row = [];
            $hasValue = false;

            foreach ($keys as $i => $key) {
                $value = $this->stringify($rawRow[$i] ?? null);
                $row[$key] = $value;
                $hasValue = $hasValue || $value !== '';
            }

            if ($hasValue) {
                $rows[] = $row;
            }
        }

        return ['headers' => $headers, 'keys' => $keys, 'rows' => $rows];
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y');
        }

        if (is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465 && str_contains((string) $value, '.') === false) {
            // Heuristik tanggal Excel (serial number) untuk sel yang dibaca
            // sebagai angka mentah; kalau tidak masuk akal sebagai tanggal,
            // biarkan angka aslinya.
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('d-m-Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return trim((string) $value);
    }
}
