<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewSimbgController extends Controller
{
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private function listTahun()
    {
        return DB::table('simbg_monitoring')
            ->selectRaw('DISTINCT YEAR(tgl_registrasi) as thn')
            ->whereNotNull('tgl_registrasi')
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%')
            ->orderByDesc('thn')
            ->pluck('thn')
            ->filter()
            ->values();
    }

    private function tahunDipilih(Request $request, $listTahun)
    {
        $tahun = $request->query('tahun');
        if ($tahun !== null && $tahun !== '') {
            return (int) $tahun;
        }

        return $listTahun->first() ?? (int) date('Y');
    }

    public function resumePertahun(Request $request)
    {
        $rows = DB::table('simbg_monitoring')
            ->selectRaw("COALESCE(YEAR(tgl_sk), YEAR(tgl_registrasi)) as tahun")
            ->selectRaw('status as status_izin')
            ->selectRaw('COUNT(id) as jumlah')
            ->where(function ($q) {
                $q->whereIn('status', ['Sertifikat SLF Terbit', 'SK PBG Terbit', 'Sertifikat PBG & SLF Terbit'])
                    ->orWhere('status_slf', 'Sertifikat SLF Terbit');
            })
            ->groupByRaw('COALESCE(YEAR(tgl_sk), YEAR(tgl_registrasi)), status')
            ->orderByRaw('COALESCE(YEAR(tgl_sk), YEAR(tgl_registrasi)) DESC')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($r) => [
                'tahun' => $r->tahun ?? 'N/A',
                'status_izin' => $r->status_izin,
                'jumlah' => (int) $r->jumlah,
            ]);

        $total = $rows->sum('jumlah');

        return view('datakita.overview.simbg-resume-pertahun', [
            'rows' => $rows,
            'total' => $total,
        ]);
    }

    public function exportResumePertahun()
    {
        $rows = DB::table('simbg_monitoring')
            ->selectRaw("COALESCE(YEAR(tgl_sk), YEAR(tgl_registrasi)) as tahun")
            ->selectRaw('status as status_izin')
            ->selectRaw('COUNT(id) as jumlah')
            ->where(function ($q) {
                $q->whereIn('status', ['Sertifikat SLF Terbit', 'SK PBG Terbit', 'Sertifikat PBG & SLF Terbit'])
                    ->orWhere('status_slf', 'Sertifikat SLF Terbit');
            })
            ->groupByRaw('COALESCE(YEAR(tgl_sk), YEAR(tgl_registrasi)), status')
            ->orderByRaw('COALESCE(YEAR(tgl_sk), YEAR(tgl_registrasi)) DESC')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($r) => [
                'tahun' => $r->tahun ?? 'N/A',
                'status_izin' => $r->status_izin,
                'jumlah' => (int) $r->jumlah,
            ]);

        $total = $rows->sum('jumlah');
        $filename = 'Rekap_SIMBG_Full_'.date('Y-m-d').'.xls';

        return response()->streamDownload(function () use ($rows, $total) {
            echo view('datakita.overview.simbg-resume-pertahun-export', [
                'rows' => $rows,
                'total' => $total,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function rekapPerkecamatanPertahun(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        $rows = DB::table('simbg_monitoring')
            ->selectRaw('kecamatan_bangunan')
            ->selectRaw('status')
            ->selectRaw('fungsi_bangunan')
            ->selectRaw('COUNT(id) as jumlah')
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%')
            ->where('kecamatan_bangunan', '!=', '')
            ->where(function ($q) {
                $q->where('status', 'like', '%Terbit%')
                    ->orWhere('status_slf', 'like', '%Terbit%');
            })
            ->groupBy('kecamatan_bangunan', 'status', 'fungsi_bangunan')
            ->orderBy('kecamatan_bangunan')
            ->orderBy('status')
            ->orderBy('fungsi_bangunan')
            ->get();

        $total = $rows->sum('jumlah');

        return view('datakita.overview.simbg-rekap-perkecamatan-pertahun', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'rows' => $rows,
            'total' => $total,
        ]);
    }

    public function exportRekapPerkecamatanPertahun(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        $rows = DB::table('simbg_monitoring')
            ->selectRaw('kecamatan_bangunan')
            ->selectRaw('status')
            ->selectRaw('fungsi_bangunan')
            ->selectRaw('COUNT(id) as jumlah')
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%')
            ->where('kecamatan_bangunan', '!=', '')
            ->where(function ($q) {
                $q->where('status', 'like', '%Terbit%')
                    ->orWhere('status_slf', 'like', '%Terbit%');
            })
            ->groupBy('kecamatan_bangunan', 'status', 'fungsi_bangunan')
            ->orderBy('kecamatan_bangunan')
            ->orderBy('status')
            ->orderBy('fungsi_bangunan')
            ->get();

        $total = $rows->sum('jumlah');
        $filename = 'Rekap_Per_Kecamatan_Table_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($rows, $total, $tahun) {
            echo view('datakita.overview.simbg-rekap-perkecamatan-pertahun-export', [
                'rows' => $rows,
                'total' => $total,
                'tahun' => $tahun,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function fungsiPerKecamatan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);
        $kecamatan = (string) $request->query('kecamatan', '');
        $kelurahan = (string) $request->query('kelurahan', '');

        $listKecamatan = DB::table('simbg_monitoring')
            ->selectRaw('DISTINCT kecamatan_bangunan')
            ->where('kecamatan_bangunan', '!=', '')
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%')
            ->orderBy('kecamatan_bangunan')
            ->pluck('kecamatan_bangunan');

        $listKelurahan = collect();
        if ($kecamatan !== '') {
            $listKelurahan = DB::table('simbg_monitoring')
                ->selectRaw('DISTINCT kelurahan_bangunan')
                ->where('kecamatan_bangunan', $kecamatan)
                ->orderBy('kelurahan_bangunan')
                ->pluck('kelurahan_bangunan');
        }

        $query = DB::table('simbg_monitoring')
            ->selectRaw('fungsi_bangunan')
            ->selectRaw('sub_fungsi_bangunan')
            ->selectRaw('COUNT(id) as jumlah')
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where(function ($q) {
                $q->where('status', 'like', '%Terbit%')
                    ->orWhere('status_slf', 'like', '%Terbit%');
            })
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%');

        if ($kecamatan !== '') {
            $query->where('kecamatan_bangunan', $kecamatan);
        }
        if ($kelurahan !== '') {
            $query->where('kelurahan_bangunan', $kelurahan);
        }

        $rows = $query
            ->groupBy('fungsi_bangunan', 'sub_fungsi_bangunan')
            ->orderBy('fungsi_bangunan')
            ->orderByDesc('jumlah')
            ->get();

        $total = $rows->sum('jumlah');

        $lblLokasi = 'Kota Semarang';
        if ($kecamatan !== '') {
            $lblLokasi = 'Kec. '.$kecamatan;
            if ($kelurahan !== '') {
                $lblLokasi .= ' - Kel. '.$kelurahan;
            }
        }

        return view('datakita.overview.simbg-fungsi-per-kecamatan', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'listKecamatan' => $listKecamatan,
            'listKelurahan' => $listKelurahan,
            'kecamatan' => $kecamatan,
            'kelurahan' => $kelurahan,
            'rows' => $rows,
            'total' => $total,
            'lblLokasi' => $lblLokasi,
        ]);
    }

    public function exportFungsiPerKecamatan(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));
        $kecamatan = (string) $request->query('kecamatan', '');
        $kelurahan = (string) $request->query('kelurahan', '');

        $query = DB::table('simbg_monitoring')
            ->selectRaw('fungsi_bangunan')
            ->selectRaw('sub_fungsi_bangunan')
            ->selectRaw('COUNT(id) as jumlah')
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where(function ($q) {
                $q->where('status', 'like', '%Terbit%')
                    ->orWhere('status_slf', 'like', '%Terbit%');
            })
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%');

        if ($kecamatan !== '') {
            $query->where('kecamatan_bangunan', $kecamatan);
        }
        if ($kelurahan !== '') {
            $query->where('kelurahan_bangunan', $kelurahan);
        }

        $rows = $query
            ->groupBy('fungsi_bangunan', 'sub_fungsi_bangunan')
            ->orderBy('fungsi_bangunan')
            ->orderByDesc('jumlah')
            ->get();

        $total = $rows->sum('jumlah');

        $lokasiFile = 'Semarang';
        $judulAtas = 'KOTA SEMARANG';
        if ($kecamatan !== '') {
            $lokasiFile = str_replace(' ', '_', $kecamatan);
            $judulAtas = 'KECAMATAN '.strtoupper($kecamatan);
            if ($kelurahan !== '') {
                $lokasiFile .= '_'.str_replace(' ', '_', $kelurahan);
                $judulAtas .= ' - KELURAHAN '.strtoupper($kelurahan);
            }
        }

        $filename = 'Rekap_Fungsi_'.$lokasiFile.'_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($rows, $total, $tahun, $judulAtas) {
            echo view('datakita.overview.simbg-fungsi-per-kecamatan-export', [
                'rows' => $rows,
                'total' => $total,
                'tahun' => $tahun,
                'judulAtas' => $judulAtas,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function slfPbgPertahun(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        $dataBulan = [];
        $dataBulanPbg = [];
        for ($i = 1; $i <= 12; $i++) {
            $dataBulan[$i] = 0;
            $dataBulanPbg[$i] = 0;
        }
        foreach (DB::table('simbg_monitoring')
            ->selectRaw('MONTH(tgl_registrasi) as bulan_angka')
            ->selectRaw("COUNT(CASE WHEN status LIKE '%slf%' THEN 1 END) as jml_slf")
            ->selectRaw("COUNT(CASE WHEN status LIKE '%pbg%' THEN 1 END) as jml_pbg")
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%')
            ->groupByRaw('MONTH(tgl_registrasi)')
            ->get() as $row) {
            $b = (int) $row->bulan_angka;
            if ($b >= 1 && $b <= 12) {
                $dataBulan[$b] = (int) $row->jml_slf;
                $dataBulanPbg[$b] = (int) $row->jml_pbg;
            }
        }

        $stats = [
            'slf' => array_values($dataBulan),
            'pbg' => array_values($dataBulanPbg),
            'total' => array_map(fn ($i) => $dataBulan[$i] + $dataBulanPbg[$i], range(1, 12)),
        ];

        return view('datakita.overview.simbg-slf-pbg-pertahun', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'dataBulan' => $dataBulan,
            'dataBulanPbg' => $dataBulanPbg,
            'stats' => $stats,
            'namaBulan' => self::NAMA_BULAN,
        ]);
    }

    public function exportSlfPbgPertahun(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        $dataBulan = [];
        $dataBulanPbg = [];
        for ($i = 1; $i <= 12; $i++) {
            $dataBulan[$i] = 0;
            $dataBulanPbg[$i] = 0;
        }
        foreach (DB::table('simbg_monitoring')
            ->selectRaw('MONTH(tgl_registrasi) as bulan_angka')
            ->selectRaw("COUNT(CASE WHEN status LIKE '%slf%' THEN 1 END) as jml_slf")
            ->selectRaw("COUNT(CASE WHEN status LIKE '%pbg%' THEN 1 END) as jml_pbg")
            ->whereRaw('YEAR(tgl_registrasi) = ?', [$tahun])
            ->where('kota_kab_bangunan', 'like', '%KOTA SEMARANG%')
            ->groupByRaw('MONTH(tgl_registrasi)')
            ->get() as $row) {
            $b = (int) $row->bulan_angka;
            if ($b >= 1 && $b <= 12) {
                $dataBulan[$b] = (int) $row->jml_slf;
                $dataBulanPbg[$b] = (int) $row->jml_pbg;
            }
        }

        $stats = [
            'slf' => array_values($dataBulan),
            'pbg' => array_values($dataBulanPbg),
            'total' => array_map(fn ($i) => $dataBulan[$i] + $dataBulanPbg[$i], range(1, 12)),
        ];

        $filename = 'Rekap_SLF_PBG_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($tahun, $dataBulan, $dataBulanPbg, $stats) {
            echo view('datakita.overview.simbg-slf-pbg-pertahun-export', [
                'tahun' => $tahun,
                'dataBulan' => $dataBulan,
                'dataBulanPbg' => $dataBulanPbg,
                'stats' => $stats,
                'namaBulan' => self::NAMA_BULAN,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
