<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Kepegawaian\DrhSatyaLancana;
use App\Models\Kepegawaian\DrhSatyaLancanaDokumen;
use App\Services\Kepegawaian\DrhSatyaLancanaDocumentService;
use App\Services\Kepegawaian\DrhSatyaLancanaGeneratorService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sajikan berkas DRH Satya Lancana secara inline (dibuka di tab baru browser,
 * bukan diunduh) — dipakai tombol "Lihat" pada panel Kepegawaian:
 *   - show()          : PDF DRH hasil generate.
 *   - showLampiran()  : berkas lampiran (SK CPNS, SK Pangkat, dll.).
 */
class DrhSatyaLancanaPdfController extends Controller
{
    public function show(DrhSatyaLancana $drh): BinaryFileResponse
    {
        abort_unless(auth()->check(), 403);

        $path = app(DrhSatyaLancanaGeneratorService::class)->generatePdf($drh);
        $filename = 'drh-satya-lancana-'.$drh->nama.'.pdf';

        return response()
            ->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ])
            ->deleteFileAfterSend(true);
    }

    public function showLampiran(DrhSatyaLancana $drh, string $jenis): BinaryFileResponse
    {        abort_unless(auth()->check(), 403);

        $service = app(DrhSatyaLancanaDocumentService::class);
        $doc = $drh->dokumen()->where('jenis', $jenis)->first();

        abort_if($doc === null, 404);

        $path = $service->absolutePath($doc);
        abort_unless(is_file($path), 404);

        $filename = $doc->original_filename ?: (strtoupper($doc->jenis).'.'.pathinfo($path, PATHINFO_EXTENSION));
        $mime = $doc->mime_type ?: (mime_content_type($path) ?: 'application/octet-stream');

        return response()
            ->file($path, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ]);
    }

    /**
     * Berkas gabungan (merge) DRH — juga inline di tab baru.
     */
    public function showMerge(DrhSatyaLancana $drh): BinaryFileResponse
    {
        abort_unless(auth()->check(), 403);

        $path = app(DrhSatyaLancanaDocumentService::class)->merge($drh);
        $filename = 'berkas-drh-'.$drh->nama.'.pdf';

        return response()
            ->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            ])
            ->deleteFileAfterSend(true);
    }
}
