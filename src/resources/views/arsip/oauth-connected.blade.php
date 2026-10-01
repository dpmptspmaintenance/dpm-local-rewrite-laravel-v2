<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Arsip Digital — Google Terkoneksi</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 720px; margin: 4rem auto; padding: 0 1rem; line-height: 1.55; }
        code { background: #f3f4f6; padding: .15rem .4rem; border-radius: 4px; font-size: .92em; }
        pre { background: #111827; color: #f9fafb; padding: .85rem 1rem; border-radius: 6px; overflow-x: auto; font-size: .85rem; }
        .ok { color: #047857; }
    </style>
</head>
<body>
    <h1 class="ok">✓ Google Drive berhasil dikoneksikan</h1>
    <p>Tambahkan baris ini ke <code>.env</code> aplikasi, lalu jalankan <code>php artisan config:clear</code> (atau restart container app):</p>

    <pre>ARSIP_DRIVE_AUTH_MODE=oauth
ARSIP_DRIVE_OAUTH_REFRESH_TOKEN={{ $refreshToken }}</pre>

    <p class="ok"><strong>Simpan token ini di tempat aman.</strong> Siapa pun yang memegangnya bisa mengakses file Google Drive akun yang baru saja kamu koneksikan — jangan commit ke git atau kirim lewat channel publik.</p>

    <p>Setelah <code>.env</code> diisi, halaman ini tidak akan dipakai lagi. Kamu bisa tutup tab ini dan langsung coba unggah dokumen di <a href="/arsip/dokumen/create">/arsip/dokumen/create</a>.</p>
</body>
</html>
