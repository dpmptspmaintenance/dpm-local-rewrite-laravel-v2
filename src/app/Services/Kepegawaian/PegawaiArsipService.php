<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\PegawaiArsip;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\User;
use App\Services\ArsipDigital\GoogleDriveService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orkestrasi upload/hapus arsip berkas pegawai. Reuse GoogleDriveService yang
 * sama dipakai modul Arsip Digital (/arsip) — bukan kredensial/Service Account
 * baru — tapi lewat subfolder "Kepegawaian" tersendiri di dalam folder induk
 * arsip (ARSIP_DRIVE_FOLDER_ID), supaya berkas pegawai tidak bercampur dengan
 * dokumen OPD umum di modul /arsip.
 *
 * Struktur folder Drive: {ARSIP_DRIVE_FOLDER_ID}/Kepegawaian/{NIP - Nama}/
 * — satu subfolder per pegawai, dibuat sekali (lazy, saat berkas pertama
 * pegawai itu diunggah) lalu ID-nya di-cache (folder Drive tak pernah
 * berubah untuk NIP yang sama, cache permanen sampai manual di-flush).
 */
class PegawaiArsipService
{
    private const CACHE_ROOT = 'arsip-kepegawaian:root-folder-id';

    private const CACHE_PEGAWAI_PREFIX = 'arsip-kepegawaian:pegawai-folder-id:';

    public function __construct(private readonly GoogleDriveService $drive) {}

    /**
     * Unggah satu berkas untuk satu pegawai: kirim ke Drive dulu (folder
     * khusus pegawai itu), baru catat metadata. Kalau Drive gagal, tak ada
     * baris DB yang dibuat. Kalau DB gagal SETELAH Drive sukses, berkas yang
     * baru terunggah dihapus lagi dari Drive — pola sama seperti
     * App\Services\ArsipDigital\DocumentService::upload().
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     */
    public function upload(string $nip, array $file, User $uploader, ?string $judul, ?string $kategori): PegawaiArsip
    {
        $folderId = $this->pegawaiFolderId($nip);

        $uploaded = $this->drive->uploadTo($file['path'], $file['filename'], $file['mime'], $folderId);

        try {
            return PegawaiArsip::create([
                'nip' => $nip,
                'judul' => filled($judul) ? $judul : pathinfo($file['filename'], PATHINFO_FILENAME),
                'kategori' => filled($kategori) ? trim($kategori) : null,
                'google_file_id' => $uploaded['id'],
                'google_web_view_link' => $uploaded['webViewLink'],
                'original_filename' => $file['filename'],
                'file_extension' => $file['extension'],
                'mime_type' => $file['mime'],
                'file_size' => $file['size'],
                'uploaded_by' => $uploader->id,
                'uploaded_by_nama' => $uploader->nama,
            ]);
        } catch (\Throwable $e) {
            $this->drive->delete($uploaded['id']);
            Log::error('[arsip-kepegawaian] upload dibatalkan, file Drive dihapus: '.$e->getMessage());

            throw $e;
        }
    }

    public function delete(PegawaiArsip $arsip): void
    {
        $this->drive->delete($arsip->google_file_id);
        $arsip->delete();
    }

    /**
     * Daftar nama kategori yang sudah pernah dipakai — untuk suggestions di
     * TagsInput/Select kategori bebas-ketik (bukan tabel master terpisah).
     *
     * @return list<string>
     */
    public function kategoriSuggestions(): array
    {
        return PegawaiArsip::query()
            ->whereNotNull('kategori')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * ID subfolder Drive khusus satu pegawai (dibuat sekali, lazy). Nama
     * folder "{NIP} - {Nama}" — hasilnya di-cache permanen per NIP karena
     * folder Drive tidak pernah pindah/berubah ID untuk pegawai yang sama.
     */
    private function pegawaiFolderId(string $nip): string
    {
        return Cache::rememberForever(self::CACHE_PEGAWAI_PREFIX.$nip, function () use ($nip): string {
            $root = $this->rootFolderId();
            $nama = PegawaiProfil::query()->where('nip', $nip)->value('nama') ?: $nip;
            $folderName = "{$nip} - {$nama}";

            return $this->drive->findOrCreateFolder($folderName, $root);
        });
    }

    /**
     * ID subfolder "Kepegawaian" di dalam folder induk arsip
     * (ARSIP_DRIVE_FOLDER_ID) — dibuat sekali, lazy, lalu di-cache permanen.
     */
    private function rootFolderId(): string
    {
        return Cache::rememberForever(self::CACHE_ROOT, fn (): string => $this->drive->findOrCreateFolder(
            'Kepegawaian',
            $this->drive->rootFolderId(),
        ));
    }
}
