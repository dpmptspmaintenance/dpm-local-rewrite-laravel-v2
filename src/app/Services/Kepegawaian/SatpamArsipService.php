<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\Satpam;
use App\Models\Kepegawaian\SatpamArsip;
use App\Models\User;
use App\Services\ArsipDigital\LocalArsipStorage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orkestrasi upload/hapus arsip berkas Satpam. Reuse LocalArsipStorage yang
 * sama dipakai modul Arsip Digital (/arsip) dan arsip pegawai — bukan
 * penyimpanan kedua — tapi lewat subfolder "Kepegawaian/satpam" tersendiri
 * di dalam root disk arsip, supaya tidak bercampur dokumen OPD umum
 * maupun arsip pegawai ("Kepegawaian/pegawai") dan DRH ("Kepegawaian/drh").
 *
 * Struktur folder disk: {ARSIP_LOCAL_ROOT}/Kepegawaian/satpam/{id - Nama}/
 * — satu subfolder per satpam, dibuat sekali (lazy, saat berkas pertama
 * satpam itu diunggah) lalu path-nya di-cache.
 */
class SatpamArsipService
{
    private const CACHE_ROOT = 'arsip-satpam:root-folder-path';

    private const CACHE_SATPAM_PREFIX = 'arsip-satpam:satpam-folder-path:';

    private const CACHE_TTL_DAYS = 7;

    /**
     * Jenis berkas khusus: satu file per jenis (KTA/KK/KTP/Ijazah), diunggah
     * lewat slot khusus di form satpam — BUKAN lewat mekanisme arsip generik
     * (tanpa judul/kategori, hanya terlihat di modul satpam). "Lainnya"
     * boleh banyak file. Disimpan sebagai baris satpam_arsip dengan kategori
     * = jenisnya, supaya tetap satu tabel/metode serve file.
     */
    public const JENIS_KTA = 'KTA';

    public const JENIS_KK = 'KK';

    public const JENIS_KTP = 'KTP';

    public const JENIS_IJAZAH = 'Ijazah';

    public const JENIS_LAINNYA = 'Lainnya';

    /** @var list<string> */
    public const JENIS_KHUSUS = [
        self::JENIS_KTA,
        self::JENIS_KK,
        self::JENIS_KTP,
        self::JENIS_IJAZAH,
    ];

    public function __construct(private readonly LocalArsipStorage $drive) {}

    /**
     * Unggah satu berkas untuk satu satpam: tulis ke disk dulu (folder
     * khusus satpam itu), baru catat metadata. Kalau disk gagal, tak ada
     * baris DB yang dibuat. Kalau DB gagal SETELAH berkas tersimpan, berkas
     * yang baru tersimpan dihapus lagi dari disk.
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     */
    public function upload(int $satpamId, array $file, User $uploader, ?string $judul, ?string $kategori): SatpamArsip
    {
        $folderPath = $this->satpamFolderId($satpamId);

        $uploaded = $this->drive->uploadTo($file['path'], $file['filename'], $file['mime'], $folderPath);

        try {
            return SatpamArsip::create([
                'satpam_id' => $satpamId,
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
            Log::error('[arsip-satpam] upload dibatalkan, berkas disk dihapus: '.$e->getMessage());

            throw $e;
        }
    }

    public function delete(SatpamArsip $arsip): void
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
    public function replaceFile(SatpamArsip $arsip, array $file): void
    {
        $folderPath = $this->satpamFolderId($arsip->satpam_id);

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
            Log::error('[arsip-satpam] ganti berkas dibatalkan, berkas baru dihapus: '.$e->getMessage());

            throw $e;
        }

        if ($oldPath && $oldPath !== $uploaded['id']) {
            $this->drive->delete($oldPath);
        }
    }

    /**
     * Unggah/ GANTI berkas khusus satu jenis (KTA/KK/KTP/Ijazah): berkas lama
     * jenis yang sama (bila ada) dihapus dulu — selalu tepat satu file per
     * jenis per satpam.
     *
     * @param  array{path: string, filename: string, mime: string, extension: string, size: int}  $file
     */
    public function uploadKhusus(int $satpamId, string $jenis, array $file, User $uploader): SatpamArsip
    {
        foreach (SatpamArsip::query()
            ->where('satpam_id', $satpamId)
            ->where('kategori', $jenis)
            ->get() as $lama) {
            $this->delete($lama);
        }

        return $this->upload($satpamId, $file, $uploader, null, $jenis);
    }

    /**
     * Path subfolder disk khusus satu satpam (dibuat sekali, lazy). Struktur:
     * "Kepegawaian/satpam/{id} - {Nama}" — di-cache per id dengan TTL karena
     * folder tak berpindah untuk satpam yang sama, tapi TTL mencegah path
     * basi tertahan permanen bila folder dipindah/rename manual.
     */
    private function satpamFolderId(int $satpamId): string
    {
        return Cache::remember(self::CACHE_SATPAM_PREFIX.$satpamId, now()->addDays(self::CACHE_TTL_DAYS), function () use ($satpamId): string {
            $root = $this->satpamRootFolderId();
            $nama = Satpam::query()->whereKey($satpamId)->value('nama') ?: $satpamId;
            $folderName = "{$satpamId} - {$nama}";

            return $this->drive->findOrCreateFolder($folderName, $root);
        });
    }

    /**
     * Path subfolder "Kepegawaian/satpam" di dalam root disk arsip
     * (ARSIP_LOCAL_ROOT) — dibuat sekali, lazy, lalu di-cache dengan TTL.
     */
    private function satpamRootFolderId(): string
    {
        return Cache::remember(self::CACHE_ROOT, now()->addDays(self::CACHE_TTL_DAYS), function (): string {
            $kepegawaian = $this->drive->findOrCreateFolder('Kepegawaian', $this->drive->rootFolderId());

            return $this->drive->findOrCreateFolder('satpam', $kepegawaian);
        });
    }
}
