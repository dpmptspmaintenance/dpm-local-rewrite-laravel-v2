<?php

use App\Http\Controllers\Api\V1\DataDukungTppController;
use App\Http\Controllers\Api\V1\GeneratorSuratCutiController;
use App\Http\Controllers\Api\V1\RekapAbsenController;
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

// Rekap absensi bulanan dari aplikasi presensi (secure-sadewa) — scope
// terpisah supaya kunci extension presensi tidak bisa menyentuh cuti.
Route::prefix('v1')
    ->middleware(['api.key:'.ApiKey::SCOPE_REKAP_ABSEN, 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/rekap-absen', [RekapAbsenController::class, 'index']);
        Route::get('/rekap-absen/{id}', [RekapAbsenController::class, 'show'])->whereNumber('id');
        Route::post('/rekap-absen', [RekapAbsenController::class, 'simpan']);
    });

// Data dukung TPP hasil scrape tpp.semarangkota.go.id/izin. Hanya baris
// dengan file pendukung terunggah (file_pendukung_status = "ada") yang
// menutup konfirmasi di rekap absen.
Route::prefix('v1')
    ->middleware(['api.key:'.ApiKey::SCOPE_DATA_DUKUNG_TPP, 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/data-dukung-tpp', [DataDukungTppController::class, 'index']);
        Route::post('/data-dukung-tpp', [DataDukungTppController::class, 'simpan']);
    });
