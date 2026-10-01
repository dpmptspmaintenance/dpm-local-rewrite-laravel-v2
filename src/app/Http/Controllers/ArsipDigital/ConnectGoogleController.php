<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Services\ArsipDigital\GoogleDriveService;
use Illuminate\Http\Request;

/**
 * Alur satu-kali untuk mengkoneksikan aplikasi ke akun Google user nyata
 * (mode auth oauth — Gmail gratis, file Drive masuk ke akun user itu).
 *
 * GET /arsip/connect-google          → redirect ke layar consent Google.
 * GET /arsip/connect-google/callback → Google mengirim auth-code di sini;
 *                                      ditukar jadi refresh_token dan
 *                                      ditampilkan sekali untuk disalin
 *                                      admin ke ARSIP_DRIVE_OAUTH_REFRESH_TOKEN.
 *
 * Kedua route publik (tidak dilindungi auth aplikasi) supaya bisa dipakai
 * dari browser mana pun yang akan mengotorisasi akun Google. Tidak ada
 * rahasia yang bocor — client_id/secret memang tidak boleh di-expose ke
 * user aplikasi, dan auth-code Google hanya bisa ditukar dengan secret
 * yang tetap di server.
 */
class ConnectGoogleController
{
    public function redirect(GoogleDriveService $drive)
    {
        return redirect()->away($drive->createAuthUrl($this->redirectUri()));
    }

    public function callback(Request $request, GoogleDriveService $drive)
    {
        $code = (string) $request->query('code', '');
        if ($code === '') {
            return response(
                '<h2>Google tidak mengirim auth-code.</h2>'
                .'<p>error: '.e((string) $request->query('error', 'tidak ada')).'</p>',
                400,
                ['Content-Type' => 'text/html; charset=utf-8'],
            );
        }

        $token = $drive->exchangeAuthCode($code, $this->redirectUri());

        return response()->view('arsip.oauth-connected', [
            'refreshToken' => $token['refresh_token'],
        ]);
    }

    private function redirectUri(): string
    {
        return rtrim((string) config('arsip.drive.oauth_redirect_uri'), '/')
            ?: route('arsip.connect-google.callback');
    }
}
