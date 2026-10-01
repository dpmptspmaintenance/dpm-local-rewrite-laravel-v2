<?php

namespace App\Services\ArsipDigital;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

/**
 * Bungkus tipis di atas Google Drive API v3, dipakai lewat OAuth 3-kaki user
 * biasa (refresh token) ATAU Service Account, dipilih config('arsip.drive.auth_mode').
 *
 * Kenapa ada 2 mode:
 * - oauth            : file diunggah atas nama user Gmail nyata → pakai kuota
 *                      storage akun user itu. Jalan di Gmail gratis, tanpa
 *                      Google Workspace.
 * - service_account  : butuh Google Workspace, karena SA tidak punya kuota
 *                      storage sendiri dan tidak bisa "milik" file di Drive
 *                      pribadi orang lain (HTTP 403 storageQuotaExceeded).
 *
 * Folder induk target (config('arsip.drive.folder_id')):
 * - mode oauth           : folder di Drive user yang sudah konek. Tidak perlu
 *                          di-share — file memang diunggah sebagai user itu.
 * - mode service_account : folder harus dibagikan ke email SA dengan Editor.
 */
class GoogleDriveService
{
    public const MODE_OAUTH = 'oauth';
    public const MODE_SERVICE_ACCOUNT = 'service_account';

    private ?Drive $service = null;

    /**
     * True bila konfigurasi minimal untuk mode auth yang dipilih + folder
     * tujuan + folder penampung "etc" sudah lengkap. Kalau false, upload /
     * move / delete ditolak cepat dengan pesan jelas (jangan biarkan user
     * dapat HTTP error mentah dari Google).
     */
    public function enabled(): bool
    {
        if (! (bool) config('arsip.drive.folder_id')
            || ! (bool) config('arsip.drive.etc_folder_id')) {
            return false;
        }

        return match ($this->mode()) {
            self::MODE_OAUTH => (bool) config('arsip.drive.oauth_client_id')
                && (bool) config('arsip.drive.oauth_client_secret')
                && (bool) config('arsip.drive.oauth_refresh_token'),
            self::MODE_SERVICE_ACCOUNT => (bool) config('arsip.drive.credentials_path'),
            default => false,
        };
    }

    /**
     * URL consent Google untuk alur OAuth pertama kali (dipakai route
     * /arsip/connect-google). Hanya relevan untuk mode oauth.
     */
    public function createAuthUrl(string $redirectUri): string
    {
        $client = $this->oauthClient($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // paksa Google mengeluarkan refresh_token lagi

        return $client->createAuthUrl();
    }

    /**
     * Tukar auth-code yang dikirim Google ke redirect URI menjadi refresh
     * token. Dipanggil sekali dari route callback, hasilnya disimpan admin
     * ke ARSIP_DRIVE_OAUTH_REFRESH_TOKEN.
     *
     * @return array{access_token: string, refresh_token?: string, ...}
     */
    public function exchangeAuthCode(string $code, string $redirectUri): array
    {
        $client = $this->oauthClient($redirectUri);

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new \RuntimeException(
                'Gagal menukar auth-code: '.$token['error']
                .(isset($token['error_description']) ? ' — '.$token['error_description'] : '')
            );
        }

        if (empty($token['refresh_token'])) {
            throw new \RuntimeException(
                'Google tidak mengembalikan refresh_token. Cabut akses aplikasi '
                .'di https://myaccount.google.com/permissions lalu ulangi alur '
                .'consent supaya Google menerbitkan refresh_token baru.'
            );
        }

        return $token;
    }

    /**
     * Unggah berkas fisik ke folder induk Drive.
     *
     * @return array{id: string, webViewLink: ?string}
     */
    public function upload(string $localPath, string $filename, string $mimeType): array
    {
        return $this->uploadTo($localPath, $filename, $mimeType, $this->etcFolderId());
    }

