<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Kepegawaian\Notulen;
use App\Services\Kepegawaian\NotulenDocumentService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sajikan dokumen Notulen (PDF pratinjau inline + Word unduhan) dari
 * SNAPSHOT tersimpan — bukan render ulang data — supaya koreksi manual di
 * editor Word tidak hilang saat dibuka/diunduh lagi.
 */
class NotulenPdfController extends Controller
{
    public function __construct(private readonly NotulenDocumentService $documents) {}

    public function show(Notulen $notulen): BinaryFileResponse
    {
        abort_unless(auth()->check(), 403);

        $path = $this->documents->renderPdf($notulen);
        $filename = $this->documents->downloadName($notulen, 'pdf');

        return response()
            ->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Unduh .docx dari snapshot (attachment). Snapshot dibuat/di-refresh
     * otomatis bila belum ada.
     */
    public function word(Notulen $notulen): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $this->documents->materialize($notulen);
        $relative = (string) $notulen->fresh()->document_docx_path;
        $filename = $this->documents->downloadName($notulen, 'docx');

        return Storage::disk('arsip')->download($relative, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
