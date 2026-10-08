<?php

namespace App\Services\ArsipDigital;

use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orkestrasi unggah/verifikasi dokumen — satu-satunya tempat yang boleh
 * menulis ke tabel documents / document_files + memanggil LocalArsipStorage
 * bersamaan. Lihat AGENTS.md § 5: transaksi database wajib aman terhadap
 * kegagalan salah satu sisi (storage vs DB).
 *
 * Model list flat: dokumen = metadata (judul/dll), berkas fisik = baris
 * document_files. Tidak ada konsep "berkas utama" — semua setara.
 */
class DocumentService
{
    public function __construct(private readonly LocalArsipStorage $drive) {}

    /**
     * Unggah satu berkas: tulis ke disk dulu, lalu catat metadata di DB.
     * Kalau tulis disk gagal, tidak ada baris DB yang pernah dibuat. Kalau
     * penyimpanan DB gagal SETELAH berkas tersimpan, berkas yang baru
     * tersimpan dihapus lagi dari disk.
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     * @param  list<string>  $tagNames
     */
    public function upload(array $file, User $uploader, ?int $categoryId, ?string $title, array $tagNames = [], ?int $ownershipId = null): Document
    {
        // Berkas mentah (belum diverifikasi) masuk ke "{tahun}/etc/" — tahun
        // dari tanggal unggah (now()), bukan created_at record (belum ada).
        $uploaded = $this->drive->uploadTo(
            $file['path'],
            $file['filename'],
            $file['mime'],
            $this->pendingFolderForYear(now()->year),
        );

        try {
            return DB::transaction(function () use ($file, $uploader, $categoryId, $title, $tagNames, $uploaded, $ownershipId): Document {
                $document = Document::create([
                    'title' => filled($title) ? $title : Document::fallbackTitle($file['filename']),
                    'source_type' => Document::SOURCE_FILE,
                    'status' => Document::STATUS_PENDING,
                    'category_id' => $categoryId,
                    'ownership_id' => $ownershipId,
                    'created_by' => $uploader->id,
                ]);

                $document->files()->create([
                    'storage_path' => $uploaded['id'],
                    'original_filename' => $file['filename'],
                    'file_extension' => $file['extension'],
                    'mime_type' => $file['mime'],
                    'file_size' => $file['size'],
                ]);

                $document->tags()->sync($this->resolveTags($tagNames));

                return $document;
            });
        } catch (\Throwable $e) {
            $this->drive->delete($uploaded['id']);
            Log::error('[arsip] upload dibatalkan, berkas disk dihapus: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Daftarkan satu dokumen berbasis tautan (Google Drive / web) — TIDAK ada
     * berkas fisik yang diunggah; baris cuma menyimpan referensi URL. Ikut
     * alur verifikasi sama seperti berkas (status awal pending_review).
     */
    public function registerUrl(string $url, User $uploader, ?int $categoryId, ?string $title, array $tagNames = [], ?int $ownershipId = null): Document
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \RuntimeException("URL tidak valid (harus http/https): {$url}");
        }

        return DB::transaction(function () use ($url, $uploader, $categoryId, $title, $tagNames, $ownershipId): Document {
            $document = Document::create([
                'title' => filled($title) ? $title : Document::fallbackTitle(self::filenameFromUrl($url)),
                'source_type' => Document::SOURCE_URL,
                'source_url' => $url,
                'status' => Document::STATUS_PENDING,
                'category_id' => $categoryId,
                'ownership_id' => $ownershipId,
                'created_by' => $uploader->id,
            ]);

            $document->tags()->sync($this->resolveTags($tagNames));

            return $document;
        });
    }

    /**
     * Ambil "nama berkas semu" dari URL untuk fallback judul: segmen terakhir
     * path (di-url-decode), fallback ke host kalau path kosong.
     */
    private static function filenameFromUrl(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $base = trim((string) pathinfo($path, PATHINFO_BASENAME));
        $base = rawurldecode($base);

        if ($base === '' || $base === '.' || $base === '/') {
            $base = (string) (parse_url($url, PHP_URL_HOST) ?? 'tautan');
        }

        return $base;
    }

    public function approve(Document $document, User $verifier, ?string $title, ?int $categoryId, array $tagNames, ?int $ownershipId = null): Document
    {
        DB::transaction(function () use ($document, $verifier, $title, $categoryId, $tagNames, $ownershipId): void {
            $data = [
                'title' => filled($title) ? $title : $document->title,
                'category_id' => $categoryId,
                'status' => Document::STATUS_PUBLISHED,
                'rejection_reason' => null,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ];

            if ($ownershipId !== null || array_key_exists('ownership_id', func_get_args())) {
                $data['ownership_id'] = $ownershipId;
            }

            $document->update($data);

            $document->tags()->sync($this->resolveTags($tagNames));
        });

        $this->organizeOnPublish($document->fresh());

        return $document->refresh();
    }

    /**
     * Buat subfolder "YYYY-MM-DD - Judul" di folder induk (sekali saja per
     * dokumen) dan pindahkan SELURUH berkas document_files milik dokumen ini
     * ke sana. Dipanggil dari approve(), moveFileTo(), dan EditDocument.
     * Idempoten: folder tidak dibuat ulang bila sudah ada; berkas yang
     * ditambahkan belakangan tetap ikut dipindah.
     *
     * Berbeda dari Drive (id berkas stabil walau pindah folder), di disk
     * lokal path = identitas berkas, jadi storage_path WAJIB ikut di-update
     * setiap kali berkas dipindah.
     */
    public function organizeOnPublish(Document $document): void
    {
        if ($document->isUrl()) {
            return;
        }

        try {
            $folderPath = $document->drive_folder_id;

            if (! $folderPath) {
                $folderName = $document->folderName();
                $yearPath = $this->yearFolderFor($document);
                $folderPath = $this->drive->createFolder($folderName, $yearPath);

                $document->update([
                    'drive_folder_id' => $folderPath,
                    'drive_folder_name' => $folderName,
                ]);
            }

            foreach ($document->files as $file) {
                if (! $file->storage_path) {
                    continue;
                }

                $newPath = $this->drive->moveFile($file->storage_path, $folderPath);

                if ($newPath !== $file->storage_path) {
                    $file->update(['storage_path' => $newPath]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[arsip] organizeOnPublish gagal, sebagian berkas mungkin masih di folder etc', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Path folder "{tahun}/etc" untuk dokumen ini — dipakai berkas mentah
     * yang belum diverifikasi. Tahun dari created_at dokumen (tanggal unggah).
     */
    private function pendingFolderForDocument(Document $document): string
    {
        $year = ($document->created_at ?? now())->year;

        return $this->pendingFolderForYear($year);
    }

    /**
     * Path folder "{tahun}/etc" — buat folder tahun dan subfolder "etc"
     * bila belum ada, lalu kembalikan path-nya.
     */
    private function pendingFolderForYear(int $year): string
    {
        $yearPath = $this->drive->findOrCreateFolder((string) $year, $this->drive->rootFolderId());

        return $this->drive->findOrCreateFolder('etc', $yearPath);
    }

    /**
     * Path folder "{tahun}" (polos) tempat folder dokumen dibuat. Tahun dari
     * created_at dokumen — konsisten dengan format nama folder dokumen.
     */
    private function yearFolderFor(Document $document): string
    {
        $year = ($document->created_at ?? now())->year;

        return $this->drive->findOrCreateFolder((string) $year, $this->drive->rootFolderId());
    }

    /**
     * Sinkronkan nama folder (kini folder disk lokal) ke judul dokumen saat
     * judul berubah ("ketika judul di edit maka folder juga rename").
     * Rename folder menggeser seluruh isi, jadi storage_path tiap berkas
     * ikut diperbarui (prefix lama → prefix baru).
     */
    public function syncDriveNames(Document $document): void
    {
        if (! $document->drive_folder_id) {
            return;
        }

        try {
            $newFolderName = $document->folderName();

            if ($newFolderName === $document->drive_folder_name) {
                return;
            }

            $oldFolderPath = $document->drive_folder_id;
            $newFolderPath = $this->drive->rename($oldFolderPath, $newFolderName);

            foreach ($document->files as $file) {
                if ($file->storage_path && str_starts_with($file->storage_path, $oldFolderPath.'/')) {
                    $file->update([
                        'storage_path' => $newFolderPath.substr($file->storage_path, strlen($oldFolderPath)),
                    ]);
                }
            }

            $document->update([
                'drive_folder_id' => $newFolderPath,
                'drive_folder_name' => $newFolderName,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[arsip] syncDriveNames gagal, nama folder dibiarkan', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gabungkan seluruh berkas dokumen sumber ke dokumen target (model list
     * flat: semua berkas sumber dipindah ke files milik target, berkas lama
     * target tetap ada). Baris sumber dihapus (tanpa menghapus berkas Drive),
     * lalu seluruh berkas target ditata ke folder Drive milik dokumen target
     * — folder dibuat saat itu juga walau target masih pending (keputusan
     * user). Baris sumber tidak diproses lewat delete() karena berkasnya
     * harus dipertahankan.
     */
    public function moveFileTo(Document $source, Document $target, User $actor): Document
    {
        if ($source->isUrl() || $source->files()->count() === 0) {
            throw new \RuntimeException('Dokumen sumber tidak punya berkas fisik yang bisa dipindah.');
        }

        if ($source->is($target)) {
            throw new \RuntimeException('Tidak bisa memindah berkas ke dokumen yang sama.');
        }

        DB::transaction(function () use ($source, $target): void {
            $source->files()->update(['document_id' => $target->id]);
            $source->delete();
        });

        $this->organizeOnPublish($target->fresh(['files']));

        Log::info("[arsip] berkas digabung oleh {$actor->id}: dokumen {$source->id} → {$target->id}");

        return $target->refresh();
    }

    /**
     * Hapus SATU berkas (baris document_files) dari sebuah dokumen — dipakai
     * tombol hapus di daftar berkas halaman Edit. Berkas dihapus dari Drive
     * (best-effort) lalu baris DB dihapus. Dokumen boleh sampai 0 berkas.
     */
    public function removeFile(DocumentFile $file): void
    {
        if ($file->storage_path) {
            $this->drive->delete($file->storage_path);
        }

        $file->delete();
    }

    /**
     * Tambahkan berkas baru langsung ke dokumen yang sedang di-Edit. Diunggah
     * ke folder Drive dokumen ini kalau sudah ada (drive_folder_id terisi),
     * kalau belum tetap ke folder penampung etc.
     *
     * @param  list<array{path: string, filename: string, mime: string, extension: string, size: int}>  $files
     */
    public function addFiles(Document $document, array $files): void
    {
        $targetFolderId = $document->drive_folder_id ?: $this->pendingFolderForDocument($document);

        foreach ($files as $file) {
            $uploaded = $this->drive->uploadTo($file['path'], $file['filename'], $file['mime'], $targetFolderId);

            try {
                if ($document->isUrl()) {
                    // Dokumen yang tadinya cuma tautan berubah jadi dokumen
                    // berkas begitu berkas fisik pertama ditambahkan.
                    $document->update([
                        'source_type' => Document::SOURCE_FILE,
                        'source_url' => null,
                    ]);
                }

                $document->files()->create([
                    'storage_path' => $uploaded['id'],
                    'original_filename' => $file['filename'],
                    'file_extension' => $file['extension'],
                    'mime_type' => $file['mime'],
                    'file_size' => $file['size'],
                ]);
            } catch (\Throwable $e) {
                $this->drive->delete($uploaded['id']);
                Log::error('[arsip] addFiles gagal mencatat metadata, berkas disk dihapus: '.$e->getMessage());

                throw $e;
            }
        }
    }

    /**
     * Tolak dokumen — berkas TETAP berada di folder penampung "etc"
     * (keputusan user: tidak ikut dihapus; berkas hanya dihapus dari Drive
     * saat baris dokumennya sendiri dihapus lewat delete()).
     */
    public function reject(Document $document, User $verifier, string $reason): Document
    {
        $document->update([
            'status' => Document::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        return $document->refresh();
    }

    /**
     * Hapus dokumen: seluruh berkas fisik (semua baris document_files)
     * dihapus dulu (best-effort, tidak menggagalkan penghapusan baris DB bila
     * sebagian berkas sudah tidak ada), baru baris DB (cascade juga menghapus
     * document_files).
     */
    public function delete(Document $document): void
    {
        if (! $document->isUrl()) {
            foreach ($document->files as $file) {
                if ($file->storage_path) {
                    $this->drive->delete($file->storage_path);
                }
            }

            if ($document->drive_folder_id) {
                $this->drive->deleteDirectory($document->drive_folder_id);
            }
        }

        $document->delete();
    }

    /**
     * Cari-atau-buat tiap nama tag secara case-insensitive, kembalikan array
     * id untuk sync().
     *
     * @param  list<string>  $tagNames
     * @return list<int>
     */
    private function resolveTags(array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name): int => Tag::resolve($name)->id)
            ->values()
            ->all();
    }
}
