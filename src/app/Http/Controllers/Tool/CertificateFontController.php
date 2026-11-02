<?php

namespace App\Http\Controllers\Tool;

use App\Models\Certificate\CertificateFont;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sajikan berkas TTF dari Master Font ke browser supaya kanvas preview
 * Generator Sertifikat memakai font yang sama persis dengan PDF Dompdf.
 *
 * Sumber berkas bisa dua:
 * - "bundled:" → resources/fonts/certificate/ (ikut repo, mis. Liberation).
 * - path biasa → disk privat storage/app/private/certificate/fonts (unggahan).
 */
class CertificateFontController
{
    public function show(Request $request, CertificateFont $font, string $variant): BinaryFileResponse
    {
        if (! $font->is_active) {
            abort(404);
        }

        $absolute = $font->absolutePath($variant);

        if ($absolute === null) {
            abort(404);
        }

        return response()->file($absolute, [
            'Content-Type' => 'font/ttf',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
