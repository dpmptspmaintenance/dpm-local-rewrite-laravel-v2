<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiKey;
use App\Models\Kepegawaian\RekapAbsen;
use App\Services\Kepegawaian\RekapAbsenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * API Rekap Absen Bulanan untuk Chrome extension presensi (secure-sadewa).
 *
 * Unggah ulang dengan nip+tahun+bulan sama akan MENGGANTI rincian harian dan
 * data dukung periode itu (upsert). Tidak ada endpoint hapus.
 */
class RekapAbsenController
{
    public function __construct(private readonly RekapAbsenService $service) {}

    /**
     * GET /api/v1/rekap-absen?nip=&tahun=&bulan=
     */
    public function index(Request $request): JsonResponse
    {
        $nip = trim((string) $request->query('nip', ''));

        $bulanMentah = $request->query('bulan');
        $bulan = $bulanMentah === null || $bulanMentah === ''
            ? null
            : RekapAbsen::bulanAngka($bulanMentah);

        if ($bulanMentah !== null && $bulanMentah !== '' && $bulan === null) {
            return response()->json(['ok' => false, 'error' => 'Parameter bulan tidak dikenali.'], 422);
        }

        $rows = RekapAbsen::query()
            ->withCount(['harian', 'dataDukung'])
            ->when($nip !== '', fn ($query) => $query->where('nip', $nip))
            ->when($request->filled('tahun'), fn ($query) => $query->where('tahun', (int) $request->query('tahun')))
            ->when($bulan !== null, fn ($query) => $query->where('bulan', $bulan))
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderBy('nama')
            ->limit(200)
            ->get();

        return response()->json([
            'ok' => true,
            'jumlah' => $rows->count(),
            'data' => $rows->map(fn (RekapAbsen $row): array => $this->ringkas($row))->all(),
        ]);
    }

    /**
     * GET /api/v1/rekap-absen/{id}
     */
    public function show(int $id): JsonResponse
    {
        $row = RekapAbsen::query()->with(['harian', 'dataDukung'])->find($id);

        if ($row === null) {
            return response()->json(['ok' => false, 'error' => 'Rekap absen tidak ditemukan.'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => array_merge($this->ringkas($row), [
                'harian' => $row->harian->map(fn ($harian): array => [
                    'tanggal' => $harian->tanggal?->toDateString(),
                    'hari' => $harian->hari,
                    'jam_hadir' => $harian->jam_hadir?->format('Y-m-d H:i:s'),
                    'hadir_selisih' => $harian->hadir_selisih,
                    'jam_pulang' => $harian->jam_pulang?->format('Y-m-d H:i:s'),
                    'pulang_selisih' => $harian->pulang_selisih,
                    'status' => $harian->status,
                ])->all(),
                'data_dukung' => $row->dataDukung->map(fn ($dukung): array => [
                    'tanggal_mulai' => $dukung->tanggal_mulai?->toDateString(),
                    'tanggal_selesai' => $dukung->tanggal_selesai?->toDateString(),
                    'keterangan' => $dukung->keterangan,
                    'jenis' => $dukung->jenis,
                    'tautan' => $dukung->tautan(),
                ])->all(),
            ]),
        ]);
    }

    /**
     * POST /api/v1/rekap-absen
     *
     * Body: satu objek rekap, atau {"items": [ ...objek rekap ]}.
     */
    public function simpan(Request $request): JsonResponse
    {
        $items = $request->input('items');
        $payloads = is_array($items) && $items !== []
            ? array_values($items)
            : [$request->except('items')];

        if (count($payloads) > RekapAbsenService::MAKS_ITEM) {
            return response()->json([
                'ok' => false,
                'error' => 'Maksimal '.RekapAbsenService::MAKS_ITEM.' rekap per request (dikirim '.count($payloads).').',
            ], 422);
        }

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');
        $pembuat = $apiKey?->user_id !== null ? \App\Models\User::find($apiKey->user_id) : null;
        $sumberLabel = trim((string) $request->input('sumber_label', '')) ?: ($apiKey?->name ?? 'extension');

        $errors = [];
        $valid = [];

        foreach ($payloads as $indeks => $item) {
            if (! is_array($item)) {
                $errors[$indeks] = ['Payload harus berupa objek.'];

                continue;
            }

            $validator = Validator::make($item, [
                'nip' => ['required', 'string', 'max:30'],
                'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
                'bulan' => ['required'],
                'nama' => ['nullable', 'string', 'max:150'],
                'harian' => ['nullable', 'array', 'max:'.RekapAbsenService::MAKS_HARIAN],
                'data_dukung' => ['nullable', 'array', 'max:'.RekapAbsenService::MAKS_DATA_DUKUNG],
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

        $hasil = [];

        foreach ($valid as $indeks => $item) {
            try {
                ['model' => $model, 'aksi' => $aksi] = $this->service->simpan($item, $pembuat, $sumberLabel);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'ok' => false,
                    'error' => 'Gagal menyimpan rekap absen: '.$e->getMessage(),
                    'indeks' => $indeks,
                ], 500);
            }

            $hasil[] = array_merge($this->ringkas($model), ['aksi' => $aksi]);
        }

        return response()->json([
            'ok' => true,
            'jumlah' => count($hasil),
            'data' => $hasil,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkas(RekapAbsen $row): array
    {
        return [
            'id' => $row->id,
            'nip' => $row->nip,
            'nama' => $row->nama,
            'jabatan' => $row->jabatan,
            'opd' => $row->opd,
            'bulan' => $row->bulan,
            'tahun' => $row->tahun,
            'periode' => $row->labelPeriode(),
            'hari_kerja' => $row->hari_kerja,
            'hadir' => $row->jumlah_hadir,
            'tidak_hadir' => $row->jumlah_tidak_hadir,
            'konfirmasi' => $row->jumlah_konfirmasi,
            'keterlambatan' => $row->total_keterlambatan,
            'jumlah_harian' => $row->harian_count ?? $row->harian()->count(),
            'jumlah_data_dukung' => $row->data_dukung_count ?? $row->dataDukung()->count(),
            'diunggah_at' => $row->diunggah_at?->toIso8601String(),
            'diunggah_oleh' => $row->diunggah_oleh_nama,
        ];
    }
}
