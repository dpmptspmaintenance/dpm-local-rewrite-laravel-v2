<?php

namespace App\Http\Controllers\Tool;

use App\Models\Certificate\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sajikan gambar background template sertifikat dari disk privat. Path
 * dikirim sebagai token base64 (dibuat CertificateGenerator) dan pengguna
 * harus punya akses ke template pemilik path tersebut — mencegah peserta
 * lain membaca background template orang lain.
 */
class CertificateBackgroundController
{
    public function show(Request $request, string $path): StreamedResponse
    {
        $token = strtr($path, '-_', '+/');
        $decoded = base64_decode($token, true);

        if ($decoded === false || $decoded === '') {
            abort(404);
        }

        $normalized = ltrim($decoded, '/');

        if (str_contains($normalized, '..')) {
            abort(404);
        }

        $template = CertificateTemplate::query()
            ->where('background_path', $normalized)
            ->first();

        if ($template === null || ! $template->isVisibleTo($request->user())) {
            abort(404);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($normalized)) {
            abort(404);
        }

        $mime = $disk->mimeType($normalized) ?: 'application/octet-stream';

        return $disk->response($normalized, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
