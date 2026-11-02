<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Kepegawaian\Cuti;
use App\Models\Kepegawaian\PegawaiProfil;
use App\Models\Kepegawaian\SuratCutiRiwayat;
use App\Services\Kepegawaian\SuratCutiGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Generator Surat Cuti untuk integrasi eksternal (Chrome extension).
 *
 * Hasilnya IDENTIK dengan halaman /kepegawaian/generator-surat-cuti karena
 * memakai `SuratCutiGeneratorService::susun()` yang sama. Hanya menulis ke
 * `riwayat_generator_cuti` (arsip) — tabel `cuti` tidak disentuh.
 */
class GeneratorSuratCutiController
{
    public function __construct(private readonly SuratCutiGeneratorService $service) {}

    /**
     * GET /api/v1/generator-surat-cuti/berikutnya?kode=800
     */
    public function berikutnya(Request $request): JsonResponse
    {
        $kode = trim((string) $request->query('kode', SuratCutiGeneratorService::DEFAULT_KODE));
        $kode = $kode !== '' ? $kode : SuratCutiGeneratorService::DEFAULT_KODE;

        return response()->json([
            'ok' => true,
            'kode' => $kode,
            'nomor_awal' => $this->service->nextSequence($kode),
            'tahun' => $this->service->tahunTampil(),
            'import_hari_ini' => Cuti::sudahImporHariIni(),
        ]);
    }

    /**
     * GET /api/v1/generator-surat-cuti/pegawai?q=SANTI
     */
    public function pegawai(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['ok' => false, 'error' => 'Parameter q wajib diisi.'], 422);
        }

        $rows = PegawaiProfil::query()
            ->where(function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where('nama', 'like', $like)->orWhere('nip', 'like', $like);
            })
            ->orderBy('nama')
            ->limit(25)
            ->get(['nip', 'nama', 'status_pegawai', 'jabatan']);

        return response()->json([
            'ok' => true,
            'jumlah' => $rows->count(),
            'data' => $rows->map(fn (PegawaiProfil $p): array => [
                'nip' => $p->nip,
                'nama' => $p->nama,
                'status_pegawai' => $p->status_pegawai,
                'jabatan' => $p->jabatan,
            ])->all(),
        ]);
    }

    /**
     * POST /api/v1/generator-surat-cuti
     *
     * Body: {"teks": "..."} ATAU {"rows": [...]}, plus opsional "kode".
     */
    public function susun(Request $request): JsonResponse
    {
        $kode = trim((string) $request->input('kode', SuratCutiGeneratorService::DEFAULT_KODE));
        $kode = $kode !== '' ? $kode : SuratCutiGeneratorService::DEFAULT_KODE;

        $tahun = $request->input('tahun');

        if (is_array($tahun) && $tahun !== []) {
            $tahun = array_values(array_filter(array_map('intval', $tahun)));
        } else {
            $tahun = $this->service->tahunTampil();
        }

        // Gate "wajib impor cuti hari ini" — sisa harus segar. Bisa
        // dimatikan per-request dengan "abaikan_gate": true (mis. uji coba).
        if (! $request->boolean('abaikan_gate') && ! Cuti::sudahImporHariIni()) {
            return response()->json([
                'ok' => false,
                'error' => 'Data cuti belum diimpor hari ini. Impor cuti dulu agar sisa akurat.',
                'kode_error' => 'belum_impor',
            ], 409);
        }

        $teks = $request->input('teks');
        $rows = $request->input('rows');

        if (is_string($teks) && trim($teks) !== '') {
            $parsed = $this->service->parse($teks);
            $orang = $parsed['orang'];
            $takDikenali = $parsed['tak_dikenali'];
            $errors = [];
            $teksMentah = $teks;
        } elseif (is_array($rows) && $rows !== []) {
            $dariRows = $this->service->dariRows($rows);
            $orang = $dariRows['orang'];
            $takDikenali = [];
            $errors = $dariRows['errors'];
            $teksMentah = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            return response()->json([
                'ok' => false,
                'error' => 'Kirim "teks" (tempelan) atau "rows" (data terstruktur).',
            ], 422);
        }

        $jumlahRinci = 0;

        foreach ($orang as $o) {
            $jumlahRinci += count($o['rinci']);
        }

        if ($jumlahRinci > SuratCutiGeneratorService::MAKS_RINCIAN) {
            return response()->json([
                'ok' => false,
                'error' => 'Maksimal '.SuratCutiGeneratorService::MAKS_RINCIAN.' rincian per request (dikirim '.$jumlahRinci.').',
            ], 422);
        }

        if ($jumlahRinci === 0) {
            return response()->json([
                'ok' => false,
                'error' => 'Tidak ada rincian cuti yang dikenali.',
                'errors' => $errors,
                'tak_dikenali' => $takDikenali,
            ], 422);
        }

        $nomorAwal = $request->filled('nomor_awal')
            ? max(1, (int) $request->input('nomor_awal'))
            : $this->service->nextSequence($kode);

        // Cek silang duplikat ke riwayat yang sudah tersimpan (opsional):
        // rincian yang nomornya sudah ada ikut ditandai duplikat.
        $kunciRiwayat = [];

        if ($request->boolean('cek_riwayat') && $this->service->tabelRiwayatAda()) {
            foreach (
                \App\Models\Kepegawaian\SuratCutiRiwayat::query()
                    ->select(['nama', 'pengajuan'])
                    ->orderByDesc('id')
                    ->limit(5000)
                    ->get() as $lama
            ) {
                $kunciRiwayat[$this->service->kunciDuplikat(
                    (string) $lama->nama,
                    (string) ($lama->pengajuan ?? ''),
                )] = true;
            }
        }

        $susunan = $this->service->susun($orang, $takDikenali, $kode, $nomorAwal, $tahun, $kunciRiwayat);

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');
        $label = trim((string) $request->input('sumber_label', '')) ?: ($apiKey?->name ?? 'extension');

        $batch = null;

        try {
            $batch = $this->service->simpanRiwayatBatch(
                $susunan['hasil'],
                SuratCutiRiwayat::SUMBER_EXTENSION,
                mb_substr($label, 0, 100),
                $teksMentah,
                $apiKey?->user_id !== null ? \App\Models\User::find($apiKey->user_id) : null,
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'Gagal menyimpan riwayat: '.$e->getMessage(),
            ], 500);
        }

        // Mirror ke Google Sheets eksternal (rewrite full dari database).
        // Gagal sync = request gagal (500) sesuai kesepakatan.
        try {
            app(\App\Services\Kepegawaian\GeneratorCutiSheetsMirror::class)->syncAll();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'Gagal menulis ke Google Sheets: '.$e->getMessage(),
                'batch' => $batch,
            ], 500);
        }

        $bernomor = array_values(array_filter(
            $susunan['hasil'],
            fn (array $h): bool => empty($h['duplikat']) && ! empty($h['nomor']),
        ));

        return response()->json([
            'ok' => true,
            'batch' => $batch,
            'kode' => $kode,
            'tahun' => $tahun,
            'nomor_awal' => $nomorAwal,
            'nomor_berikutnya' => $susunan['nomor_berikutnya'],
            'jumlah' => count($susunan['hasil']),
            'jumlah_bernomor' => count($bernomor),
            'jumlah_duplikat' => count($susunan['duplikat']),
            'hasil' => $susunan['hasil'],
            'duplikat' => $susunan['duplikat'],
            'tak_dikenali' => $susunan['tak_dikenali'],
            'errors' => $errors,
            'tersimpan' => $batch !== null,
        ], 201);
    }
}
