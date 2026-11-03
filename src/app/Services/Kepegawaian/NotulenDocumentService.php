<?php

namespace App\Services\Kepegawaian;

use App\Models\Kepegawaian\Notulen;
use App\Services\ArsipDigital\LocalArsipStorage;
use Illuminate\Support\Facades\Log;

/**
 * Snapshot dokumen .docx per Notulen — satu-satunya jalur tulis ke kolom
 * `document_docx_path` + `document_*`.
 *
 * Prinsip: dokumen yang sudah pernah dibuat TIDAK di-regenerate otomatis.
 * Ini yang membuat koreksi manual di halaman editor Word tidak hilang saat
 * notulen dibuka/download lagi. Regenerate hanya lewat aksi eksplisit
 * (tombol "Generate Ulang dari Data" di editor Word).
 */
class NotulenDocumentService
{
    private const FOLDER = 'kepegawaian/notulen';

    public function __construct(
        private readonly NotulenGeneratorService $generator,
        private readonly LocalArsipStorage $storage,
    ) {}

    /**
     * Path lokal dokumen .docx yang siap dipakai: pakai snapshot tersimpan
     * bila ada, selain itu generate + simpan sekali.
     */
    public function materialize(Notulen $notulen): string
    {
        $path = (string) ($notulen->document_docx_path ?? '');

        if ($path !== '' && $this->storage->exists($path)) {
            return $this->storage->absolutePath($path);
        }

        return $this->regenerate($notulen);
    }

    /**
     * Render ulang dokumen dari data notulen lalu timpa snapshot. Dipakai
     * tombol "Generate Ulang" di editor Word.
     */
    public function regenerate(Notulen $notulen, ?int $userId = null): string
    {
        $tmp = $this->generator->generateDocx($notulen);

        try {
            $filename = $this->filename($notulen);
            $folder = self::FOLDER.'/'.$notulen->getKey();

            $uploaded = $this->storage->uploadTo($tmp, $filename, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $folder);
            $baru = $uploaded['id'];
        } finally {
            @unlink($tmp);
        }

        $lama = (string) ($notulen->document_docx_path ?? '');

        $notulen->forceFill([
            'document_docx_path' => $baru,
            'document_generated_at' => now(),
            'document_generated_by' => $userId ?? auth()->id(),
        ])->save();

        if ($lama !== '' && $lama !== $baru) {
            $this->storage->delete($lama);
        }

        Log::info('[notulen] dokumen diregenerasi', [
            'notulen_id' => $notulen->getKey(),
            'path' => $baru,
        ]);

        return $this->storage->absolutePath($baru);
    }

    /**
     * Timpa snapshot .docx dengan biner hasil edit di browser (SuperDoc).
     *
     * Tidak memakai regenerate(): isi dokumen datang dari editor, bukan dari
     * template + data notulen. Ditandai `document_edited_at` supaya
     * peringatan "Generate Ulang" tetap muncul.
     */
    public function replaceSnapshot(Notulen $notulen, string $sourcePath, ?int $userId = null): string
    {
        if (! is_file($sourcePath)) {
            throw new \RuntimeException('Berkas hasil edit tidak ditemukan.');
        }

        $filename = $this->filename($notulen);
        $folder = self::FOLDER.'/'.$notulen->getKey();

        $uploaded = $this->storage->uploadTo(
            $sourcePath,
            $filename,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $folder,
        );

        $lama = (string) ($notulen->document_docx_path ?? '');

        $notulen->forceFill([
            'document_docx_path' => $uploaded['id'],
            'document_generated_at' => $notulen->document_generated_at ?? now(),
            'document_edited_at' => now(),
            'document_edited_by' => $userId ?? auth()->id(),
        ])->save();

        if ($lama !== '' && $lama !== $uploaded['id']) {
            $this->storage->delete($lama);
        }

        Log::info('[notulen] snapshot ditimpa dari editor browser', [
            'notulen_id' => $notulen->getKey(),
            'path' => $uploaded['id'],
        ]);

        return $uploaded['id'];
    }

    /**
     * @return string Path file PDF sementara (pemanggil hapus setelah dipakai)
     */
    public function renderPdf(Notulen $notulen): string
    {
        // PDF SELALU dari snapshot .docx tersimpan, bukan render ulang data —
        // supaya hasil PDF identik dengan Word yang sudah dikoreksi.
        return $this->generator->generatePdf($notulen->fresh(), $this->materialize($notulen));
    }

    public function downloadName(Notulen $notulen, string $extension): string
    {
        $slug = \Illuminate\Support\Str::slug((string) $notulen->judul) ?: 'notulen-'.$notulen->getKey();

        return $slug.'.'.$extension;
    }

    public function deleteSnapshot(Notulen $notulen): void
    {
        $path = (string) ($notulen->document_docx_path ?? '');

        if ($path === '') {
            return;
        }

        $this->storage->delete($path);
    }

    private function filename(Notulen $notulen): string
    {
        return $this->downloadName($notulen, 'docx');
    }
}
