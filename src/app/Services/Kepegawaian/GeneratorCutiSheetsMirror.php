<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\SuratCutiRiwayat;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

/**
 * Mirror tabel `riwayat_generator_cuti` ke spreadsheet Google Sheets
 * eksternal (kredensial & ID terpisah dari RapatKita).
 *
 * Rewrite full mirror: setiap sync menghapus isi tab lalu menulis ulang
 * seluruh riwayat dari database, sehingga sheet selalu cermin persis.
 * Gagal sync melempar exception — pemanggil (API POST) mengubahnya jadi 500.
 */
class GeneratorCutiSheetsMirror
{
    public const TAB_DEFAULT = 'Riwayat';

    private ?Sheets $service = null;

    public function enabled(): bool
    {
        return (bool) config('generatorcuti.sheets.enabled')
            && config('generatorcuti.sheets.spreadsheet_id')
            && config('generatorcuti.sheets.credentials_path');
    }

    public function spreadsheetId(): string
    {
        return (string) config('generatorcuti.sheets.spreadsheet_id');
    }

    public function tab(): string
    {
        $tab = trim((string) config('generatorcuti.sheets.tab', self::TAB_DEFAULT));

        return $tab !== '' ? $tab : self::TAB_DEFAULT;
    }

    /**
     * Tulis ulang seluruh isi tab dari database. Idempotent.
     *
     * @throws \RuntimeException bila kredensial/ID belum dikonfigurasi atau API gagal
     */
    public function syncAll(): void
    {
        if (! $this->enabled()) {
            throw new \RuntimeException('Mirror Google Sheets generator cuti belum dikonfigurasi (GENERATORCUTI_SHEETS_*).');
        }

        $this->ensureTab();

        $headers = ['Batch', 'Waktu', 'Sumber', 'Sumber Label', 'Nama', 'NIP', 'Jenis', 'Hari', 'Rentang', 'Pengajuan', 'Nomor Surat', 'Duplikat', 'Dibuat Oleh', 'Sisa'];

        $rows = [['Riwayat Generator Surat Cuti — mirror otomatis dari aplikasi (ditulis ulang tiap POST API).']];
        $rows[] = $headers;

        $query = SuratCutiRiwayat::query()->orderBy('id');

        try {
            $profilNip = \App\Models\Kepegawaian\PegawaiProfil::query()->pluck('nip', 'nama');
        } catch (\Throwable) {
            $profilNip = collect();
        }

        foreach ($query->cursor() as $r) {
            $sisa = $r->sisa;
            $sisaTeks = is_array($sisa)
                ? collect($sisa)->map(fn ($v, $k): string => $k.': '.$v)->implode('; ')
                : (string) ($sisa ?? '');

            $rows[] = [
                (string) $r->batch,
                $r->created_at?->format('d/m/Y H:i'),
                (string) ($r->sumber ?: ''),
                (string) ($r->sumber_label ?: ''),
                (string) $r->nama,
                (string) ($profilNip[$r->nama] ?? ''),
                (string) ($r->jenis ?: ''),
                (int) $r->hari,
                (string) ($r->rentang ?: ''),
                (string) ($r->pengajuan ?: ''),
                (string) $r->nomor_surat,
                $r->is_duplikat ? 'YA' : '',
                (string) ($r->created_by_nama ?: ($r->created_by ?? '')),
                $sisaTeks,
            ];
        }

        $this->write($rows);
    }

    public function ensureTab(): void
    {
        $spreadsheet = $this->service()->spreadsheets->get($this->spreadsheetId());

        $existing = collect($spreadsheet->getSheets())
            ->map(fn ($sheet) => $sheet->getProperties()->getTitle())
            ->all();

        if (! in_array($this->tab(), $existing, true)) {
            $this->service()->spreadsheets->batchUpdate(
                $this->spreadsheetId(),
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => [
                    new \Google\Service\Sheets\Request([
                        'addSheet' => ['properties' => ['title' => $this->tab()]],
                    ]),
                ]]),
            );
        }
    }

    private function write(array $rows): void
    {
        $tab = $this->tab();

        $this->service()->spreadsheets_values->clear(
            $this->spreadsheetId(),
            "'{$tab}'",
            new \Google\Service\Sheets\ClearValuesRequest(),
        );

        $body = new ValueRange(['values' => $rows]);
        $this->service()->spreadsheets_values->update(
            $this->spreadsheetId(),
            "'{$tab}'!A1",
            $body,
            ['valueInputOption' => 'RAW'],
        );
    }

    private function service(): Sheets
    {
        if ($this->service) {
            return $this->service;
        }

        $path = base_path((string) config('generatorcuti.sheets.credentials_path'));

        if (! is_file($path)) {
            throw new \RuntimeException('Service account JSON generator cuti tidak ditemukan: '.$path);
        }

        $client = new Client();
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig($path);

        return $this->service = new Sheets($client);
    }
}
