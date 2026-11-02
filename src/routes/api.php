<?php

use App\Http\Controllers\Api\V1\GeneratorSuratCutiController;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API eksternal (Chrome extension)
|--------------------------------------------------------------------------
|
| Autentikasi: header `X-API-Key: dpmk_...` (atau Authorization: Bearer).
| Kunci dibuat di panel Kepegawaian → Master → API Key.
|
| Dokumentasi lengkap tersedia di halaman tersebut (tab Dokumentasi).
| Tidak memakai session/OAuth karena dipanggil dari luar aplikasi.
|
*/

Route::prefix('v1')
    ->middleware(['api.key:'.ApiKey::SCOPE_GENERATOR_CUTI, 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/generator-surat-cuti/berikutnya', [GeneratorSuratCutiController::class, 'berikutnya']);
        Route::get('/generator-surat-cuti/pegawai', [GeneratorSuratCutiController::class, 'pegawai']);
        Route::post('/generator-surat-cuti', [GeneratorSuratCutiController::class, 'susun']);
    });
