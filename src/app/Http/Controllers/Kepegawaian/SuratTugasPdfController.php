<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Kepegawaian\SuratTugas;
use App\Services\Kepegawaian\SuratTugasDocumentService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sajikan dokumen Surat Tugas (PDF pratinjau inline + Word unduhan) dari
 * SNAPSHOT tersimpan — bukan render ulang data — supaya koreksi manual di
 * halaman "Dokumen" tidak hilang saat dibuka/diunduh lagi.
 */
class SuratTugasPdfController extends Controller
{
    public function __construct(private readonly SuratTugasDocumentService $documents) {}

    public function show(SuratTugas $suratTugas): BinaryFileResponse
    {
        abort_unless(auth()->check(), 403);

        $path = $this->documents->renderPdf($suratTugas);
        $filename = $this->documents->downloadName($suratTugas, 'pdf');

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
    public function word(SuratTugas $suratTugas): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $this->documents->materialize($suratTugas);
        $relative = (string) $suratTugas->fresh()->document_docx_path;
        $filename = $this->documents->downloadName($suratTugas, 'docx');

        return Storage::disk('arsip')->download($relative, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
