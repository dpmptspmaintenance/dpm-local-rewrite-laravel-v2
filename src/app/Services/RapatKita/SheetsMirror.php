<?php

namespace App\Services\RapatKita;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class SheetsMirror
{
    public const SCHEDULE_TAB = 'Jadwal';

    public const NOTULEN_TAB = 'Notulen';

    private ?Sheets $service = null;

    public function enabled(): bool
    {
        return (bool) config('rapatkita.sheets.enabled')
            && config('rapatkita.sheets.spreadsheet_id')
            && config('rapatkita.sheets.credentials_path');
    }

    /**
     * Tulis ulang seluruh isi dua tab. Idempotent: setiap panggilan menghapus
     * data lama lalu menulis ulang dari database, sehingga sheet selalu cermin
     * persis dari database.
     */
    public function syncAll(): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $this->ensureTabs();
            $this->writeScheduleTab();
            $this->writeNotulenTab();
        } catch (\Throwable $e) {
            Log::error('[rapatkita-sheets] sync gagal: '.$e->getMessage());
        }
    }

    /**
     * Pastikan tab Jadwal dan Notulen ada di spreadsheet. Dibuat bila belum ada.
     * Berlaku pada sheet yang sudah dibagikan (shared) ke service account.
     */
    public function ensureTabs(): void
    {
        $spreadsheet = $this->service()->spreadsheets->get($this->spreadsheetId());

        $existing = collect($spreadsheet->getSheets())
            ->map(fn ($sheet) => $sheet->getProperties()->getTitle())
            ->all();

        $requests = [];
        foreach ([self::SCHEDULE_TAB, self::NOTULEN_TAB] as $tab) {
            if (! in_array($tab, $existing, true)) {
                $requests[] = new \Google\Service\Sheets\Request([
                    'addSheet' => ['properties' => ['title' => $tab]],
                ]);
            }
        }

        if ($requests !== []) {
            $this->service()->spreadsheets->batchUpdate(
                $this->spreadsheetId(),
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests]),
            );
        }
    }

    public function writeScheduleTab(): void
    {
        $headers = ['ID', 'Nama Kegiatan', 'Lokasi', 'Disposisi', 'Dihadiri Oleh', 'Pelaksana', 'Deskripsi', 'Mulai', 'Selesai', 'Bidang Pembuat', 'Dibuat Oleh', 'Status'];

        $rows = [['Data Jadwal Rapat — dibaca offline (backup otomatis dari aplikasi).']];
        $rows[] = $headers;

        $names = \App\Models\User::query()->pluck('nama', 'id');

        foreach (\App\Models\RapatKitaSchedule::aktif()->orderBy('start_datetime')->get() as $s) {
            $rows[] = [
                $s->id,
                $s->title,
                $s->lokasi,
                $s->dispo,
                $s->dihadiri,
                $s->pelaksana,
                $s->description,
                $s->start_datetime?->format('d/m/Y H:i'),
                $s->end_datetime?->format('d/m/Y H:i'),
                $s->bidang_pembuat_jadwal,
                $names[$s->created_by] ?? $s->created_by,
                'Aktif',
            ];
        }

        $this->write(self::SCHEDULE_TAB, $rows);
    }

    public function writeNotulenTab(): void
    {
        $headers = ['ID', 'ID Kegiatan', 'Nama Kegiatan', 'Tanggal', 'Jam', 'Ketua', 'Sekretaris', 'Anggota', 'Susunan', 'Pembahasan', 'Hasil', 'Nama Pimpinan', 'Jabatan Pimpinan', 'Nama Notulis', 'Jabatan Notulis', 'Dibuat Oleh'];

        $rows = [['Data Notulen Rapat — dibaca offline (backup otomatis dari aplikasi).']];
        $rows[] = $headers;

        foreach (\App\Models\RapatKitaNotulen::orderBy('tanggal')->get() as $n) {
            $rows[] = [
                $n->id,
                $n->id_kegiatan,
                $n->nama_kegiatan,
                $n->tanggal?->format('d/m/Y'),
                $n->jam_mulai,
                $n->ketua,
                $n->sekretaris,
                $n->anggota,
                $n->susunan,
                $n->pembahasan,
                $n->hasil,
                $n->nama_pimpinan,
                $n->jabatan_pimpinan,
                $n->nama_notulis,
                $n->jabatan_notulis,
                $n->created_by,
            ];
        }

        $this->write(self::NOTULEN_TAB, $rows);
    }

    private function write(string $tab, array $rows): void
    {
        $range = "'{$tab}'!A1";

        $this->service()->spreadsheets_values->clear(
            $this->spreadsheetId(),
            "'{$tab}'",
            new \Google\Service\Sheets\ClearValuesRequest(),
        );

        $body = new ValueRange(['values' => $rows]);
        $this->service()->spreadsheets_values->update(
            $this->spreadsheetId(),
            $range,
            $body,
            ['valueInputOption' => 'RAW'],
        );
    }

    private function spreadsheetId(): string
    {
        return (string) config('rapatkita.sheets.spreadsheet_id');
    }

    private function service(): Sheets
    {
        if ($this->service) {
            return $this->service;
        }

        $path = base_path((string) config('rapatkita.sheets.credentials_path'));
        if (! is_file($path)) {
            throw new \RuntimeException('Service account JSON tidak ditemukan: '.$path);
        }

        $client = new Client();
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig($path);

        return $this->service = new Sheets($client);
    }
}
