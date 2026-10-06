<?php

namespace App\Services\Kepegawaian;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use SimpleXMLElement;
use Throwable;

/**
 * Impor DAFTAR URUT KEPANGKATAN (DUK) dari PDF.
 *
 * PDF DUK punya text layer, jadi diekstrak lewat `pdftotext -bbox` (poppler)
 * lalu tiap "word" dikelompokkan ke baris (kluster Y) dan ke kolom (posisi X)
 * — bukan OCR. Kolom DUK: NO | NAMA | NIP | GOL | TMT | GOL CPNS | TMT CPNS |
 * JABATAN / Eselon | MASA KERJA | PENDIDIKAN. Nilai panjang (NIP 18 digit, TMT
 * "dd-mm-yyyy") ter-wrap 2 baris di PDF, direkonstruksi dengan menggabung
 * token satu kolom lintas baris milik satu pegawai.
 *
 * Masa kerja diambil APA ADANYA dari dokumen (tahun + bulan) — DUK memang
 * membawa perhitungan resmi kepegawaian sendiri, bukan turunan NIP.
 *
 * Snapshot terbaru: tiap impor mengganti seluruh isi tabel `duk`.
 */
class DukImportService
{
    /** @var array<int, array{0:float,1:float}> Batas kolom (xMin) → slice. */
    private const COLUMNS = [
        'no' => [0.0, 52.0],
        'nama' => [52.0, 231.0],
        'nip' => [231.0, 273.0],
        'gol' => [273.0, 296.0],
        'tmt' => [296.0, 317.0],
        'gol_cpns' => [317.0, 339.0],
        'tmt_cpns' => [339.0, 359.0],
        'jabatan' => [359.0, 539.0],
        'masa' => [539.0, 575.0],
        'pendidikan' => [575.0, 100000.0],
    ];

    /**
     * @return array{count:int,opd:?string,periode:?string,rows:array<int,array<string,mixed>>,errors:array<int,string>}
     */
    public function parse(string $pdfPath): array
    {
        $words = $this->extractWords($pdfPath);

        if ($words === []) {
            throw new \RuntimeException('Tidak ada teks terbaca di PDF. Pastikan PDF DUK punya text layer (bukan hasil scan/gambar).');
        }

        $lines = $this->groupIntoLines($words);

        $opd = null;
        $periode = null;
        $rows = [];
        $current = null;

        foreach ($lines as $line) {
            $text = $this->lineText($line);

            // Baris header/kop: ambil OPD & periode, jangan dianggap data.
            if ($opd === null && stripos($text, 'OPD') !== false && stripos($text, 'NAMA OPD') !== false) {
                $opd = trim(preg_replace('/^.*?NAMA\s+OPD\s*:\s*/i', '', $text)) ?: null;
                continue;
            }
            if ($periode === null && stripos($text, 'PERIODE') !== false) {
                $after = trim((string) preg_replace('/^.*?PERIODE\s*:\s*/i', '', $text));
                $periode = $after !== '' ? $after : null;
            }

            $cols = $this->classify($line);

            // Baris data baru mulai saat kolom NO berisi "N." (mis. "1.").
            $isNew = isset($cols['no']) && preg_match('/^\d+\.$/', trim($cols['no']));

            if ($isNew) {
                if ($current !== null) {
                    $rows[] = $this->finalize($current);
                }
                $current = $cols;
                continue;
            }

            // Baris lanjutan (wrap NIP/TMT/masa): tempel token ke record aktif
            // per kolom yang sama.
            if ($current !== null) {
                foreach ($cols as $col => $val) {
                    if ($col === 'no') {
                        continue;
                    }
                    $current[$col] = trim(($current[$col] ?? '').' '.$val);
                }
            }
        }

        if ($current !== null) {
            $rows[] = $this->finalize($current);
        }

        return [
            'count' => count($rows),
            'opd' => $opd,
            'periode' => $periode,
            'rows' => $rows,
            'errors' => [],
        ];
    }

