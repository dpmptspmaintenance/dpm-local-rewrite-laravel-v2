<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Kepegawaian\RekapAbsen;
use App\Models\Kepegawaian\RekapAbsenTpp;
use App\Services\Kepegawaian\TppDataDukungService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * API Data Dukung TPP untuk Chrome extension (scrape
 * tpp.semarangkota.go.id/izin).
 *
 * Baris dengan file pendukung belum diunggah tetap disimpan
 * (`file_pendukung_status = "belum"`), tetapi TIDAK menutup konfirmasi di
 * rekap absen. Tidak ada endpoint hapus.
 */
class DataDukungTppController
{
    public function __construct(private readonly TppDataDukungService $service) {}

    /**
     * GET /api/v1/data-dukung-tpp?nip=&tahun=&bulan=&valid=
     */
    public function index(Request $request): JsonResponse
    {
        $bulanMentah = $request->query('bulan');
        $bulan = $bulanMentah === null || $bulanMentah === ''
            ? null
            : RekapAbsen::bulanAngka($bulanMentah);

        if ($bulanMentah !== null && $bulanMentah !== '' && $bulan === null) {
            return response()->json(['ok' => false, 'error' => 'Parameter bulan tidak dikenali.'], 422);
        }

        $nip = trim((string) $request->query('nip', ''));

        $rows = RekapAbsenTpp::query()
            ->with('tanggal')
            ->when($nip !== '', fn ($query) => $query->where('nip', $nip))
            ->when($request->filled('tahun'), fn ($query) => $query->where('tahun', (int) $request->query('tahun')))
            ->when($bulan !== null, fn ($query) => $query->where('bulan', $bulan))
            ->when($request->filled('valid'), fn ($query) => $query->where(
                'file_pendukung_status',
                $request->boolean('valid') ? RekapAbsenTpp::FILE_ADA : RekapAbsenTpp::FILE_BELUM,
            ))
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderBy('nip')
            ->limit(500)
            ->get();

        return response()->json([
            'ok' => true,
            'jumlah' => $rows->count(),
            'data' => $rows->map(fn (RekapAbsenTpp $row): array => $this->ringkas($row))->all(),
        ]);
    }

    /**
     * POST /api/v1/data-dukung-tpp
     *
     * Body: satu baris, atau {"items":[...]} / {"rows":[...]}.
     */
    public function simpan(Request $request): JsonResponse
    {
        $items = $request->input('items') ?? $request->input('rows');

        $payloads = is_array($items) && $items !== []
            ? array_values($items)
            : [$request->except(['items', 'rows'])];

        if (count($payloads) > TppDataDukungService::MAKS_ITEM) {
            return response()->json([
                'ok' => false,
                'error' => 'Maksimal '.TppDataDukungService::MAKS_ITEM.' baris per request (dikirim '.count($payloads).').',
            ], 422);
        }

        $errors = [];
        $valid = [];

        foreach ($payloads as $indeks => $item) {
            if (! is_array($item)) {
                $errors[$indeks] = ['Payload harus berupa objek.'];

                continue;
            }

            $validator = Validator::make($item, [
                'nip' => ['required', 'string', 'max:30'],
                'nama' => ['nullable', 'string', 'max:150'],
                'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
                'bulan' => ['required'],
                'keterangan' => ['nullable', 'string', 'max:2000'],
                'jumlah_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
                'tanggal_list' => ['nullable', 'array', 'max:31'],
                'file_pendukung_url' => ['nullable', 'string', 'max:500'],
                'file_pendukung_nama' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $errors[$indeks] = $validator->errors()->all();

                continue;
            }

            if (RekapAbsen::bulanAngka($item['bulan'] ?? null) === null) {
                $errors[$indeks] = ['Bulan tidak dikenali (pakai angka 1-12 atau nama bulan).'];

                continue;
            }

            $valid[$indeks] = $item;
        }

        if ($errors !== []) {
            return response()->json([
                'ok' => false,
                'error' => 'Sebagian payload tidak valid.',
                'errors' => $errors,
            ], 422);
        }

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');
        $pembuat = $apiKey?->user_id !== null ? \App\Models\User::find($apiKey->user_id) : null;
        $sumberLabel = trim((string) $request->input('sumber_label', '')) ?: ($apiKey?->name ?? 'extension');

        $hasil = [];
        $dibuat = 0;
        $diperbarui = 0;

        foreach ($valid as $indeks => $item) {
            try {
                ['model' => $model, 'aksi' => $aksi] = $this->service->simpan($item, $pembuat, $sumberLabel);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'error' => 'Gagal menyimpan data dukung TPP: '.$e->getMessage(),
                    'indeks' => $indeks,
                ], 500);
            }

            $aksi === 'dibuat' ? $dibuat++ : $diperbarui++;
            $hasil[] = array_merge($this->ringkas($model), ['aksi' => $aksi]);
        }

        return response()->json([
            'ok' => true,
            'jumlah' => count($hasil),
            'dibuat' => $dibuat,
            'diperbarui' => $diperbarui,
            'data' => $hasil,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkas(RekapAbsenTpp $row): array
    {
        return [
            'id' => $row->getKey(),
            'id_tpp' => $row->id_tpp,
            'nip' => $row->nip,
            'nama' => $row->nama,
            'bulan' => $row->bulan,
            'tahun' => $row->tahun,
            'periode' => ($row->bulan >= 1 && $row->bulan <= 12 ? RekapAbsen::BULAN[$row->bulan] : $row->bulan).' '.$row->tahun,
            'jenis' => $row->jenis,
            'keterangan' => $row->keterangan,
            'tanggal' => $row->tanggal
                ->map(fn ($t): ?string => $t->tanggal?->toDateString())
                ->all(),
            'jumlah_hari' => $row->jumlah_hari,
            'file_pendukung_status' => $row->file_pendukung_status,
            'file_pendukung_nama' => $row->file_pendukung_nama,
            'file_pendukung_url' => $row->file_pendukung_url,
            'valid' => $row->valid(),
            'sumber' => $row->sumber,
            'diunggah_at' => $row->imported_at?->toIso8601String(),
        ];
    }
}
