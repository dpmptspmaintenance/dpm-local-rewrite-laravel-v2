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
 * menulis ke tabel documents / document_files + memanggil GoogleDriveService
 * bersamaan. Lihat AGENTS.md § 5: transaksi database wajib aman terhadap
 * kegagalan salah satu sisi (Drive vs DB).
 *
 * Model list flat: dokumen = metadata (judul/dll), berkas fisik = baris
 * document_files. Tidak ada konsep "berkas utama" — semua setara.
 */
class DocumentService
{
    public function __construct(private readonly GoogleDriveService $drive) {}

    /**
     * Unggah satu berkas: kirim ke Drive dulu, lalu catat metadata di DB.
     * Kalau upload Drive gagal, tidak ada baris DB yang pernah dibuat. Kalau
     * penyimpanan DB gagal SETELAH upload Drive sukses, berkas yang baru
     * terunggah dihapus lagi dari Drive.
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     * @param  list<string>  $tagNames
     */
    public function upload(array $file, User $uploader, ?int $categoryId, ?string $title, array $tagNames = []): Document
    {
        $uploaded = $this->drive->upload($file['path'], $file['filename'], $file['mime']);

        try {
            return DB::transaction(function () use ($file, $uploader, $categoryId, $title, $tagNames, $uploaded): Document {
                $document = Document::create([
                    'title' => filled($title) ? $title : Document::fallbackTitle($file['filename']),
                    'source_type' => Document::SOURCE_FILE,
                    'status' => Document::STATUS_PENDING,
                    'category_id' => $categoryId,
                    'created_by' => $uploader->id,
                ]);

                $document->files()->create([
                    'google_file_id' => $uploaded['id'],
                    'google_web_view_link' => $uploaded['webViewLink'],
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
            Log::error('[arsip] upload dibatalkan, file Drive dihapus: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Daftarkan satu dokumen berbasis tautan (Google Drive / web) — TIDAK ada
     * berkas fisik yang diunggah; baris cuma menyimpan referensi URL. Ikut
     * alur verifikasi sama seperti berkas (status awal pending_review).
     */
    public function registerUrl(string $url, User $uploader, ?int $categoryId, ?string $title, array $tagNames = []): Document
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \RuntimeException("URL tidak valid (harus http/https): {$url}");
        }

        return DB::transaction(function () use ($url, $uploader, $categoryId, $title, $tagNames): Document {
            $document = Document::create([
                'title' => filled($title) ? $title : Document::fallbackTitle(self::filenameFromUrl($url)),
                'source_type' => Document::SOURCE_URL,
                'source_url' => $url,
                'status' => Document::STATUS_PENDING,
                'category_id' => $categoryId,
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

    public function approve(Document $document, User $verifier, ?string $title, ?int $categoryId, array $tagNames): Document
    {
        DB::transaction(function () use ($document, $verifier, $title, $categoryId, $tagNames): void {
            $document->update([
                'title' => filled($title) ? $title : $document->title,
                'category_id' => $categoryId,
                'status' => Document::STATUS_PUBLISHED,
                'rejection_reason' => null,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);

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
     */
    public function organizeOnPublish(Document $document): void
    {
        if ($document->isUrl()) {
            return;
        }

        try {
            $folderId = $document->drive_folder_id;

            if (! $folderId) {
                $folderName = $document->folderName();
                $folderId = $this->drive->createFolder($folderName, $this->drive->rootFolderId());

                $document->update([
                    'drive_folder_id' => $folderId,
                    'drive_folder_name' => $folderName,
                ]);
            }

            foreach ($document->files as $file) {
                $this->drive->moveFile($file->google_file_id, $folderId);
            }
        } catch (\Throwable $e) {
            Log::warning('[arsip] organizeOnPublish gagal, sebagian berkas mungkin masih di folder etc', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sinkronkan nama folder Drive ke judul dokumen saat judul berubah
     * ("ketika judul di edit maka di google drive juga rename").
     */
    public function syncDriveNames(Document $document): void
    {
        if (! $document->drive_folder_id) {
            return;
        }

        try {
            $newFolderName = $document->folderName();

            if ($newFolderName !== $document->drive_folder_name) {
                $this->drive->rename($document->drive_folder_id, $newFolderName);
                $document->update(['drive_folder_name' => $newFolderName]);
            }
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
        $this->drive->delete($file->google_file_id);
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
        $targetFolderId = $document->drive_folder_id ?: $this->drive->pendingFolderId();

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
                    'google_file_id' => $uploaded['id'],
                    'google_web_view_link' => $uploaded['webViewLink'],
                    'original_filename' => $file['filename'],
                    'file_extension' => $file['extension'],
                    'mime_type' => $file['mime'],
                    'file_size' => $file['size'],
                ]);
            } catch (\Throwable $e) {
                $this->drive->delete($uploaded['id']);
                Log::error('[arsip] addFiles gagal mencatat metadata, berkas Drive dihapus: '.$e->getMessage());

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
     * Hapus dokumen: seluruh berkas Drive (semua baris document_files)
     * dihapus dulu (best-effort, tidak menggagalkan penghapusan baris DB bila
     * sebagian file sudah tidak ada di Drive), baru baris DB (cascade juga
     * menghapus document_files).
     */
    public function delete(Document $document): void
    {
        if (! $document->isUrl()) {
            foreach ($document->files as $file) {
                $this->drive->delete($file->google_file_id);
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