    /**
     * Parse + simpan sebagai batch impor baru (riwayat per upload). Batch
     * lama TIDAK dihapus — halaman DUK memilih batch mana yang ditampilkan.
     *
     * @return array{count:int,opd:?string,periode:?string,batch_id:int}
     */
    public function import(string $pdfPath, ?string $originalFilename, ?string $userName): array
    {
        $parsed = $this->parse($pdfPath);

        $batchId = DB::connection('kepegawaian')->transaction(function () use ($parsed, $originalFilename, $userName): int {
            $batchId = DB::connection('kepegawaian')->table('duk_impor')->insertGetId([
                'opd' => $parsed['opd'],
                'periode' => $parsed['periode'],
                'original_filename' => $originalFilename,
                'diimpor_oleh_nama' => $userName,
                'jumlah_baris' => $parsed['count'],
                'diimpor_pada' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($parsed['rows'] as $row) {
                DB::connection('kepegawaian')->table('duk')->insert([
                    'duk_impor_id' => $batchId,
                    'urutan_duk' => $row['urutan_duk'],
                    'nama' => $row['nama'],
                    'nip' => $row['nip'],
                    'gol' => $row['gol'],
                    'tmt' => $row['tmt'],
                    'gol_cpns' => $row['gol_cpns'],
                    'tmt_cpns' => $row['tmt_cpns'],
                    'jabatan' => $row['jabatan'],
                    'eselon' => $row['eselon'],
                    'masa_kerja_tahun' => $row['masa_kerja_tahun'],
                    'masa_kerja_bulan' => $row['masa_kerja_bulan'],
                    'pendidikan' => $row['pendidikan'],
                    'opd' => $parsed['opd'],
                    'periode' => $parsed['periode'],
                    'original_filename' => $originalFilename,
                    'diimpor_oleh_nama' => $userName,
                    'diimpor_pada' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $batchId;
        });

        return [
            'count' => $parsed['count'],
            'opd' => $parsed['opd'],
            'periode' => $parsed['periode'],
            'batch_id' => $batchId,
        ];
    }

    /** Jalankan pdftotext -bbox, kembalikan daftar kata berkoordinat. */
    private function extractWords(string $pdfPath): array
    {
        $result = Process::timeout(120)->run(['pdftotext', '-bbox', $pdfPath, '-']);

        if (! $result->successful()) {
            throw new \RuntimeException('pdftotext gagal: '.trim($result->errorOutput()));
        }

        $xml = @simplexml_load_string($result->output());

        if (! $xml instanceof SimpleXMLElement) {
            throw new \RuntimeException('Hasil pdftotext tidak bisa dibaca.');
        }

        $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');

        $words = [];
        foreach ($xml->xpath('//x:word') as $w) {
            $text = trim((string) $w);
            if ($text === '') {
                continue;
            }
            $words[] = [
                'x0' => (float) $w['xMin'],
                'y0' => (float) $w['yMin'],
                'text' => $text,
            ];
        }

        return $words;
    }

    /**
     * Kluster kata jadi baris: urutkan per Y lalu X, pisah baris saat beda Y
     * > 3pt (baris DUK main & lanjutan berjarak ~4.5pt).
     *
     * @return array<int, array<int, array{x0:float,y0:float,text:string}>>
     */
    private function groupIntoLines(array $words): array
    {
        usort($words, fn ($a, $b) => $a['y0'] <=> $b['y0'] ?: $a['x0'] <=> $b['x0']);

        $lines = [];
        $currentY = null;

        foreach ($words as $w) {
            if ($currentY === null || abs($w['y0'] - $currentY) > 3.0) {
                $lines[] = [];
                $currentY = $w['y0'];
            }
            $lines[count($lines) - 1][] = $w;
        }

        // Urutkan tiap baris per X.
        foreach ($lines as &$line) {
            usort($line, fn ($a, $b) => $a['x0'] <=> $b['x0']);
        }

        return $lines;
    }

    /** @param array<int,array{x0:float,y0:float,text:string}> $line */
    private function lineText(array $line): string
    {
        return implode(' ', array_column($line, 'text'));
    }

    /**
     * Kelompokkan token satu baris per kolom berdasarkan xMin.
     *
     * @param array<int,array{x0:float,y0:float,text:string}> $line
     * @return array<string,string>
     */
    private function classify(array $line): array
    {
        $cols = [];

        foreach ($line as $w) {
            foreach (self::COLUMNS as $name => [$min, $max]) {
                if ($w['x0'] >= $min && $w['x0'] < $max) {
                    $cols[$name] = trim(($cols[$name] ?? '').' '.$w['text']);
                    break;
                }
            }
        }

        return $cols;
    }

    /**
     * Ubah kumpulan teks kolom jadi baris siap simpan: pisah jabatan vs
     * eselon (setelah "/" terakhir), pisah tahun/bulan masa kerja, rapikan
     * NIP jadi digit murni.
     *
     * @param array<string,string> $cols
     * @return array<string,mixed>
     */
    private function finalize(array $cols): array
    {
        $no = (int) rtrim(trim($cols['no'] ?? ''), '.');

        // Jabatan & eselon: dokumen menulis "Jabatan / Eselon".
        $jabatanRaw = trim($cols['jabatan'] ?? '');
        $jabatan = $jabatanRaw;
        $eselon = null;
        if (str_contains($jabatanRaw, '/')) {
            $pos = strrpos($jabatanRaw, '/');
            $jabatan = trim(substr($jabatanRaw, 0, $pos));
            $eselon = trim(substr($jabatanRaw, $pos + 1));
        }

        // Masa kerja: "28 tahun 12 bulan" → 28 & 12.
        $masa = trim($cols['masa'] ?? '');
        $tahun = null;
        $bulan = null;
        if (preg_match('/(\d+)/', $masa, $m)) {
            $tahun = (int) $m[1];
        }
        if (preg_match('/(\d+)\s*bulan/i', $masa, $m)) {
            $bulan = (int) $m[1];
        }

        return [
            'urutan_duk' => $no,
            'nama' => trim($cols['nama'] ?? '') ?: null,
            'nip' => ($nip = preg_replace('/\D+/', '', $cols['nip'] ?? '')) !== '' ? $nip : null,
            'gol' => trim($cols['gol'] ?? '') ?: null,
            'tmt' => $this->cleanDate($cols['tmt'] ?? null),
            'gol_cpns' => trim($cols['gol_cpns'] ?? '') ?: null,
            'tmt_cpns' => $this->cleanDate($cols['tmt_cpns'] ?? null),
            'jabatan' => $jabatan ?: null,
            'eselon' => $eselon ?: null,
            'masa_kerja_tahun' => $tahun,
            'masa_kerja_bulan' => $bulan,
            'pendidikan' => trim($cols['pendidikan'] ?? '') ?: null,
        ];
    }

    /**
     * Tanggal DUK ("01-02- 2024") kadang punya spasi setelah dash karena
     * pemisahan token PDF → normalkan jadi "01-02-2024".
     */
    private function cleanDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return str_replace(' ', '', $value) ?: null;
    }
}
