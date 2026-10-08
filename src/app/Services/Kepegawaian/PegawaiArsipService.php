<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\PegawaiArsip;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\User;
use App\Services\ArsipDigital\LocalArsipStorage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orkestrasi upload/hapus arsip berkas pegawai. Reuse LocalArsipStorage yang
 * sama dipakai modul Arsip Digital (/arsip) — bukan penyimpanan kedua — tapi
 * lewat subfolder "Kepegawaian/pegawai" tersendiri di dalam root disk arsip,
 * supaya berkas pegawai tidak bercampur dengan dokumen OPD umum di modul
 * /arsip maupun berkas DRH Satya Lancana ("Kepegawaian/drh").
 *
 * Struktur folder disk: {ARSIP_LOCAL_ROOT}/Kepegawaian/pegawai/{NIP - Nama}/
 * — satu subfolder per pegawai, dibuat sekali (lazy, saat berkas pertama
 * pegawai itu diunggah) lalu path-nya di-cache (folder tak berpindah untuk
 * NIP yang sama; cache diberi TTL agar perbaikan/pemindahan folder manual
 * tidak tertahan permanen).
 */
class PegawaiArsipService
{
    private const CACHE_ROOT = 'arsip-kepegawaian:root-folder-path';

    private const CACHE_PEGAWAI_PREFIX = 'arsip-kepegawaian:pegawai-folder-path:';

    private const CACHE_TTL_DAYS = 7;

    public function __construct(private readonly LocalArsipStorage $drive) {}

    /**
     * Unggah satu berkas untuk satu pegawai: tulis ke disk dulu (folder
     * khusus pegawai itu), baru catat metadata. Kalau disk gagal, tak ada
     * baris DB yang dibuat. Kalau DB gagal SETELAH berkas tersimpan, berkas
     * yang baru tersimpan dihapus lagi dari disk — pola sama seperti
     * App\Services\ArsipDigital\DocumentService::upload().
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     */
    public function upload(string $nip, array $file, User $uploader, ?string $judul, ?string $kategori): PegawaiArsip
    {
        $folderPath = $this->pegawaiFolderId($nip);

        $uploaded = $this->drive->uploadTo($file['path'], $file['filename'], $file['mime'], $folderPath);

        try {
            return PegawaiArsip::create([
                'nip' => $nip,
                'judul' => filled($judul) ? $judul : pathinfo($file['filename'], PATHINFO_FILENAME),
                'kategori' => filled($kategori) ? trim($kategori) : null,
                'storage_path' => $uploaded['id'],
                'original_filename' => $file['filename'],
                'file_extension' => $file['extension'],
                'mime_type' => $file['mime'],
                'file_size' => $file['size'],
                'uploaded_by' => $uploader->id,
                'uploaded_by_nama' => $uploader->nama,
            ]);
        } catch (\Throwable $e) {
            $this->drive->delete($uploaded['id']);
            Log::error('[arsip-kepegawaian] upload dibatalkan, berkas disk dihapus: '.$e->getMessage());

            throw $e;
        }
    }

    public function delete(PegawaiArsip $arsip): void
    {
        if ($arsip->storage_path) {
            $this->drive->delete($arsip->storage_path);
        }

        $arsip->delete();
    }

    /**
     * Ganti berkas fisik satu arsip (opsional saat edit metadata). Tulis
     * berkas baru dulu ke disk, baru update baris; berkas lama dihapus
     * SETELAH update sukses supaya tidak kehilangan berkas bila update DB
     * gagal. Bila berkas baru gagal diunggah, baris lama tak tersentuh.
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     */
    public function replaceFile(PegawaiArsip $arsip, array $file): void
    {
        $folderPath = $this->pegawaiFolderId($arsip->nip);

        $uploaded = $this->drive->uploadTo($file['path'], $file['filename'], $file['mime'], $folderPath);
        $oldPath = $arsip->storage_path;

        try {
            $arsip->update([
                'storage_path' => $uploaded['id'],
                'original_filename' => $file['filename'],
                'file_extension' => $file['extension'],
                'mime_type' => $file['mime'],
                'file_size' => $file['size'],
            ]);
        } catch (\Throwable $e) {
            // Rollback: hapus berkas baru, baris lama tetap utuh.
            $this->drive->delete($uploaded['id']);
            Log::error('[arsip-kepegawaian] ganti berkas dibatalkan, berkas baru dihapus: '.$e->getMessage());

            throw $e;
        }

        if ($oldPath && $oldPath !== $uploaded['id']) {
            $this->drive->delete($oldPath);
        }
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
     * Path subfolder disk khusus satu pegawai (dibuat sekali, lazy). Struktur:
     * "Kepegawaian/pegawai/{NIP} - {Nama}" — nama folder "{NIP} - {Nama}"
     * di-cache per NIP dengan TTL karena folder tak berpindah untuk pegawai
     * yang sama, tapi TTL mencegah path basi tertahan permanen bila folder
     * dipindah/rename manual.
     */
    private function pegawaiFolderId(string $nip): string
    {
        return Cache::remember(self::CACHE_PEGAWAI_PREFIX.$nip, now()->addDays(self::CACHE_TTL_DAYS), function () use ($nip): string {
            $root = $this->pegawaiRootFolderId();
            $nama = PegawaiProfil::query()->where('nip', $nip)->value('nama') ?: $nip;
            $folderName = "{$nip} - {$nama}";

            return $this->drive->findOrCreateFolder($folderName, $root);
        });
    }

    /**
     * Path subfolder "Kepegawaian/pegawai" di dalam root disk arsip
     * (ARSIP_LOCAL_ROOT) — dibuat sekali, lazy, lalu di-cache dengan TTL.
     * Dipisah dari "Kepegawaian/drh" supaya berkas pegawai tidak bercampur
     * dengan berkas DRH Satya Lancana.
     */
    private function pegawaiRootFolderId(): string
    {
        return Cache::remember(self::CACHE_ROOT, now()->addDays(self::CACHE_TTL_DAYS), function (): string {
            $kepegawaian = $this->drive->findOrCreateFolder('Kepegawaian', $this->drive->rootFolderId());

            return $this->drive->findOrCreateFolder('pegawai', $kepegawaian);
        });
    }
}
