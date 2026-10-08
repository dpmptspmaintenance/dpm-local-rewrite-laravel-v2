<?php

namespace App\Services\ArsipDigital;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Backend penyimpanan berkas Arsip Digital berbasis disk lokal — pengganti
 * App\Services\ArsipDigital\GoogleDriveService setelah modul ini berhenti
 * memakai Google Drive.
 *
 * Konsep pemetaan Drive → disk lokal:
 * - "file id" Drive     → path relatif berkas di dalam disk `arsip`
 *   (mis. "etc/2026-10-08 - Judul/berkas.pdf").
 * - "folder id" Drive   → path relatif direktori di dalam disk `arsip`.
 * - folder "etc"        → direktori root disk `arsip` (semua unggahan baru
 *   langsung di sini; saat approve dipindah ke subfolder
 *   "YYYY-MM-DD - Judul").
 *
 * Slug nama folder dibuat aman-filesystem oleh caller (folderName() di
 * Document, atau "{NIP} - {Nama}" di PegawaiArsipService) — kelas ini tetap
 * membersihkan segmen agar tidak ada traversal keluar root disk.
 */
class LocalArsipStorage
{
    public function __construct(private readonly string $disk = 'arsip') {}

    /**
     * Selalu siap: disk lokal tak butuh kredensial. Dipertahankan supaya
     * pemanggil lama (cek GoogleDriveService::enabled()) tak berubah.
     */
    public function enabled(): bool
    {
        return true;
    }

    /**
     * Unggah berkas ke folder penampung "etc" (root disk). Mengembalikan
     * ['id' => path relatif, 'webViewLink' => null] — bentuk array sama
     * seperti GoogleDriveService agar pemanggil tak perlu berubah.
     *
     * @return array{id: string, webViewLink: ?string}
     */
    public function upload(string $localPath, string $filename, string $mimeType): array
    {
        return $this->uploadTo($localPath, $filename, $mimeType, $this->etcFolderId());
    }

    /**
     * @return array{id: string, webViewLink: ?string}
     */
    public function uploadTo(string $localPath, string $filename, string $mimeType, string $parentFolderId): array
    {
        $path = $this->joinPath($parentFolderId, $this->safeSegment($filename));

        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Berkas sumber tidak bisa dibuka: '.$localPath);
        }

        try {
            Storage::disk($this->disk)->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return [
            'id' => $path,
            'webViewLink' => null,
        ];
    }

    /**
     * Buat folder baru di dalam parent, kembalikan path relatifnya.
     */
    public function createFolder(string $name, string $parentId): string
    {
        $path = $this->joinPath($parentId, $this->safeSegment($name));
        Storage::disk($this->disk)->makeDirectory($path);

        return $path;
    }

    /**
     * Cari subfolder dengan nama persis di dalam parent. Null bila tidak ada.
     */
    public function findFolder(string $name, string $parentId): ?string
    {
        $path = $this->joinPath($parentId, $this->safeSegment($name));

        return Storage::disk($this->disk)->directoryExists($path) ? $path : null;
    }

    /**
     * Cari folder dengan nama itu di dalam parent, buat bila belum ada.
     */
    public function findOrCreateFolder(string $name, string $parentId): string
    {
        return $this->findFolder($name, $parentId) ?? $this->createFolder($name, $parentId);
    }

    /**
     * Pindahkan berkas antar folder, kembalikan path relatif BARU. $oldParentId
     * dipertahankan di signature demi kompatibilitas pemanggil; di disk lokal
     * path sumber sudah cukup (berkas tunggal punya satu lokasi).
     */
    public function moveFile(string $fileId, string $newParentId, ?string $oldParentId = null): string
    {
        $destination = $this->joinPath($newParentId, $this->basename($fileId));

        if ($fileId === $destination) {
            return $destination;
        }

        $disk = Storage::disk($this->disk);
        $disk->makeDirectory($newParentId);
        $disk->move($fileId, $destination);

        return $destination;
    }

    /**
     * Parent SAAT INI (satu folder) dari path relatif — sederajat dengan
     * GoogleDriveService::currentParents().
     *
     * @return list<string>
     */
    public function currentParents(string $fileId): array
    {
        $dir = $this->dirname($fileId);

        return [$dir];
    }

    /**
     * Ganti nama berkas ATAU folder (generik, dipakai keduanya). Kembalikan
     * path relatif BARU (untuk berkas: nama berubah; untuk folder: seluruh
     * isi ikut berpindah, pemanggil harus memperbarui storage_path anak-anak).
     */
    public function rename(string $fileId, string $newName): string
    {
        $destination = $this->joinPath($this->dirname($fileId), $this->safeSegment($newName));

        if ($fileId === $destination) {
            return $destination;
        }

        Storage::disk($this->disk)->move($fileId, $destination);

        return $destination;
    }

    /**
     * Hapus berkas dari disk. Diam-diam gagal (log saja) bila tak ada —
     * bukan alasan menggagalkan operasi pemanggil.
     */
    public function delete(string $fileId): void
    {
        try {
            $disk = Storage::disk($this->disk);

            if ($disk->exists($fileId)) {
                $disk->delete($fileId);

                return;
            }

            if ($disk->directoryExists($fileId)) {
                $disk->deleteDirectory($fileId);
            }
        } catch (\Throwable $e) {
            Log::warning('[arsip-local] gagal hapus berkas: '.$e->getMessage(), ['path' => $fileId]);
        }
    }

    /**
     * Hapus direktori (folder dokumen) bila ada — dipakai saat seluruh berkas
     * dokumen sudah dibuang supaya tidak menyisakan folder kosong menumpuk.
     */
    public function deleteDirectory(string $path): void
    {
        if ($path === '') {
            return;
        }

        try {
            $disk = Storage::disk($this->disk);

            if ($disk->directoryExists($path)) {
                $disk->deleteDirectory($path);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[arsip-local] gagal hapus folder: '.$e->getMessage(), ['path' => $path]);
        }
    }

    /**
     * Path absolut berkas di disk — dipakai controller untuk stream/unduh.
     */
    public function absolutePath(string $fileId): string
    {
        return Storage::disk($this->disk)->path($fileId);
    }

    public function exists(string $fileId): bool
    {
        return Storage::disk($this->disk)->exists($fileId);
    }

    /**
     * ID/path folder induk tempat subfolder "YYYY-MM-DD - Judul" dibuat
     * (root disk lokal).
     */
    public function rootFolderId(): string
    {
        return '';
    }

    /**
     * ID/path folder penampung "etc" (root disk lokal).
     */
    public function pendingFolderId(): string
    {
        return $this->etcFolderId();
    }

    private function etcFolderId(): string
    {
        return '';
    }

    private function joinPath(string $parent, string $child): string
    {
        $parent = trim($parent, '/');

        return $parent === '' ? $child : $parent.'/'.$child;
    }

    private function dirname(string $path): string
    {
        $dir = str_replace('\\', '/', dirname($path));

        return $dir === '.' || $dir === '/' ? '' : $dir;
    }

    private function basename(string $path): string
    {
        return basename(str_replace('\\', '/', $path));
    }

    /**
     * Bersihkan satu segmen nama berkas/folder: buang karakter terlarang dan
     * cegah ".." agar tidak keluar dari root disk.
     */
    private function safeSegment(string $name): string
    {
        $name = str_replace(['\\', '/'], '-', $name);
        $name = preg_replace('/[:*?"<>|]/', '-', $name) ?? $name;
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            return 'berkas';
        }

        return $name;
    }
}
