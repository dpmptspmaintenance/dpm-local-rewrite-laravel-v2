<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RapatKita Google Sheets Mirror
    |--------------------------------------------------------------------------
    |
    | Backup baca-only saat server (WSL/cloudflare tunnel) mati. Database tetap
    | sumber utama; sheet cuma mirror satu arah yang ditulis ulang tiap ada
    | perubahan jadwal/notulen.
    |
    */

    'sheets' => [
        'enabled' => env('RAPATKITA_SHEETS_ENABLED', false),

        // Path file JSON service account (relatif terhadap base_path()).
        'credentials_path' => env('RAPATKITA_SHEETS_CREDENTIALS_PATH'),

        // ID spreadsheet Google Sheets. Kosong = mirror belum di-init.
        'spreadsheet_id' => env('RAPATKITA_SHEETS_ID'),

        // Email yang diberi akses tulis/baca ke sheet (untuk buka dari HP saat offline).
        'share_email' => env('RAPATKITA_SHEETS_SHARE_EMAIL'),
    ],

];
