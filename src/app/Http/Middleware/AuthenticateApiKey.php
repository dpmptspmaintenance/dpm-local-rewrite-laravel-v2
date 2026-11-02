<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi API lewat header `X-API-Key` (atau `Authorization: Bearer`).
 * Dipakai integrasi eksternal (Chrome extension Generator Surat Cuti) —
 * bukan session/OAuth, karena API dipanggil dari luar aplikasi.
 *
 * Setelah lolos, kunci tersedia di $request->attributes->get('api_key').
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $plaintext = $request->header('X-API-Key') ?: $request->bearerToken();
        $key = ApiKey::dariPlaintext($plaintext);

        if ($key === null) {
            return response()->json([
                'ok' => false,
                'error' => 'API key tidak valid atau tidak dikirim (header X-API-Key).',
            ], 401);
        }

        if (! $key->aktif()) {
            return response()->json([
                'ok' => false,
                'error' => 'API key sudah dicabut atau kadaluarsa.',
            ], 401);
        }

        if ($scope !== null && ! $key->punyaScope($scope)) {
            return response()->json([
                'ok' => false,
                'error' => 'API key tidak punya akses "'.$scope.'".',
            ], 403);
        }

        $key->tandaiDipakai($request->ip());
        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}
