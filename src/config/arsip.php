<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sistem Arsip Digital — Penyimpanan Berkas Lokal
    |--------------------------------------------------------------------------
    |
    | Berkas fisik (dokumen OPD dan arsip pegawai) disimpan di disk lokal
    | server, BUKAN lagi di Google Drive. Dulu modul ini memakai OAuth Drive,
    | tapi dipindah ke disk karena kendala scope/kuota/consent Workspace.
    | Metadata (judul, kategori, tag, status verifikasi) tetap di database —
    | lihat App\Models\Document dan App\Models\Kepegawaian\PegawaiArsip.
    |
    | Isi disk memakai driver Laravel "local" (lihat disk `arsip` di
    | config/filesystems.php). Path di bawah ini relatif terhadap base_path()
    | bila relatif, atau absolut bila diawali "/" — untuk produksi biasanya
    | di-mount volume Docker, mis. ARSIP_LOCAL_ROOT=/var/www/storage/app/arsip
    | yang diarahkan keluar container.
    |
    */

    'local_root' => env('ARSIP_LOCAL_ROOT', storage_path('app/private/arsip')),

    // Ekstensi berkas yang diizinkan saat unggah.
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'],

    // Batas ukuran berkas dalam kilobyte (dipakai langsung oleh Filament
    // FileUpload::maxSize(), yang menerima KB). 50 MB default.
    'max_upload_kb' => (int) env('ARSIP_MAX_UPLOAD_KB', 50 * 1024),

    // Ekstensi yang boleh dipratinjau langsung di browser (inline). Selain ini
    // dipaksa unduh (attachment).
    'inline_preview_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],

];
