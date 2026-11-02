<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Kepegawaian\Notulen;
use App\Services\Kepegawaian\NotulenGeneratorService;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sajikan PDF Notulen secara inline (dibuka di tab baru browser, bukan
 * diunduh) — dipakai tombol "PDF" pada panel Kepegawaian.
 */
class NotulenPdfController extends Controller
{
    public function show(Notulen $notulen): BinaryFileResponse
    {
        abort_unless(auth()->check(), 403);

        $path = app(NotulenGeneratorService::class)->generatePdf($notulen);
        $filename = Str::slug($notulen->judul).'.pdf';

        return response()
            ->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ])
            ->deleteFileAfterSend(true);
    }
}
