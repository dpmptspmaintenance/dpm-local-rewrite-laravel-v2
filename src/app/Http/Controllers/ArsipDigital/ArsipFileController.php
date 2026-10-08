<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Models\DocumentFile;
use App\Models\Kepegawaian\PegawaiArsip;
use App\Services\ArsipDigital\LocalArsipStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan berkas arsip fisik dari disk lokal (pengganti preview lewat
 * Google Drive). Otorisasi:
 * - Dokumen OPD  : user harus lolos User::canAccessDocument() (AGENTS.md § 4).
 * - Arsip pegawai: mengikuti akses modul Kepegawaian (admin kepegawaian /
 *   superadmin / pemilik terkait); berkas pegawai bersifat internal OPD.
 *
 * PDF & gambar disajikan inline (pratinjau di browser); tipe lain dipaksa
 * unduh (attachment). Lihat config('arsip.inline_preview_extensions').
 */
class ArsipFileController
{
    public function __construct(private readonly LocalArsipStorage $storage) {}

    public function showDocumentFile(Request $request, DocumentFile $documentFile): StreamedResponse
    {
        $user = $request->user();
        $document = $documentFile->document;

        abort_if($user === null || ! $user->canAccessDocument($document), 403);
        abort_unless(
            $documentFile->storage_path && $this->storage->exists($documentFile->storage_path),
            404,
        );

        return $this->stream(
            $documentFile->storage_path,
            $documentFile->original_filename ?: basename($documentFile->storage_path),
            $documentFile->mime_type ?: 'application/octet-stream',
            $documentFile->file_extension,
        );
    }

    public function showPegawaiArsip(Request $request, PegawaiArsip $pegawaiArsip): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless(
            $user->isArsipAdmin() || (bool) $user->is_admin_kepegawaian,
            403,
        );
        abort_unless(
            $pegawaiArsip->storage_path && $this->storage->exists($pegawaiArsip->storage_path),
            404,
        );

        return $this->stream(
            $pegawaiArsip->storage_path,
            $pegawaiArsip->original_filename ?: basename($pegawaiArsip->storage_path),
            $pegawaiArsip->mime_type ?: 'application/octet-stream',
            $pegawaiArsip->file_extension,
        );
    }

    private function stream(string $path, string $downloadName, string $mimeType, ?string $extension): StreamedResponse
    {
        $disk = Storage::disk('arsip');
        $inline = in_array(strtolower((string) $extension), config('arsip.inline_preview_extensions', []), true);

        return $disk->response(
            $path,
            $downloadName,
            ['Content-Type' => $mimeType],
            $inline ? 'inline' : 'attachment',
        );
    }
}
