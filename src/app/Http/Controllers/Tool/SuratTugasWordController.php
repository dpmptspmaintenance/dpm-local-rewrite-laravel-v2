<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use App\Models\Kepegawaian\SuratTugas;
use App\Services\Kepegawaian\SuratTugasDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas .docx Surat Tugas untuk editor browser (Surat Tugas Word).
 *
 * - `show`  : GET berkas .docx mentah (dipakai SuperDoc lewat fetch + Blob).
 * - `store` : POST hasil ekspor editor, menimpa snapshot dokumen surat.
 *
 * Snapshot tetap satu-satunya sumber kebenaran: PDF pratinjau & unduhan Word
 * di halaman lain membaca berkas yang sama.
 */
class SuratTugasWordController extends Controller
{
    public function __construct(private readonly SuratTugasDocumentService $documents) {}

    public function show(Request $request, SuratTugas $suratTugas): StreamedResponse
    {
        abort_unless($request->user() !== null, 403);

        $absolute = $this->documents->materialize($suratTugas);
        $nama = $this->documents->downloadName($suratTugas, 'docx');

        return response()->streamDownload(function () use ($absolute): void {
            readfile($absolute);
        }, $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Simpan hasil edit browser. Body = biner .docx; tidak ada berkas yang
     * disimpan di disk publik — langsung masuk storage arsip privat.
     */
    public function store(Request $request, SuratTugas $suratTugas): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $maxKb = (int) config('arsip.max_upload_kb', 51200);

        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/octet-stream'],
        ]);

        $uploaded = $request->file('file');

        try {
            $path = $this->documents->replaceSnapshot(
                $suratTugas,
                (string) $uploaded->getRealPath(),
                $request->user()?->getKey(),
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal menyimpan dokumen: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'path' => $path,
            'saved_at' => now()->toIso8601String(),
            'download_url' => route('kepegawaian.surat-tugas.word', $suratTugas),
            'pdf_url' => route('kepegawaian.surat-tugas.pdf', $suratTugas),
        ]);
    }

    /**
     * Titik lapor diagnostik editor browser (hanya menulis ke log, tidak
     * menyimpan isi dokumen). Dipakai untuk melacak kegagalan boot SuperDoc.
     */
    public function debug(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $data = $request->validate([
            'stage' => ['required', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
            'detail' => ['nullable', 'string', 'max:2000'],
        ]);

        \Illuminate\Support\Facades\Log::warning('[surat-tugas-word][browser] '.$data['stage'], [
            'user_id' => $request->user()?->getKey(),
            'message' => $data['message'] ?? null,
            'detail' => $data['detail'] ?? null,
            'ua' => substr((string) $request->userAgent(), 0, 250),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Hapus snapshot lalu render ulang dari data surat (tombol "Generate
     * Ulang dari Data" di editor).
     */
    public function regenerate(Request $request, SuratTugas $suratTugas): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $path = $this->documents->regenerate($suratTugas, $request->user()?->getKey());

        return response()->json([
            'ok' => true,
            'path' => basename($path),
            'saved_at' => now()->toIso8601String(),
            'download_url' => route('kepegawaian.surat-tugas.word', $suratTugas),
        ]);
    }
}
