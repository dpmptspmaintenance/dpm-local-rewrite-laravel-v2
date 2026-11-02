<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Generator Surat Cuti — Google Sheets Mirror
    |--------------------------------------------------------------------------
    |
    | Mirror satu arah isi tabel `riwayat_generator_cuti` ke spreadsheet
    | Google Sheets TERPISAH (kredensial & ID sendiri, bukan RapatKita).
    | Setiap POST /api/v1/generator-surat-cuti yang sukses menulis ulang
    | seluruh isi tab dari database (rewrite full mirror).
    |
    */

    'sheets' => [
        'enabled' => env('GENERATORCUTI_SHEETS_ENABLED', false),

        // Path file JSON service account (relatif terhadap base_path()).
        'credentials_path' => env('GENERATORCUTI_SHEETS_CREDENTIALS_PATH'),

        // ID spreadsheet Google Sheets khusus generator cuti.
        'spreadsheet_id' => env('GENERATORCUTI_SHEETS_ID'),

        // Nama tab tujuan (dibuat otomatis bila belum ada).
        'tab' => env('GENERATORCUTI_SHEETS_TAB', 'Riwayat'),
    ],

];