    /**
     * Unggah berkas fisik ke folder Drive tertentu (dipakai DocumentService
     * saat menambah berkas ke dokumen yang sudah punya folder sendiri, agar
     * berkas baru langsung masuk folder itu — bukan singgah dulu di etc).
     *
     * @return array{id: string, webViewLink: ?string}
     */
    public function uploadTo(string $localPath, string $filename, string $mimeType, string $parentFolderId): array
    {
        if (! $this->enabled()) {
            throw new \RuntimeException(
                'Konfigurasi Arsip Digital belum lengkap untuk mode "'
                .$this->mode().'". '
                .($this->mode() === self::MODE_OAUTH
                    ? 'Isi ARSIP_DRIVE_OAUTH_CLIENT_ID / _CLIENT_SECRET / _REFRESH_TOKEN, ARSIP_DRIVE_FOLDER_ID, dan ARSIP_DRIVE_ETC_FOLDER_ID di .env, lalu php artisan config:clear.'
                    : 'Isi ARSIP_DRIVE_CREDENTIALS_PATH, ARSIP_DRIVE_FOLDER_ID, dan ARSIP_DRIVE_ETC_FOLDER_ID di .env. Folder Drive juga harus dibagikan ke email service account dengan hak akses Editor.')
            );
        }

        $file = new DriveFile([
            'name' => $filename,
            'parents' => [$parentFolderId],
        ]);

        $result = $this->service()->files->create($file, [
            'data' => file_get_contents($localPath),
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink',
        ]);

        return [
            'id' => $result->getId(),
            'webViewLink' => $result->getWebViewLink(),
        ];
    }

    /**
     * Buat folder baru di dalam parent, kembalikan ID-nya.
     */
    public function createFolder(string $name, string $parentId): string
    {
        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $result = $this->service()->files->create($folder, ['fields' => 'id']);

        return (string) $result->getId();
    }

    /**
     * Cari subfolder dengan nama persis di dalam parent — dipakai pemanggil
     * yang perlu "cari-atau-buat" folder tanpa risiko duplikat kalau cache ID
     * folder sebelumnya hilang (mis. App\Services\Kepegawaian\PegawaiArsipService).
     * Null bila tidak ditemukan.
     */
    public function findFolder(string $name, string $parentId): ?string
    {
        $escaped = str_replace("'", "\\'", $name);

        $result = $this->service()->files->listFiles([
            'q' => "'{$parentId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false and name='{$escaped}'",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ]);

        $files = $result->getFiles();

        return $files !== [] ? (string) $files[0]->getId() : null;
    }

    /**
     * Cari folder dengan nama itu di dalam parent, buat baru bila belum ada.
     * Lebih aman daripada createFolder() polos untuk folder yang harus unik
     * (mis. satu subfolder per pegawai) — mencegah folder duplikat terbuat
     * berulang kali kalau cache ID sebelumnya hilang/di-flush.
     */
    public function findOrCreateFolder(string $name, string $parentId): string
    {
        return $this->findFolder($name, $parentId) ?? $this->createFolder($name, $parentId);
    }

    /**
     * Pindahkan berkas antar folder (tambah parent baru, cabut parent lama).
     * Bila $oldParentId tidak diberikan, parent SAAT INI dideteksi otomatis
     * lewat Drive API (files.get) — lebih aman daripada mengasumsikan lokasi
     * lama, karena berkas bisa saja sudah pernah dipindah sebelumnya (mis.
     * lewat fitur Pindah Berkas) dan lokasi sebenarnya sudah beda dari yang
     * dicatat di DB.
     */
    public function moveFile(string $fileId, string $newParentId, ?string $oldParentId = null): void
    {
        $oldParents = $oldParentId !== null ? [$oldParentId] : $this->currentParents($fileId);

        // Sudah di parent tujuan — tidak perlu panggilan API lagi.
        if (in_array($newParentId, $oldParents, true) && count($oldParents) === 1) {
            return;
        }

        $params = ['addParents' => $newParentId, 'fields' => 'id, parents'];
        if ($oldParents !== []) {
            $params['removeParents'] = implode(',', $oldParents);
        }

        $this->service()->files->update($fileId, new DriveFile(), $params);
    }

