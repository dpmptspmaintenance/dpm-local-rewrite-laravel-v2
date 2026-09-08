<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistikController extends Controller
{
    private function getStats(array $arr): array
    {
        $active = array_filter($arr, fn ($v) => $v > 0);
        $count = count($active);

        return [
            'avg' => $count > 0 ? round(array_sum($active) / $count) : 0,
            'max' => $count > 0 ? max($active) : 0,
            'min' => $count > 0 ? min($active) : 0,
        ];
    }

    // ==========================================
    // STATISTIK KANTOR (2023_dp_nib_kantor)
    // ==========================================

    private function kantorYears(): array
    {
        $years = DB::table('2023_dp_nib_kantor')
            ->selectRaw('DISTINCT YEAR(day_of_tanggal_terbit_oss) as thn')
            ->whereNotNull('day_of_tanggal_terbit_oss')
            ->orderByDesc('thn')
            ->pluck('thn')
            ->filter()
            ->values()
            ->all();

        return empty($years) ? [(int) date('Y')] : $years;
    }

    private function kantorData(int $selectedYear): array
    {
        $dataNib = array_fill(1, 12, 0);
        $totalNib = 0;
        foreach (DB::table('2023_dp_nib_kantor')
            ->selectRaw('MONTH(day_of_tanggal_terbit_oss) as bln, COUNT(id) as jml')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$selectedYear])
            ->groupBy('bln')
            ->get() as $row) {
            $dataNib[(int) $row->bln] = (int) $row->jml;
            $totalNib += (int) $row->jml;
        }

        $dataPma = array_fill(1, 12, 0);
        $dataPmdn = array_fill(1, 12, 0);
        $totalPma = 0;
        $totalPmdn = 0;
        foreach (DB::table('2023_dp_nib_kantor')
            ->selectRaw('MONTH(day_of_tanggal_terbit_oss) as bln, status_penanaman_modal, COUNT(id) as jml')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$selectedYear])
            ->groupBy('bln', 'status_penanaman_modal')
            ->get() as $row) {
            $bln = (int) $row->bln;
            $jml = (int) $row->jml;
            $status = strtoupper(trim((string) $row->status_penanaman_modal));
            if ($status === 'PMA') {
                $dataPma[$bln] += $jml;
                $totalPma += $jml;
            } elseif ($status === 'PMDN') {
                $dataPmdn[$bln] += $jml;
                $totalPmdn += $jml;
            }
        }

        $jenisList = [];
        $dataJenis = [];
        foreach (DB::table('2023_dp_nib_kantor')
            ->selectRaw('MONTH(day_of_tanggal_terbit_oss) as bln, uraian_jenis_perusahaan, COUNT(id) as jml')
            ->whereRaw('YEAR(day_of_tanggal_terbit_oss) = ?', [$selectedYear])
            ->whereNotNull('uraian_jenis_perusahaan')
            ->groupBy('bln', 'uraian_jenis_perusahaan')
            ->get() as $row) {
            $jp = trim((string) $row->uraian_jenis_perusahaan) ?: 'Tidak Diketahui';
            $bln = (int) $row->bln;
            if (! in_array($jp, $jenisList, true)) {
                $jenisList[] = $jp;
            }
            if (! isset($dataJenis[$jp])) {
                $dataJenis[$jp] = array_fill(1, 12, 0);
            }
            $dataJenis[$jp][$bln] += (int) $row->jml;
        }

        return compact('dataNib', 'totalNib', 'dataPma', 'dataPmdn', 'totalPma', 'totalPmdn', 'jenisList', 'dataJenis');
    }

    public function kantor(Request $request)
    {
        $yearsList = $this->kantorYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->kantorData($selectedYear);

        return view('datakita.statistik.kantor', [
            'yearsList' => $yearsList,
            'selectedYear' => $selectedYear,
            'dataNib' => $d['dataNib'],
            'totalNib' => $d['totalNib'],
            'dataPma' => $d['dataPma'],
            'dataPmdn' => $d['dataPmdn'],
            'totalPma' => $d['totalPma'],
            'totalPmdn' => $d['totalPmdn'],
            'jenisList' => $d['jenisList'],
            'dataJenis' => $d['dataJenis'],
            'statNib' => $this->getStats($d['dataNib']),
            'statPma' => $this->getStats($d['dataPma']),
            'statPmdn' => $this->getStats($d['dataPmdn']),
        ]);
    }

    public function kantorExcel(Request $request)
    {
        $yearsList = $this->kantorYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->kantorData($selectedYear);

        return view('datakita.statistik.kantor-download-excel', [
            'selectedYear' => $selectedYear,
            'dataNib' => $d['dataNib'],
            'totalNib' => $d['totalNib'],
            'dataPma' => $d['dataPma'],
            'dataPmdn' => $d['dataPmdn'],
            'totalPma' => $d['totalPma'],
            'totalPmdn' => $d['totalPmdn'],
            'jenisList' => $d['jenisList'],
            'dataJenis' => $d['dataJenis'],
            'statNib' => $this->getStats($d['dataNib']),
            'statPma' => $this->getStats($d['dataPma']),
            'statPmdn' => $this->getStats($d['dataPmdn']),
        ]);
    }

    // ==========================================
    // STATISTIK PROYEK (2023_dp_proyek)
    // ==========================================

    private function proyekYears(): array
    {
        $years = DB::table('2023_dp_proyek')
            ->selectRaw('DISTINCT YEAR(tanggal_terbit_oss) as thn')
            ->whereNotNull('tanggal_terbit_oss')
            ->orderByDesc('thn')
            ->pluck('thn')
            ->filter()
            ->values()
            ->all();

        return empty($years) ? [(int) date('Y')] : $years;
    }

    private function proyekData(int $selectedYear): array
    {
        $dataProyek = array_fill(1, 12, 0);
        $dataInvestasi = array_fill(1, 12, 0);
        $totalProyek = 0;
        $totalInvestasi = 0;
        foreach (DB::table('2023_dp_proyek')
            ->selectRaw('MONTH(tanggal_terbit_oss) as bln, COUNT(id) as jml_proyek, SUM(jumlah_investasi3) as jml_investasi')
            ->whereRaw('YEAR(tanggal_terbit_oss) = ?', [$selectedYear])
            ->groupBy('bln')
            ->get() as $row) {
            $bln = (int) $row->bln;
            $dataProyek[$bln] = (int) $row->jml_proyek;
            $dataInvestasi[$bln] = (float) $row->jml_investasi;
            $totalProyek += $dataProyek[$bln];
            $totalInvestasi += $dataInvestasi[$bln];
        }

        $risikoList = [];
        $dataRisikoProyek = [];
        $dataRisikoInvestasi = [];
        foreach (DB::table('2023_dp_proyek')
            ->selectRaw('MONTH(tanggal_terbit_oss) as bln, uraian_risiko_proyek, COUNT(id) as jml_proyek, SUM(jumlah_investasi3) as jml_investasi')
            ->whereRaw('YEAR(tanggal_terbit_oss) = ?', [$selectedYear])
            ->groupBy('bln', 'uraian_risiko_proyek')
            ->get() as $row) {
            $risiko = trim((string) $row->uraian_risiko_proyek) ?: 'Tidak Diketahui';
            $bln = (int) $row->bln;
            if (! in_array($risiko, $risikoList, true)) {
                $risikoList[] = $risiko;
            }
            if (! isset($dataRisikoProyek[$risiko])) {
                $dataRisikoProyek[$risiko] = array_fill(1, 12, 0);
                $dataRisikoInvestasi[$risiko] = array_fill(1, 12, 0);
            }
            $dataRisikoProyek[$risiko][$bln] += (int) $row->jml_proyek;
            $dataRisikoInvestasi[$risiko][$bln] += (float) $row->jml_investasi;
        }

        return compact('dataProyek', 'dataInvestasi', 'totalProyek', 'totalInvestasi', 'risikoList', 'dataRisikoProyek', 'dataRisikoInvestasi');
    }

    public function proyek(Request $request)
    {
        $yearsList = $this->proyekYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->proyekData($selectedYear);

        return view('datakita.statistik.proyek', array_merge($d, [
            'yearsList' => $yearsList,
            'selectedYear' => $selectedYear,
            'statProyek' => $this->getStats($d['dataProyek']),
            'statInvestasi' => $this->getStats($d['dataInvestasi']),
        ]));
    }

    public function proyekExcel(Request $request)
    {
        $yearsList = $this->proyekYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->proyekData($selectedYear);

        return view('datakita.statistik.proyek-download-excel', array_merge($d, [
            'selectedYear' => $selectedYear,
            'statProyek' => $this->getStats($d['dataProyek']),
            'statInvestasi' => $this->getStats($d['dataInvestasi']),
        ]));
    }

    public function proyekWord(Request $request)
    {
        $yearsList = $this->proyekYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->proyekData($selectedYear);
        $statProyek = $this->getStats($d['dataProyek']);

        $filename = 'Laporan_Statistik_DPMPTSP_'.$selectedYear.'.doc';

        return response()->streamDownload(function () use ($d, $selectedYear, $statProyek) {
            echo view('datakita.statistik.proyek-download-word', array_merge($d, [
                'selectedYear' => $selectedYear,
                'statProyek' => $statProyek,
            ]))->render();
        }, $filename, ['Content-Type' => 'application/msword']);
    }

    // ==========================================
    // STATISTIK IZIN (2023_list_izin)
    // ==========================================

    private const KATEGORI_RISIKO = ['KOSONG', 'MR', 'MT', 'R', 'T'];

    private function mapRisiko(?string $kd): string
    {
        $kd = strtoupper(trim((string) $kd));

        return in_array($kd, ['MR', 'MT', 'R', 'T'], true) ? $kd : 'KOSONG';
    }

    private function izinYears(): array
    {
        $years = DB::table('2023_list_izin')
            ->selectRaw('DISTINCT YEAR(tanggal_permohonan) as thn')
            ->whereNotNull('tanggal_permohonan')
            ->orderByDesc('thn')
            ->pluck('thn')
            ->filter()
            ->values()
            ->all();

        return empty($years) ? [(int) date('Y')] : $years;
    }

    private function getStatsRef(array $arr, array $referenceArr): array
    {
        $activeMonths = [];
        for ($i = 1; $i <= 12; $i++) {
            if (($referenceArr[$i] ?? 0) > 0) {
                $activeMonths[] = $arr[$i] ?? 0;
            }
        }
        $count = count($activeMonths);

        return [
            'avg' => $count > 0 ? round(array_sum($activeMonths) / $count) : 0,
            'max' => $count > 0 ? max($activeMonths) : 0,
            'min' => $count > 0 ? min($activeMonths) : 0,
        ];
    }

    private function izinData(int $selectedYear): array
    {
        $dataResiko = [];
        foreach (self::KATEGORI_RISIKO as $kr) {
            $dataResiko[$kr] = array_fill(1, 12, 0);
        }
        $dataTotal = array_fill(1, 12, 0);
        $grandTotal = 0;

        foreach (DB::table('2023_list_izin')
            ->selectRaw('MONTH(tanggal_permohonan) as bln, kd_resiko, COUNT(id) as jml')
            ->whereRaw('YEAR(tanggal_permohonan) = ?', [$selectedYear])
            ->groupBy('bln', 'kd_resiko')
            ->get() as $row) {
            $bln = (int) $row->bln;
            $jml = (int) $row->jml;
            $kr = $this->mapRisiko($row->kd_resiko);
            $dataResiko[$kr][$bln] += $jml;
            $dataTotal[$bln] += $jml;
            $grandTotal += $jml;
        }

        $jenisList = [];
        $dataJenis = [];
        foreach (DB::table('2023_list_izin')
            ->selectRaw('MONTH(tanggal_permohonan) as bln, uraian_jenis_perizinan, COUNT(id) as jml')
            ->whereRaw('YEAR(tanggal_permohonan) = ?', [$selectedYear])
            ->whereNotNull('uraian_jenis_perizinan')
            ->groupBy('bln', 'uraian_jenis_perizinan')
            ->get() as $row) {
            $jp = trim((string) $row->uraian_jenis_perizinan) ?: 'Tidak Diketahui';
            $bln = (int) $row->bln;
            if (! in_array($jp, $jenisList, true)) {
                $jenisList[] = $jp;
            }
            if (! isset($dataJenis[$jp])) {
                $dataJenis[$jp] = array_fill(1, 12, 0);
            }
            $dataJenis[$jp][$bln] += (int) $row->jml;
        }

        $dokumenList = [];
        $dataDokumen = [];
        foreach (DB::table('2023_list_izin')
            ->selectRaw('MONTH(tanggal_permohonan) as bln, nama_dokumen, COUNT(id) as jml')
            ->whereRaw('YEAR(tanggal_permohonan) = ?', [$selectedYear])
            ->whereNotNull('nama_dokumen')
            ->groupBy('bln', 'nama_dokumen')
            ->get() as $row) {
            $nd = trim((string) $row->nama_dokumen) ?: 'Tidak Diketahui';
            $bln = (int) $row->bln;
            if (! in_array($nd, $dokumenList, true)) {
                $dokumenList[] = $nd;
            }
            if (! isset($dataDokumen[$nd])) {
                $dataDokumen[$nd] = array_fill(1, 12, 0);
            }
            $dataDokumen[$nd][$bln] += (int) $row->jml;
        }

        $responList = [];
        $dataRespon = [];
        foreach (DB::table('2023_list_izin')
            ->selectRaw('MONTH(tanggal_permohonan) as bln, uraian_status_respon, COUNT(id) as jml')
            ->whereRaw('YEAR(tanggal_permohonan) = ?', [$selectedYear])
            ->whereNotNull('uraian_status_respon')
            ->groupBy('bln', 'uraian_status_respon')
            ->get() as $row) {
            $sr = trim((string) $row->uraian_status_respon) ?: 'Tidak Diketahui';
            $bln = (int) $row->bln;
            if (! in_array($sr, $responList, true)) {
                $responList[] = $sr;
            }
            if (! isset($dataRespon[$sr])) {
                $dataRespon[$sr] = array_fill(1, 12, 0);
            }
            $dataRespon[$sr][$bln] += (int) $row->jml;
        }

        return compact('dataResiko', 'dataTotal', 'grandTotal', 'jenisList', 'dataJenis', 'dokumenList', 'dataDokumen', 'responList', 'dataRespon');
    }

    public function izin(Request $request)
    {
        $yearsList = $this->izinYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->izinData($selectedYear);

        $statTotal = $this->getStatsRef($d['dataTotal'], $d['dataTotal']);
        $statResiko = [];
        foreach (self::KATEGORI_RISIKO as $kr) {
            $statResiko[$kr] = $this->getStatsRef($d['dataResiko'][$kr], $d['dataTotal']);
        }

        return view('datakita.statistik.izin', array_merge($d, [
            'yearsList' => $yearsList,
            'selectedYear' => $selectedYear,
            'kategoriRisiko' => self::KATEGORI_RISIKO,
            'statTotal' => $statTotal,
            'statResiko' => $statResiko,
            'getStatsRef' => fn ($arr) => $this->getStatsRef($arr, $d['dataTotal']),
        ]));
    }

    public function izinExcel(Request $request)
    {
        $yearsList = $this->izinYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $d = $this->izinData($selectedYear);

        $statTotal = $this->getStatsRef($d['dataTotal'], $d['dataTotal']);
        $statResiko = [];
        foreach (self::KATEGORI_RISIKO as $kr) {
            $statResiko[$kr] = $this->getStatsRef($d['dataResiko'][$kr], $d['dataTotal']);
        }

        return view('datakita.statistik.izin-download-excel', array_merge($d, [
            'selectedYear' => $selectedYear,
            'kategoriRisiko' => self::KATEGORI_RISIKO,
            'statTotal' => $statTotal,
            'statResiko' => $statResiko,
        ]));
    }

    // ==========================================
    // STATISTIK SIMBG (simbg_monitoring)
    // ==========================================

    private function simbgYears(): array
    {
        $years = DB::table('simbg_monitoring')
            ->selectRaw('DISTINCT YEAR(tgl_registrasi) as thn')
            ->whereNotNull('tgl_registrasi')
            ->orderByDesc('thn')
            ->pluck('thn')
            ->filter()
            ->values()
            ->all();

        return empty($years) ? [(int) date('Y')] : $years;
    }

    public function simbg(Request $request)
    {
        $yearsList = $this->simbgYears();
        $selectedYear = (int) $request->query('tahun', $yearsList[0]);

        $kecamatanList = DB::table('simbg_monitoring')
            ->select('kecamatan_bangunan')
            ->distinct()
            ->whereNotNull('kecamatan_bangunan')
            ->where('kecamatan_bangunan', '!=', '')
            ->orderBy('kecamatan_bangunan')
            ->pluck('kecamatan_bangunan')
            ->map(fn ($v) => trim((string) $v))
            ->values()
            ->all();
        $selectedKecamatan = $kecamatanList[0] ?? '';

        $dataTotal = array_fill(1, 12, 0);
        $grandTotal = 0;
        $dataTotalKec = array_fill(1, 12, 0);
        $grandTotalKec = 0;

        $dataJp = [];
        $dataSt = [];
        $dataKel = [];
        $dataFb = [];
        $dataSlf = [];
        $dataTb = [];
        $catLb = ['< 100', '100 - 500', '500 - 1000', '1000 - 2000', '> 2000'];
        $catJl = ['1 Lantai', '2 Lantai', '3 Lantai', '4 Lantai', '> 4 Lantai'];
        $dataLb = [];
        foreach ($catLb as $c) {
            $dataLb[$c] = array_fill(1, 12, 0);
        }
        $dataJl = [];
        foreach ($catJl as $c) {
            $dataJl[$c] = array_fill(1, 12, 0);
        }

        $rows = DB::table('simbg_monitoring')
            ->select('jenis_permohonan', 'status', 'kecamatan_bangunan', 'kelurahan_bangunan', 'fungsi_bangunan', 'status_slf', 'tipe_bangunan', 'luas_bangunan', 'jumlah_lantai')
            ->selectRaw('MONTH(tgl_registrasi) as bln')
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$selectedYear])
            ->get();

        foreach ($rows as $r) {
            $bln = (int) $r->bln;
            if ($bln < 1 || $bln > 12) {
                continue;
            }

            $dataTotal[$bln]++;
            $grandTotal++;

            $jp = trim((string) $r->jenis_permohonan) ?: 'Tidak Diketahui';
            $dataJp[$jp] ??= array_fill(1, 12, 0);
            $dataJp[$jp][$bln]++;

            $st = trim((string) $r->status) ?: 'Tidak Diketahui';
            $dataSt[$st] ??= array_fill(1, 12, 0);
            $dataSt[$st][$bln]++;

            if (trim((string) $r->kecamatan_bangunan) === $selectedKecamatan) {
                $kel = trim((string) $r->kelurahan_bangunan) ?: 'Tidak Diketahui';
                $dataKel[$kel] ??= array_fill(1, 12, 0);
                $dataKel[$kel][$bln]++;
                $dataTotalKec[$bln]++;
                $grandTotalKec++;
            }

            $fb = trim((string) $r->fungsi_bangunan) ?: 'Tidak Diketahui';
            $dataFb[$fb] ??= array_fill(1, 12, 0);
            $dataFb[$fb][$bln]++;

            $slf = trim((string) $r->status_slf) ?: 'Tidak Diketahui';
            $dataSlf[$slf] ??= array_fill(1, 12, 0);
            $dataSlf[$slf][$bln]++;

            $tb = trim((string) $r->tipe_bangunan) ?: 'Tidak Diketahui';
            $dataTb[$tb] ??= array_fill(1, 12, 0);
            $dataTb[$tb][$bln]++;

            $luas = (float) $r->luas_bangunan;
            $clb = match (true) {
                $luas < 100 => '< 100',
                $luas <= 500 => '100 - 500',
                $luas <= 1000 => '500 - 1000',
                $luas <= 2000 => '1000 - 2000',
                default => '> 2000',
            };
            $dataLb[$clb][$bln]++;

            $lantai = (int) $r->jumlah_lantai;
            $cjl = match (true) {
                $lantai <= 1 => '1 Lantai',
                $lantai === 2 => '2 Lantai',
                $lantai === 3 => '3 Lantai',
                $lantai === 4 => '4 Lantai',
                default => '> 4 Lantai',
            };
            $dataJl[$cjl][$bln]++;
        }

        $blocks = [
            ['title' => 'JENIS PERMOHONAN', 'data' => $dataJp, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartJP'],
            ['title' => 'STATUS', 'data' => $dataSt, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartStatus'],
            ['title' => 'KELURAHAN', 'data' => $dataKel, 'tot_arr' => $dataTotalKec, 'g_tot' => $grandTotalKec, 'chartId' => 'chartKelurahan'],
            ['title' => 'FUNGSI BANGUNAN', 'data' => $dataFb, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartFB'],
            ['title' => 'STATUS SLF', 'data' => $dataSlf, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartSLF'],
            ['title' => 'TIPE BANGUNAN', 'data' => $dataTb, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartTB'],
            ['title' => 'LUAS BANGUNAN (m2)', 'data' => $dataLb, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartLB'],
            ['title' => 'TINGGI BANGUNAN', 'data' => $dataJl, 'tot_arr' => $dataTotal, 'g_tot' => $grandTotal, 'chartId' => 'chartJL'],
        ];

        return view('datakita.statistik.simbg', [
            'yearsList' => $yearsList,
            'selectedYear' => $selectedYear,
            'kecamatanList' => $kecamatanList,
            'selectedKecamatan' => $selectedKecamatan,
            'blocks' => $blocks,
            'getStatsRef' => fn ($arr, $ref) => $this->getStatsRef($arr, $ref),
        ]);
    }

    public function simbgKelurahanAjax(Request $request)
    {
        $tahun = (int) $request->query('tahun');
        $kecamatan = (string) $request->query('kecamatan', '');

        $dataKel = [];
        $dataTotalKec = array_fill(1, 12, 0);
        $grandTotalKec = 0;

        foreach (DB::table('simbg_monitoring')
            ->selectRaw('MONTH(tgl_registrasi) as bln, kelurahan_bangunan, COUNT(id) as jml')
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where('kecamatan_bangunan', $kecamatan)
            ->groupBy('bln', 'kelurahan_bangunan')
            ->get() as $r) {
            $bln = (int) $r->bln;
            if ($bln < 1 || $bln > 12) {
                continue;
            }
            $kel = trim((string) $r->kelurahan_bangunan) ?: 'Tidak Diketahui';
            $dataKel[$kel] ??= array_fill(1, 12, 0);
            $jml = (int) $r->jml;
            $dataKel[$kel][$bln] += $jml;
            $dataTotalKec[$bln] += $jml;
            $grandTotalKec += $jml;
        }

        $html = view('datakita.statistik.partials.simbg-table-rows', [
            'dataArray' => $dataKel,
            'totalArr' => $dataTotalKec,
            'gTotal' => $grandTotalKec,
            'getStatsRef' => fn ($arr, $ref) => $this->getStatsRef($arr, $ref),
        ])->render();

        $palette = ['#e5536b', '#569bd5', '#e5d836', '#4caf50', '#9c27b0', '#ff9800', '#00bcd4', '#795548', '#607d8b', '#e91e63'];
        $chartDatasets = [];
        $cIdx = 0;
        foreach ($dataKel as $key => $arrVal) {
            $chartDatasets[] = [
                'label' => (string) $key,
                'data' => array_values($arrVal),
                'backgroundColor' => $palette[$cIdx % count($palette)],
                'borderColor' => 'white',
                'borderWidth' => 1,
            ];
            $cIdx++;
        }

        return response()->json([
            'html' => $html,
            'chart' => $chartDatasets,
            'title_suffix' => ' ('.$kecamatan.')',
        ]);
    }
}
