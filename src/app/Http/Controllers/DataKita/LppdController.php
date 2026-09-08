<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LppdController extends Controller
{
    /** Raw SQL fragment classifying a laporan_realisasi_investasi row as PMA. No user input — safe to inline. */
    private const PMA_RAW = "(status LIKE '%PMA%' OR (negara != 'INDONESIA' AND negara IS NOT NULL))";

    /** Raw SQL fragment classifying a laporan_realisasi_investasi row as PMDN. No user input — safe to inline. */
    private const PMDN_RAW = "(status LIKE '%PMDN%' OR negara = 'INDONESIA' OR negara IS NULL)";

    private function listTahun()
    {
        return DB::table('laporan_realisasi_investasi')
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');
    }

    private function resolveTahun(Request $request, $listTahun)
    {
        $default = $listTahun->first() ?? (int) date('Y');

        return $request->filled('tahun') ? (int) $request->input('tahun') : (int) $default;
    }

    // -------------------------------------------------------------------
    // 1. lppd_realisasi_investasi.php
    // -------------------------------------------------------------------
    private function realisasiInvestasiData(int $tahunPilih): array
    {
        $row = DB::table('laporan_realisasi_investasi')
            ->selectRaw('SUM(CASE WHEN '.self::PMA_RAW.' THEN nilai_investasi ELSE 0 END) as pma_realisasi')
            ->selectRaw('SUM(CASE WHEN '.self::PMDN_RAW.' THEN nilai_investasi ELSE 0 END) as pmdn_realisasi')
            ->where('tahun', $tahunPilih)
            ->where('kab_kot', 'like', '%SEMARANG%')
            ->first();

        $pmaRealisasi = $row->pma_realisasi ?? 0;
        $pmdnRealisasi = $row->pmdn_realisasi ?? 0;

        return [
            'pmaRealisasi' => $pmaRealisasi,
            'pmdnRealisasi' => $pmdnRealisasi,
            // Target values are not yet sourced from DB for this report — mirrors legacy (hardcoded 0).
            'pmaTarget' => 0,
            'pmdnTarget' => 0,
            'totalRealisasi' => $pmaRealisasi + $pmdnRealisasi,
            'totalTarget' => 0,
        ];
    }

    public function realisasiInvestasi(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahunPilih = $this->resolveTahun($request, $listTahun);

        return view('datakita.lppd.realisasi-investasi', array_merge([
            'listTahun' => $listTahun,
            'tahunPilih' => $tahunPilih,
        ], $this->realisasiInvestasiData($tahunPilih)));
    }

    public function exportRealisasiInvestasi(Request $request)
    {
        $tahunPilih = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $data = $this->realisasiInvestasiData($tahunPilih);

        return response()->streamDownload(function () use ($data, $tahunPilih) {
            echo view('datakita.lppd.export.realisasi-investasi', array_merge(['tahunPilih' => $tahunPilih], $data))->render();
        }, "Realisasi_vs_Target_Investasi_{$tahunPilih}.xls", ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // -------------------------------------------------------------------
    // 2. lppd_realisasi_per_kecamatan.php
    // -------------------------------------------------------------------
    private function realisasiPerKecamatanData(int $tahunPilih)
    {
        $kecamatanSub = DB::table('2023_dp_proyek')
            ->select('id_proyek', DB::raw('MAX(kecamatan_usaha) as kecamatan'))
            ->groupBy('id_proyek');

        return DB::table('laporan_realisasi_investasi as a')
            ->leftJoinSub($kecamatanSub, 'b', 'a.no_proyek', '=', 'b.id_proyek')
            ->selectRaw("COALESCE(NULLIF(TRIM(b.kecamatan), ''), 'Tidak Terdeteksi') AS nama_kecamatan")
            ->selectRaw(str_replace(['status', 'negara'], ['a.status', 'a.negara'], 'SUM(CASE WHEN '.self::PMA_RAW.' THEN a.nilai_investasi ELSE 0 END) as pma'))
            ->selectRaw(str_replace(['status', 'negara'], ['a.status', 'a.negara'], 'SUM(CASE WHEN '.self::PMDN_RAW.' THEN a.nilai_investasi ELSE 0 END) as pmdn'))
            ->selectRaw('SUM(a.nilai_investasi) as total_investasi')
            ->where('a.tahun', $tahunPilih)
            ->where('a.kab_kot', 'like', '%SEMARANG%')
            ->groupBy('nama_kecamatan')
            ->orderByDesc('total_investasi')
            ->get();
    }

    public function realisasiPerKecamatan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahunPilih = $this->resolveTahun($request, $listTahun);

        $dataLaporan = $this->realisasiPerKecamatanData($tahunPilih);

        return view('datakita.lppd.realisasi-per-kecamatan', [
            'listTahun' => $listTahun,
            'tahunPilih' => $tahunPilih,
            'dataLaporan' => $dataLaporan,
            'totPma' => $dataLaporan->sum('pma'),
            'totPmdn' => $dataLaporan->sum('pmdn'),
            'grandTotal' => $dataLaporan->sum('total_investasi'),
        ]);
    }

    public function exportRealisasiPerKecamatan(Request $request)
    {
        $tahunPilih = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $dataLaporan = $this->realisasiPerKecamatanData($tahunPilih);

        return response()->streamDownload(function () use ($dataLaporan, $tahunPilih) {
            echo view('datakita.lppd.export.realisasi-per-kecamatan', [
                'tahunPilih' => $tahunPilih,
                'dataLaporan' => $dataLaporan,
                'totPma' => $dataLaporan->sum('pma'),
                'totPmdn' => $dataLaporan->sum('pmdn'),
                'grandTotal' => $dataLaporan->sum('total_investasi'),
            ])->render();
        }, "Realisasi_Investasi_PerKecamatan_{$tahunPilih}.xls", ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // -------------------------------------------------------------------
    // 3. lppd_rincian_realisasi.php (paginated, with kecamatan/alamat join)
    // -------------------------------------------------------------------
    private function rincianRealisasiSummary(int $tahunPilih, string $statusPilih): array
    {
        $raw = $statusPilih === 'PMA' ? self::PMA_RAW : self::PMDN_RAW;

        $row = DB::table('laporan_realisasi_investasi')
            ->selectRaw('COUNT(id) as total_data, SUM(nilai_investasi) as total_investasi')
            ->where('tahun', $tahunPilih)
            ->where('kab_kot', 'like', '%SEMARANG%')
            ->whereRaw($raw)
            ->first();

        return [
            'totalRecords' => (int) ($row->total_data ?? 0),
            'grandTotalInvestasi' => $row->total_investasi ?? 0,
        ];
    }

    private function rincianRealisasiRows(int $tahunPilih, string $statusPilih, int $offset, int $limit)
    {
        $raw = str_replace(['status', 'negara'], ['a.status', 'a.negara'], $statusPilih === 'PMA' ? self::PMA_RAW : self::PMDN_RAW);

        $dpSub = DB::table('2023_dp_proyek')
            ->select(
                'id_proyek',
                DB::raw('MAX(kecamatan_usaha) as kecamatan_usaha'),
                DB::raw('MAX(alamat_usaha) as alamat_usaha')
            )
            ->groupBy('id_proyek');

        return DB::table('laporan_realisasi_investasi as a')
            ->leftJoinSub($dpSub, 'b', 'a.no_proyek', '=', 'b.id_proyek')
            ->select(
                'a.nama_perusahaan',
                'a.lokasi_usaha',
                'a.nama_sektor',
                'a.deskripsi_kbli',
                'a.nilai_investasi',
                'a.negara',
                'b.kecamatan_usaha as kecamatan',
                'b.alamat_usaha as alamat_dp'
            )
            ->where('a.tahun', $tahunPilih)
            ->where('a.kab_kot', 'like', '%SEMARANG%')
            ->whereRaw($raw)
            ->orderByDesc('a.nilai_investasi')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    public function rincianRealisasi(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahunPilih = $this->resolveTahun($request, $listTahun);
        $statusPilih = $request->input('status') !== null && $request->input('status') !== '' ? $request->input('status') : 'PMDN';
        $statusPilih = $statusPilih === 'PMA' ? 'PMA' : 'PMDN';

        $limit = 50;
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * $limit;

        $summary = $this->rincianRealisasiSummary($tahunPilih, $statusPilih);
        $totalPages = max(1, (int) ceil($summary['totalRecords'] / $limit));
        $dataInvestasi = $this->rincianRealisasiRows($tahunPilih, $statusPilih, $offset, $limit);

        return view('datakita.lppd.rincian-realisasi', [
            'listTahun' => $listTahun,
            'tahunPilih' => $tahunPilih,
            'statusPilih' => $statusPilih,
            'headerStatusText' => $statusPilih === 'PMA' ? 'PENANAMAN MODAL ASING (PMA)' : 'PENANAMAN MODAL DALAM NEGERI (PMDN)',
            'dataInvestasi' => $dataInvestasi,
            'totalRecords' => $summary['totalRecords'],
            'grandTotalInvestasi' => $summary['grandTotalInvestasi'],
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'totalPages' => $totalPages,
        ]);
    }

    public function exportRincianRealisasi(Request $request)
    {
        $tahunPilih = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $statusPilih = $request->input('status') === 'PMA' ? 'PMA' : 'PMDN';

        $raw = str_replace(['status', 'negara'], ['a.status', 'a.negara'], $statusPilih === 'PMA' ? self::PMA_RAW : self::PMDN_RAW);

        $dpSub = DB::table('2023_dp_proyek')
            ->select('id_proyek', DB::raw('MAX(kecamatan_usaha) as kecamatan_usaha'))
            ->groupBy('id_proyek');

        $rows = DB::table('laporan_realisasi_investasi as a')
            ->leftJoinSub($dpSub, 'b', 'a.no_proyek', '=', 'b.id_proyek')
            ->select('a.nama_perusahaan', 'a.lokasi_usaha', 'a.nama_sektor', 'a.deskripsi_kbli', 'a.nilai_investasi', 'b.kecamatan_usaha as kecamatan')
            ->where('a.tahun', $tahunPilih)
            ->where('a.kab_kot', 'like', '%SEMARANG%')
            ->whereRaw($raw)
            ->orderByDesc('a.nilai_investasi')
            ->get();

        return response()->streamDownload(function () use ($rows, $tahunPilih, $statusPilih) {
            echo view('datakita.lppd.export.rincian-realisasi', [
                'tahunPilih' => $tahunPilih,
                'statusPilih' => $statusPilih,
                'headerStatusText' => $statusPilih === 'PMA' ? 'PENANAMAN MODAL ASING (PMA)' : 'PENANAMAN MODAL DALAM NEGERI (PMDN)',
                'rows' => $rows,
                'totalInvestasi' => $rows->sum('nilai_investasi'),
            ])->render();
        }, "Rincian_Realisasi_{$statusPilih}_{$tahunPilih}.xls", ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // -------------------------------------------------------------------
    // 4. lppd_rekap_investasi.php (N vs N-1 year comparison)
    // -------------------------------------------------------------------
    private function rekapInvestasiData(int $tahunN): array
    {
        $tahunN1 = $tahunN - 1;

        $row = DB::table('laporan_realisasi_investasi')
            ->selectRaw('SUM(CASE WHEN '.self::PMA_RAW.' AND tahun = ? THEN nilai_investasi ELSE 0 END) as pma_n', [$tahunN])
            ->selectRaw('SUM(CASE WHEN '.self::PMA_RAW.' AND tahun = ? THEN nilai_investasi ELSE 0 END) as pma_n_1', [$tahunN1])
            ->selectRaw('SUM(CASE WHEN '.self::PMDN_RAW.' AND tahun = ? THEN nilai_investasi ELSE 0 END) as pmdn_n', [$tahunN])
            ->selectRaw('SUM(CASE WHEN '.self::PMDN_RAW.' AND tahun = ? THEN nilai_investasi ELSE 0 END) as pmdn_n_1', [$tahunN1])
            ->where(function ($q) use ($tahunN, $tahunN1) {
                $q->where('tahun', $tahunN)->orWhere('tahun', $tahunN1);
            })
            ->where('kab_kot', 'like', '%SEMARANG%')
            ->first();

        $pmaN = $row->pma_n ?? 0;
        $pmaN1 = $row->pma_n_1 ?? 0;
        $pmdnN = $row->pmdn_n ?? 0;
        $pmdnN1 = $row->pmdn_n_1 ?? 0;

        return [
            'tahunN' => $tahunN,
            'tahunN1' => $tahunN1,
            'pmaN' => $pmaN,
            'pmaN1' => $pmaN1,
            'pmdnN' => $pmdnN,
            'pmdnN1' => $pmdnN1,
            'totalN' => $pmaN + $pmdnN,
            'totalN1' => $pmaN1 + $pmdnN1,
        ];
    }

    public function rekapInvestasi(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahunN = $this->resolveTahun($request, $listTahun);

        return view('datakita.lppd.rekap-investasi', array_merge([
            'listTahun' => $listTahun,
        ], $this->rekapInvestasiData($tahunN)));
    }

    public function exportRekapInvestasi(Request $request)
    {
        $tahunN = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $data = $this->rekapInvestasiData($tahunN);

        return response()->streamDownload(function () use ($data) {
            echo view('datakita.lppd.export.rekap-investasi', $data)->render();
        }, "Rekapitulasi_Investasi_LPPD_{$tahunN}.xls", ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // -------------------------------------------------------------------
    // 5. lppd_status_investasi.php (paginated, no join)
    // -------------------------------------------------------------------
    private function statusInvestasiSummary(int $tahunPilih, string $statusPilih): array
    {
        $raw = $statusPilih === 'PMA' ? self::PMA_RAW : self::PMDN_RAW;

        $row = DB::table('laporan_realisasi_investasi')
            ->selectRaw('COUNT(id) as total_data, SUM(nilai_investasi) as total_investasi')
            ->where('tahun', $tahunPilih)
            ->where('kab_kot', 'like', '%SEMARANG%')
            ->whereRaw($raw)
            ->first();

        return [
            'totalRecords' => (int) ($row->total_data ?? 0),
            'grandTotalInvestasi' => $row->total_investasi ?? 0,
        ];
    }

    private function statusInvestasiRows(int $tahunPilih, string $statusPilih, int $offset, int $limit)
    {
        $raw = $statusPilih === 'PMA' ? self::PMA_RAW : self::PMDN_RAW;

        return DB::table('laporan_realisasi_investasi')
            ->select('nama_perusahaan', 'lokasi_usaha', 'nama_sektor', 'deskripsi_kbli', 'nilai_investasi')
            ->where('tahun', $tahunPilih)
            ->where('kab_kot', 'like', '%SEMARANG%')
            ->whereRaw($raw)
            ->orderByDesc('nilai_investasi')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    public function statusInvestasi(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahunPilih = $this->resolveTahun($request, $listTahun);
        $statusPilih = $request->input('status') === 'PMA' ? 'PMA' : 'PMDN';

        $limit = 50;
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * $limit;

        $summary = $this->statusInvestasiSummary($tahunPilih, $statusPilih);
        $totalPages = max(1, (int) ceil($summary['totalRecords'] / $limit));
        $dataInvestasi = $this->statusInvestasiRows($tahunPilih, $statusPilih, $offset, $limit);

        return view('datakita.lppd.status-investasi', [
            'listTahun' => $listTahun,
            'tahunPilih' => $tahunPilih,
            'statusPilih' => $statusPilih,
            'headerStatusText' => $statusPilih === 'PMA' ? 'PENANAMAN MODAL ASING (PMA)' : 'PENANAMAN MODAL DALAM NEGERI (PMDN)',
            'dataInvestasi' => $dataInvestasi,
            'totalRecords' => $summary['totalRecords'],
            'grandTotalInvestasi' => $summary['grandTotalInvestasi'],
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'totalPages' => $totalPages,
        ]);
    }

    public function exportStatusInvestasi(Request $request)
    {
        $tahunPilih = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $statusPilih = $request->input('status') === 'PMA' ? 'PMA' : 'PMDN';
        $raw = $statusPilih === 'PMA' ? self::PMA_RAW : self::PMDN_RAW;

        $rows = DB::table('laporan_realisasi_investasi')
            ->select('nama_perusahaan', 'lokasi_usaha', 'nama_sektor', 'deskripsi_kbli', 'nilai_investasi')
            ->where('tahun', $tahunPilih)
            ->where('kab_kot', 'like', '%SEMARANG%')
            ->whereRaw($raw)
            ->orderByDesc('nilai_investasi')
            ->get();

        return response()->streamDownload(function () use ($rows, $tahunPilih, $statusPilih) {
            echo view('datakita.lppd.export.status-investasi', [
                'tahunPilih' => $tahunPilih,
                'statusPilih' => $statusPilih,
                'headerStatusText' => $statusPilih === 'PMA' ? 'PENANAMAN MODAL ASING (PMA)' : 'PENANAMAN MODAL DALAM NEGERI (PMDN)',
                'rows' => $rows,
                'totalInvestasi' => $rows->sum('nilai_investasi'),
            ])->render();
        }, "Rincian_Investasi_{$statusPilih}_{$tahunPilih}.xls", ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // -------------------------------------------------------------------
    // 6. target_investasi.php (list + insert form)
    // -------------------------------------------------------------------
    public function targetInvestasi(Request $request)
    {
        $listTahun = DB::table('target_investasi')
            ->whereNotNull('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');
        if ($listTahun->isEmpty()) {
            $listTahun = collect([(int) date('Y')]);
        }

        $defaultTahun = $listTahun->first();
        $tahunPilih = $request->filled('tahun') ? (int) $request->input('tahun') : (int) $defaultTahun;
        $statusPilih = $request->input('status') === 'PMA' ? 'PMA' : 'PMDN';

        $dataTarget = DB::table('target_investasi')
            ->select('kecamatan', 'target_nilai', 'keterangan')
            ->where('tahun', $tahunPilih)
            ->where('status', $statusPilih)
            ->orderBy('kecamatan')
            ->get();

        return view('datakita.lppd.target-investasi', [
            'listTahun' => $listTahun,
            'tahunPilih' => $tahunPilih,
            'statusPilih' => $statusPilih,
            'headerStatusText' => $statusPilih === 'PMA' ? 'PENANAMAN MODAL ASING (PMA)' : 'PENANAMAN MODAL DALAM NEGERI (PMDN)',
            'dataTarget' => $dataTarget,
            'totalTargetInvestasi' => $dataTarget->sum('target_nilai'),
        ]);
    }

    public function storeTargetInvestasi(Request $request)
    {
        $validated = $request->validate([
            'in_tahun' => ['required', 'integer'],
            'in_status' => ['required', 'in:PMA,PMDN'],
            'in_kecamatan' => ['required', 'string', 'max:150'],
            'in_target' => ['required', 'numeric'],
            'in_ket' => ['nullable', 'string'],
        ]);

        DB::table('target_investasi')->insert([
            'tahun' => $validated['in_tahun'],
            'status' => $validated['in_status'],
            'kecamatan' => $validated['in_kecamatan'],
            'target_nilai' => $validated['in_target'],
            'keterangan' => $validated['in_ket'] ?? null,
        ]);

        return redirect()->route('datakita.lppd.target-investasi.index', [
            'tahun' => $validated['in_tahun'],
            'status' => $validated['in_status'],
        ]);
    }

    public function exportTargetInvestasi(Request $request)
    {
        $tahunPilih = $request->filled('tahun') ? (int) $request->input('tahun') : (int) date('Y');
        $statusPilih = $request->input('status') === 'PMA' ? 'PMA' : 'PMDN';

        $dataTarget = DB::table('target_investasi')
            ->select('kecamatan', 'target_nilai', 'keterangan')
            ->where('tahun', $tahunPilih)
            ->where('status', $statusPilih)
            ->orderBy('kecamatan')
            ->get();

        return response()->streamDownload(function () use ($dataTarget, $tahunPilih, $statusPilih) {
            echo view('datakita.lppd.export.target-investasi', [
                'tahunPilih' => $tahunPilih,
                'statusPilih' => $statusPilih,
                'headerStatusText' => $statusPilih === 'PMA' ? 'PENANAMAN MODAL ASING (PMA)' : 'PENANAMAN MODAL DALAM NEGERI (PMDN)',
                'dataTarget' => $dataTarget,
                'totalTargetInvestasi' => $dataTarget->sum('target_nilai'),
            ])->render();
        }, "Target_Investasi_{$statusPilih}_{$tahunPilih}.xls", ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
