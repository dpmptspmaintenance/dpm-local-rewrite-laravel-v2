<?php

namespace App\Console\Commands;

use App\Services\Kepegawaian\GeneratorCutiSheetsMirror;
use Illuminate\Console\Command;

class InitGeneratorCutiSheets extends Command
{
    protected $signature = 'generatorcuti:init-sheets';

    protected $description = 'Setup spreadsheet mirror riwayat generator cuti: buat tab, isi data, verifikasi akses';

    public function handle(): int
    {
        $spreadsheetId = config('generatorcuti.sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            $this->error('GENERATORCUTI_SHEETS_ID belum diisi di .env.');
            $this->line('');
            $this->line('Langkah manual (sekali saja):');
            $this->line('  1. Buka https://docs.google.com/spreadsheets/u/0/create');
            $this->line('  2. Beri judul, misal "Generator Cuti — Riwayat".');
            $this->line('  3. Klik tombol Share → tambahkan email service account sebagai Editor:');
            $this->line('     '.$this->serviceAccountEmail());
            $this->line('  4. Salin ID dari URL: docs.google.com/spreadsheets/d/<ID>/edit');
            $this->line('  5. Isi .env: GENERATORCUTI_SHEETS_ENABLED=true, GENERATORCUTI_SHEETS_CREDENTIALS_PATH, GENERATORCUTI_SHEETS_ID=<ID>');
            $this->line('  6. Jalankan ulang: php artisan generatorcuti:init-sheets');

            return self::FAILURE;
        }

        $mirror = app(GeneratorCutiSheetsMirror::class);

        try {
            $mirror->ensureTab();
            $this->info('Tab "'.$mirror->tab().'" siap.');
        } catch (\Throwable $e) {
            $this->error('Gagal akses spreadsheet: '.$e->getMessage());
            $this->line('');
            $this->line('Pastikan sheet sudah di-share ke service account sebagai Editor:');
            $this->line('  '.$this->serviceAccountEmail());

            return self::FAILURE;
        }

        try {
            $mirror->syncAll();
            $this->info('Data riwayat ditulis ke sheet.');
        } catch (\Throwable $e) {
            $this->warn('Tab dibuat, tapi sync data gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('');
        $this->info("Selesai. URL sheet: https://docs.google.com/spreadsheets/d/{$spreadsheetId}");

        return self::SUCCESS;
    }

    private function serviceAccountEmail(): string
    {
        $path = base_path((string) config('generatorcuti.sheets.credentials_path'));

        if (! is_file($path)) {
            return '(credentials JSON belum ditemukan)';
        }

        $json = json_decode((string) file_get_contents($path), true);

        return $json['client_email'] ?? '(client_email tidak ada di JSON)';
    }
}
