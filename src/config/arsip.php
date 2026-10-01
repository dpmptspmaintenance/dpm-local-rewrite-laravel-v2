<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sistem Arsip Digital — Google Drive Storage
    |--------------------------------------------------------------------------
    |
    | File fisik disimpan di Google Drive lewat Service Account (bukan Google
    | Sheets seperti mirror RapatKita, tapi Drive API murni). Metadata (judul,
    | kategori, tag, status verifikasi) tetap di database lokal — lihat
    | App\Models\Document.
    |
    */

    'drive' => [
        // Mode auth: 'oauth' = 3-legged OAuth user biasa (Gmail gratis OK);
        // 'service_account' = file JSON SA (butuh Google Workspace, karena SA
        // gratisan tidak punya kuota storage).
        'auth_mode' => env('ARSIP_DRIVE_AUTH_MODE', 'oauth'),

        // === Mode oauth (Gmail gratis) ===
        // Client ID/Secret dari Google Cloud Console → Credentials → OAuth client ID.
        'oauth_client_id' => env('ARSIP_DRIVE_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('ARSIP_DRIVE_OAUTH_CLIENT_SECRET'),

        // Refresh token hasil pertukaran auth-code pertama kali. Diisi SEKALI
        // oleh artisan arsip:connect-google, lalu dipakai untuk fetch access
        // token baru tiap kali kadaluarsa.
        'oauth_refresh_token' => env('ARSIP_DRIVE_OAUTH_REFRESH_TOKEN'),

        // URI yang di-redirect Google setelah user klik Allow di layar consent.
        // HARUS sama persis dengan yang didaftarkan di OAuth client (termasuk
        // http/https, port, dan trailing slash atau ketiadaannya).
        'oauth_redirect_uri' => env('ARSIP_DRIVE_OAUTH_REDIRECT_URI'),

        // === Mode service_account (Workspace only) ===
        // Path file JSON service account (relatif terhadap base_path()).
        'credentials_path' => env('ARSIP_DRIVE_CREDENTIALS_PATH'),

        // ID folder induk di Google Drive tempat semua dokumen diunggah.
        // Mode oauth:     folder di Drive akun user yang login (share tidak perlu,
        //                 karena file memang diunggah sebagai user itu).
        // Mode service_account: folder harus dibagikan ke email SA dengan Editor.
        'folder_id' => env('ARSIP_DRIVE_FOLDER_ID'),

        // Folder penampung berkas BELUM ter-approve (umumnya subfolder "etc"
        // di dalam folder induk di atas). Upload baru masuk sini; saat admin
        // menekan Approve, berkas dipindah ke subfolder "YYYY-MM-DD - Judul"
        // yang dibuat otomatis di folder induk.
        'etc_folder_id' => env('ARSIP_DRIVE_ETC_FOLDER_ID'),
    ],

    // Ekstensi berkas yang diizinkan saat unggah.
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'],

    // Batas ukuran berkas dalam kilobyte (dipakai langsung oleh Filament
    // FileUpload::maxSize(), yang menerima KB). 50 MB default.
    'max_upload_kb' => (int) env('ARSIP_MAX_UPLOAD_KB', 50 * 1024),

];
