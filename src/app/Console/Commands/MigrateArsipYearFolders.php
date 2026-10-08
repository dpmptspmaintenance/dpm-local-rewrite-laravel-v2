<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\ArsipDigital\LocalArsipStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Migrasi sekali-pakai: tata ulang berkas arsip OPD lama ke struktur folder
 * per tahun "{tahun}/..." — dokumen publish ke "{tahun}/{YYYY-MM-DD - Judul}/",
 * berkas pending ke "{tahun}/etc/".
 *
 * Idempoten: dokumen yang sudah berada di bawah folder tahun (drive_folder_id
 * berawalan "{tahun}/") dilewati. Aman dijalankan berulang.
 *
 * Opsi --dry-run: hanya menampilkan rencana, tidak mengubah berkas/DB.
 */
class MigrateArsipYearFolders extends Command
{
    protected $signature = 'arsip:migrate-year-folders {--dry-run : Tampilkan rencana tanpa mengubah apa pun}';

    protected $description = 'Tata ulang berkas arsip lama ke struktur folder per tahun';

    public function handle(LocalArsipStorage $storage): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $migrated = 0;
        $skipped = 0;
        $missing = 0;

        foreach (Document::where('source_type', Document::SOURCE_FILE)->orderBy('id')->get() as $document) {
            $year = ($document->created_at ?? now())->year;
            $folder = $document->drive_folder_id;

            if ($folder && str_starts_with($folder, $year.'/')) {
                $this->line("SKIP   doc={$document->id} (sudah di tahun) {$folder}");
                $skipped++;

                continue;
            }

            $realFiles = $document->files->filter(fn ($f) => filled($f->storage_path));
            $hasFolder = filled($folder);

            if ($realFiles->isEmpty() && ! $hasFolder) {
                $this->line("KOSONG doc={$document->id} (tanpa berkas fisik) — dilewati");
                $missing++;

                continue;
            }

            if ($dryRun) {
                $this->line("RENCANA doc={$document->id} tahun={$year} folder=".($folder ?: '(baru)').' berkas='.$realFiles->count());
                $migrated++;

                continue;
            }

            $this->migrateDocument($document, $year);
            $this->line("PINDAH doc={$document->id} → {$year}/");
            $migrated++;
        }

        $this->info("Selesai. dipindah={$migrated} dilewati={$skipped} kosong={$missing}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    private function migrateDocument(Document $document, int $year): void
    {
        $storage = app(LocalArsipStorage::class);
        $yearPath = $storage->findOrCreateFolder((string) $year, $storage->rootFolderId());

        // Dokumen yang sudah punya folder: pindahkan FOLDER itu ke bawah tahun
        // (bukan bikin folder baru), lalu sesuaikan storage_path tiap berkas.
        if ($document->drive_folder_id && Storage::disk('arsip')->directoryExists($document->drive_folder_id)) {
            $oldFolder = $document->drive_folder_id;
            $newFolder = $storage->moveFile($oldFolder, $yearPath);

            foreach ($document->files as $file) {
                if ($file->storage_path && str_starts_with($file->storage_path, $oldFolder.'/')) {
                    $file->update([
                        'storage_path' => $newFolder.substr($file->storage_path, strlen($oldFolder)),
                    ]);
                }
            }

            $document->update(['drive_folder_id' => $newFolder]);

            return;
        }

        // Berkas mentah tanpa folder dokumen: pindahkan tiap berkas ke
        // "{tahun}/etc/" (status pending) — bukan dibuatkan folder dokumen.
        $etcPath = $storage->findOrCreateFolder('etc', $yearPath);

        foreach ($document->files as $file) {
            if (! $file->storage_path || ! Storage::disk('arsip')->exists($file->storage_path)) {
                continue;
            }

            $newPath = $storage->moveFile($file->storage_path, $etcPath);
            $file->update(['storage_path' => $newPath]);
        }
    }
}