    /**
     * ID folder-folder yang saat ini menjadi induk berkas (biasanya cuma 1,
     * Drive API mengizinkan banyak tapi kita tidak pernah membuatnya begitu).
     *
     * @return list<string>
     */
    public function currentParents(string $fileId): array
    {
        $result = $this->service()->files->get($fileId, ['fields' => 'parents']);

        return $result->getParents() ?? [];
    }

    /**
     * Ganti nama file ATAU folder di Drive (method ini generik, dipakai keduanya).
     * Best-effort di level pemanggil (DocumentService membungkus try/catch).
     */
    public function rename(string $fileId, string $newName): void
    {
        $this->service()->files->update($fileId, new DriveFile(['name' => $newName]), [
            'fields' => 'id, name',
        ]);
    }

    /**
     * Hapus berkas dari Drive. Dipanggil saat rollback (metadata gagal
     * disimpan setelah upload sukses) atau saat dokumen dihapus dari sistem.
     * Diam-diam gagal (log saja) bila file sudah tidak ada — bukan alasan
     * untuk menggagalkan operasi pemanggil.
     */
    public function delete(string $fileId): void
    {
        try {
            $this->service()->files->delete($fileId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[arsip-drive] gagal hapus file Drive: '.$e->getMessage(), ['file_id' => $fileId]);
        }
    }

    /**
     * URL embed untuk iframe preview (dashboard verifikasi & halaman detail).
     */
    public function previewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    private function mode(): string
    {
        return (string) config('arsip.drive.auth_mode', self::MODE_OAUTH);
    }

    /**
     * ID folder induk tempat subfolder "YYYY-MM-DD - Judul" dibuat saat
     * Approve. Dipublikasi supaya DocumentService tidak baca config langsung.
     */
    public function rootFolderId(): string
    {
        return $this->folderId();
    }

    /**
     * ID folder penampung "etc" (tempat berkas pending/rejected).
     */
    public function pendingFolderId(): string
    {
        return $this->etcFolderId();
    }

    private function folderId(): string
    {
        return (string) config('arsip.drive.folder_id');
    }

    private function etcFolderId(): string
    {
        return (string) config('arsip.drive.etc_folder_id');
    }

    private function service(): Drive
    {
        if ($this->service) {
            return $this->service;
        }

        return $this->service = match ($this->mode()) {
            self::MODE_OAUTH => new Drive($this->oauthClientWithRefreshToken()),
            self::MODE_SERVICE_ACCOUNT => new Drive($this->serviceAccountClient()),
            default => throw new \RuntimeException('arsip.drive.auth_mode tidak dikenal: '.$this->mode()),
        };
    }

    /**
     * Client OAuth untuk flow consent/callback (tanpa token apa pun — token
     * di-set di oauthClientWithRefreshToken atau di exchangeAuthCode).
     */
    private function oauthClient(?string $redirectUri = null): Client
    {
        $client = new Client();
        $client->setClientId((string) config('arsip.drive.oauth_client_id'));
        $client->setClientSecret((string) config('arsip.drive.oauth_client_secret'));
        $client->setScopes([Drive::DRIVE_FILE]); // file yang dibuat/dibuka app saja — lebih sempit dari DRIVE penuh
        if ($redirectUri !== null) {
            $client->setRedirectUri($redirectUri);
        }

        return $client;
    }

    private function oauthClientWithRefreshToken(): Client
    {
        $client = $this->oauthClient();
        $client->fetchAccessTokenWithRefreshToken((string) config('arsip.drive.oauth_refresh_token'));

        return $client;
    }

    private function serviceAccountClient(): Client
    {
        $path = base_path((string) config('arsip.drive.credentials_path'));
        if (! is_file($path)) {
            throw new \RuntimeException('Service account JSON Arsip Digital tidak ditemukan: '.$path);
        }

        $client = new Client();
        $client->setScopes([Drive::DRIVE]);
        $client->setAuthConfig($path);

        return $client;
    }
}
