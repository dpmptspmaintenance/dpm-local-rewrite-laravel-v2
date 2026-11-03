<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use App\Models\Kepegawaian\Notulen;
use App\Services\Kepegawaian\NotulenDocumentService;
use App\Services\Kepegawaian\NotulenGeneratorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Berkas .docx Notulen untuk editor browser (Notulen Word).
 *
 * - `show`  : GET berkas .docx mentah (dipakai SuperDoc lewat fetch + Blob).
 * - `store` : POST hasil ekspor editor, menimpa snapshot dokumen notulen.
 *
 * Snapshot tetap satu-satunya sumber kebenaran: PDF pratinjau & unduhan Word
 * di halaman lain membaca berkas yang sama.
 */
class NotulenWordController extends Controller
{
    public function __construct(private readonly NotulenDocumentService $documents) {}

    public function show(Request $request, Notulen $notulen): StreamedResponse
    {
        abort_unless($request->user() !== null, 403);

        $absolute = $this->documents->materialize($notulen);
        $nama = $this->documents->downloadName($notulen, 'docx');

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
    public function store(Request $request, Notulen $notulen): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $maxKb = (int) config('arsip.max_upload_kb', 51200);

        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/octet-stream'],
        ]);

        $uploaded = $request->file('file');

        try {
            $path = $this->documents->replaceSnapshot(
                $notulen,
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
            'download_url' => route('kepegawaian.notulen.word', $notulen),
            'pdf_url' => route('kepegawaian.notulen.pdf', $notulen),
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

        \Illuminate\Support\Facades\Log::warning('[notulen-word][browser] '.$data['stage'], [
            'user_id' => $request->user()?->getKey(),
            'message' => $data['message'] ?? null,
            'detail' => $data['detail'] ?? null,
            'ua' => substr((string) $request->userAgent(), 0, 250),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Cetak dari isi kanvas: terima .docx ekspor editor (termasuk perubahan
     * belum disimpan), render PDF, kembalikan inline untuk dibuka di tab baru.
     * Hasil memakai mesin LibreOffice — sama dengan berkas unduhan.
     */
    public function print(Request $request, Notulen $notulen): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($request->user() !== null, 403);

        $maxKb = (int) config('arsip.max_upload_kb', 51200);

        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/octet-stream'],
        ]);

        $uploaded = $request->file('file');
        $tmp = sys_get_temp_dir().'/'.uniqid('nwprint_', true).'.docx';
        copy((string) $uploaded->getRealPath(), $tmp);

        try {
            $pdf = app(NotulenGeneratorService::class)->generatePdf($notulen, $tmp);
        } finally {
            @unlink($tmp);
        }

        $filename = $this->documents->downloadName($notulen, 'pdf');

        return response()
            ->file($pdf, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Hapus snapshot lalu render ulang dari data notulen (tombol "Generate
     * Ulang dari Data" di editor).
     */
    public function regenerate(Request $request, Notulen $notulen): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $path = $this->documents->regenerate($notulen, $request->user()?->getKey());

        return response()->json([
            'ok' => true,
            'path' => basename($path),
            'saved_at' => now()->toIso8601String(),
            'download_url' => route('kepegawaian.notulen.word', $notulen),
        ]);
    }
}
