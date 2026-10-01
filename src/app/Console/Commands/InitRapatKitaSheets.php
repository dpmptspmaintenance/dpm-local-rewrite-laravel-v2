<?php

namespace App\Console\Commands;

use App\Services\RapatKita\SheetsMirror;
use Illuminate\Console\Command;

class InitRapatKitaSheets extends Command
{
    protected $signature = 'rapatkita:init-sheets';

    protected $description = 'Setup sheet yang sudah di-share ke service account: buat tab, isi data, verifikasi akses';

    public function handle(): int
    {
        $spreadsheetId = config('rapatkita.sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            $this->error('RAPATKITA_SHEETS_ID belum diisi di .env.');
            $this->line('');
            $this->line('Langkah manual (sekali saja):');
            $this->line('  1. Buka https://docs.google.com/spreadsheets/u/0/create');
            $this->line('  2. Beri judul, misal "RapatKita — Backup Offline".');
            $this->line('  3. Klik tombol Share → tambahkan email service account sebagai Editor:');
            $this->line('     ' . $this->serviceAccountEmail());
            $this->line('  4. Salin ID dari URL: docs.google.com/spreadsheets/d/<ID>/edit');
            $this->line('  5. Isi .env: RAPATKITA_SHEETS_ID=<ID>');
            $this->line('  6. Jalankan ulang: php artisan rapatkita:init-sheets');
            return self::FAILURE;
        }

        $mirror = app(SheetsMirror::class);

        try {
            $mirror->ensureTabs();
            $this->info('Tab "Jadwal" dan "Notulen" siap.');
        } catch (\Throwable $e) {
            $this->error('Gagal akses spreadsheet: ' . $e->getMessage());
            $this->line('');
            $this->line('Pastikan sheet sudah di-share ke service account sebagai Editor:');
            $this->line('  ' . $this->serviceAccountEmail());
            return self::FAILURE;
        }

        try {
            $mirror->syncAll();
            $this->info('Data jadwal + notulen ditulis ke sheet.');
        } catch (\Throwable $e) {
            $this->warn('Tab dibuat, tapi sync data gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('');
        $this->info("Selesai. URL sheet: https://docs.google.com/spreadsheets/d/{$spreadsheetId}");

        return self::SUCCESS;
    }

    private function serviceAccountEmail(): string
    {
        $path = base_path((string) config('rapatkita.sheets.credentials_path'));

        if (! is_file($path)) {
            return '(credentials JSON belum ditemukan)';
        }

        $json = json_decode((string) file_get_contents($path), true);

        return $json['client_email'] ?? '(client_email tidak ada di JSON)';
    }
}
