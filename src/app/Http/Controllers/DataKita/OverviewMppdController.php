<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewMppdController extends Controller
{
    private const LIST_BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const LIST_BULAN_KODE = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];

    private const LIST_KECAMATAN = [
        'Banyumanik', 'Candisari', 'Gajahmungkur', 'Gayamsari', 'Genuk', 'Gunungpati',
        'Mijen', 'Ngaliyan', 'Pedurungan', 'Semarang Barat', 'Semarang Selatan',
        'Semarang Tengah', 'Semarang Timur', 'Semarang Utara', 'Tembalang', 'Tugu',
    ];

    private function listTahun()
    {
        return DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('DISTINCT YEAR(waktu_input) as thn')
            ->whereNotNull('waktu_input')
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

    private function sipPerKecamatanData($tahun)
    {
        $selectParts = [];
        foreach (self::LIST_KECAMATAN as $index => $namaKec) {
            $selectParts[] = "SUM(CASE WHEN alamat LIKE ? OR tempat_praktik LIKE ? THEN 1 ELSE 0 END) as k{$index}";
        }

        $bindings = [];
        foreach (self::LIST_KECAMATAN as $namaKec) {
            $bindings[] = '%'.$namaKec.'%';
            $bindings[] = '%'.$namaKec.'%';
        }

        $row = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw(implode(', ', $selectParts))
            ->selectRaw('COUNT(id) as total_global')
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
            ->addBinding($bindings, 'select')
            ->first();

        $dataFinal = [];
        $total = 0;
        if ($row) {
            foreach (self::LIST_KECAMATAN as $index => $namaKec) {
                $jml = (int) $row->{'k'.$index};
                $dataFinal[] = ['kecamatan' => $namaKec, 'jumlah' => $jml];
                $total += $jml;
            }
        }

        return [$dataFinal, $total];
    }

    public function sipPerkecamatan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        [$dataFinal, $total] = $this->sipPerKecamatanData($tahun);

        return view('datakita.overview.mppd-sip-perkecamatan', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'rows' => $dataFinal,
            'total' => $total,
        ]);
    }

    public function exportSipPerkecamatan(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        [$dataFinal, $total] = $this->sipPerKecamatanData($tahun);

        $filename = 'Laporan_SIP_Perkecamatan_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($dataFinal, $total, $tahun) {
            echo view('datakita.overview.mppd-sip-perkecamatan-export', [
                'rows' => $dataFinal,
                'total' => $total,
                'tahun' => $tahun,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function sipPerFasilitas(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        $rows = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('tempat_praktik')
            ->selectRaw('COUNT(id) as jumlah')
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
            ->groupBy('tempat_praktik')
            ->orderByDesc('jumlah')
            ->get();

        $total = $rows->sum('jumlah');

        return view('datakita.overview.mppd-sip-per-fasilitas', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'rows' => $rows,
            'total' => $total,
        ]);
    }

    public function exportSipPerFasilitas(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        $rows = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('tempat_praktik')
            ->selectRaw('COUNT(id) as jumlah')
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
            ->groupBy('tempat_praktik')
            ->orderByDesc('jumlah')
            ->get();

        $total = $rows->sum('jumlah');

        $filename = 'Laporan_Tempat_Praktik_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($rows, $total, $tahun) {
            echo view('datakita.overview.mppd-sip-per-fasilitas-export', [
                'rows' => $rows,
                'total' => $total,
                'tahun' => $tahun,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function resumePerbulanData($tahun)
    {
        $dataBulan = [];
        for ($i = 1; $i <= 12; $i++) {
            $dataBulan[$i] = ['batal' => 0, 'tolak' => 0, 'verif' => 0, 'terbit' => 0];
        }

        $grandTotal = 0;

        $rows = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('MONTH(waktu_input) as bulan_angka')
            ->selectRaw("COUNT(CASE WHEN status LIKE '%Batal%' THEN 1 END) as jml_batal")
            ->selectRaw("COUNT(CASE WHEN status LIKE '%Tolak%' THEN 1 END) as jml_tolak")
            ->selectRaw("COUNT(CASE WHEN status LIKE '%Verifikasi%' OR status LIKE '%Validasi%' THEN 1 END) as jml_verif")
            ->selectRaw("COUNT(CASE WHEN status LIKE '%Terbit%' OR status LIKE '%Selesai%' OR status LIKE '%Cetak%' THEN 1 END) as jml_terbit")
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
            ->groupByRaw('MONTH(waktu_input)')
            ->get();

        foreach ($rows as $row) {
            $b = (int) $row->bulan_angka;
            if ($b >= 1 && $b <= 12) {
                $dataBulan[$b]['batal'] = (int) $row->jml_batal;
                $dataBulan[$b]['tolak'] = (int) $row->jml_tolak;
                $dataBulan[$b]['verif'] = (int) $row->jml_verif;
                $dataBulan[$b]['terbit'] = (int) $row->jml_terbit;
                $grandTotal += (int) $row->jml_batal + (int) $row->jml_tolak + (int) $row->jml_verif + (int) $row->jml_terbit;
            }
        }

        return [$dataBulan, $grandTotal];
    }

    public function resumePerbulan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        [$dataBulan, $grandTotal] = $this->resumePerbulanData($tahun);

        return view('datakita.overview.mppd-resume-perbulan', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'dataBulan' => $dataBulan,
            'grandTotal' => $grandTotal,
            'namaBulan' => self::LIST_BULAN_NAMA,
        ]);
    }

    public function exportResumePerbulan(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        [$dataBulan, $grandTotal] = $this->resumePerbulanData($tahun);

        $filename = 'Resume_SIP_Perbulan_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($tahun, $dataBulan, $grandTotal) {
            echo view('datakita.overview.mppd-resume-perbulan-export', [
                'tahun' => $tahun,
                'dataBulan' => $dataBulan,
                'grandTotal' => $grandTotal,
                'namaBulan' => self::LIST_BULAN_NAMA,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function profesiData($tahun, $bulan)
    {
        $query = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('profesi')
            ->selectRaw('COUNT(*) as total_per_profesi')
            ->selectRaw("SUM(CASE WHEN status LIKE '%Batal%' THEN 1 ELSE 0 END) as jml_batal")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Tolak%' THEN 1 ELSE 0 END) as jml_tolak")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Verifikasi%' OR status LIKE '%Validasi%' THEN 1 ELSE 0 END) as jml_verif")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Terbit%' OR status LIKE '%Selesai%' OR status LIKE '%Cetak%' THEN 1 ELSE 0 END) as jml_terbit")
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(waktu_input) = ?', [$bulan]);
        }

        $rows = $query
            ->groupBy('profesi')
            ->orderBy('profesi')
            ->get();

        $totalBatal = 0;
        $totalTolak = 0;
        $totalVerif = 0;
        $totalTerbit = 0;
        $grandTotal = 0;
        foreach ($rows as $row) {
            $totalBatal += (int) $row->jml_batal;
            $totalTolak += (int) $row->jml_tolak;
            $totalVerif += (int) $row->jml_verif;
            $totalTerbit += (int) $row->jml_terbit;
            $grandTotal += (int) $row->total_per_profesi;
        }

        return [
            'rows' => $rows,
            'totalBatal' => $totalBatal,
            'totalTolak' => $totalTolak,
            'totalVerif' => $totalVerif,
            'totalTerbit' => $totalTerbit,
            'grandTotal' => $grandTotal,
        ];
    }

    public function profesiPertahun(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);
        $bulan = (string) $request->query('bulan', '');

        $data = $this->profesiData($tahun, $bulan);

        $labelPeriode = 'Tahun '.$tahun;
        if ($bulan !== '' && isset(self::LIST_BULAN_KODE[$bulan])) {
            $labelPeriode = self::LIST_BULAN_KODE[$bulan].' '.$tahun;
        }

        return view('datakita.overview.mppd-profesi-pertahun', array_merge([
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'listBulan' => self::LIST_BULAN_KODE,
            'labelPeriode' => $labelPeriode,
        ], $data));
    }

    public function exportProfesiPertahun(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));
        $bulan = (string) $request->query('bulan', '');

        $data = $this->profesiData($tahun, $bulan);

        $txtPeriode = 'TAHUN: '.$tahun;
        if ($bulan !== '' && isset(self::LIST_BULAN_KODE[$bulan])) {
            $txtPeriode .= ' | BULAN: '.strtoupper(self::LIST_BULAN_KODE[$bulan]);
        }

        $suffixBulan = ($bulan !== '') ? '_Bulan_'.$bulan : '';
        $filename = 'Laporan_Profesi_Nakes_'.$tahun.$suffixBulan.'.xls';

        return response()->streamDownload(function () use ($data, $txtPeriode) {
            echo view('datakita.overview.mppd-profesi-pertahun-export', array_merge($data, [
                'txtPeriode' => $txtPeriode,
            ]))->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function profesiPerKecamatan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);
        $bulan = (string) $request->query('bulan', '');
        $kecamatan = (string) $request->query('kecamatan', '');

        $listKecamatan = DB::table('mppdig_faskes')
            ->selectRaw('DISTINCT kecamatan')
            ->where('kecamatan', '!=', '')
            ->orderBy('kecamatan')
            ->pluck('kecamatan');

        $query = DB::table('mppdig_permohonan_sip_semua as a')
            ->leftJoin('mppdig_faskes as b', 'a.tempat_praktik', '=', 'b.nama')
            ->selectRaw("COALESCE(b.kecamatan, 'Tidak Terdeteksi') as nama_kecamatan")
            ->selectRaw('a.profesi as jabatan')
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Batal%' THEN 1 ELSE 0 END) as stat_batal")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Verifikasi%' OR a.status LIKE '%Validasi%' THEN 1 ELSE 0 END) as stat_verif")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Terbit%' OR a.status LIKE '%Selesai%' OR a.status LIKE '%Cetak%' OR a.no_sip IS NOT NULL THEN 1 ELSE 0 END) as stat_terbit")
            ->selectRaw('COUNT(a.id) as total_per_row')
            ->whereRaw('YEAR(a.waktu_input) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(a.waktu_input) = ?', [$bulan]);
        }
        if ($kecamatan !== '') {
            $query->where('b.kecamatan', $kecamatan);
        }

        $rows = $query
            ->groupByRaw('COALESCE(b.kecamatan, \'Tidak Terdeteksi\'), a.profesi')
            ->orderByRaw('COALESCE(b.kecamatan, \'Tidak Terdeteksi\')')
            ->orderBy('a.profesi')
            ->get();

        $totBatal = 0;
        $totTolak = 0;
        $totVerif = 0;
        $totTerbit = 0;
        $grandTotal = 0;
        foreach ($rows as $row) {
            $totBatal += (int) $row->stat_batal;
            $totTolak += (int) $row->stat_tolak;
            $totVerif += (int) $row->stat_verif;
            $totTerbit += (int) $row->stat_terbit;
            $grandTotal += (int) $row->total_per_row;
        }

        return view('datakita.overview.mppd-profesi-per-kecamatan', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'kecamatan' => $kecamatan,
            'listKecamatan' => $listKecamatan,
            'listBulan' => self::LIST_BULAN_KODE,
            'rows' => $rows,
            'totBatal' => $totBatal,
            'totTolak' => $totTolak,
            'totVerif' => $totVerif,
            'totTerbit' => $totTerbit,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function exportProfesiPerKecamatan(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));
        $bulan = (string) $request->query('bulan', '');
        $kecamatan = (string) $request->query('kecamatan', '');

        $query = DB::table('mppdig_permohonan_sip_semua as a')
            ->leftJoin('mppdig_faskes as b', 'a.tempat_praktik', '=', 'b.nama')
            ->selectRaw("COALESCE(b.kecamatan, 'Tidak Terdeteksi') as nama_kecamatan")
            ->selectRaw('a.profesi as jabatan')
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Batal%' THEN 1 ELSE 0 END) as stat_batal")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Verifikasi%' OR a.status LIKE '%Validasi%' THEN 1 ELSE 0 END) as stat_verif")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Terbit%' OR a.status LIKE '%Selesai%' OR a.status LIKE '%Cetak%' OR a.no_sip IS NOT NULL THEN 1 ELSE 0 END) as stat_terbit")
            ->selectRaw('COUNT(a.id) as total_per_row')
            ->whereRaw('YEAR(a.waktu_input) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(a.waktu_input) = ?', [$bulan]);
        }
        if ($kecamatan !== '') {
            $query->where('b.kecamatan', $kecamatan);
        }

        $rows = $query
            ->groupByRaw('COALESCE(b.kecamatan, \'Tidak Terdeteksi\'), a.profesi')
            ->orderByRaw('COALESCE(b.kecamatan, \'Tidak Terdeteksi\')')
            ->orderBy('a.profesi')
            ->get();

        $totBatal = 0;
        $totTolak = 0;
        $totVerif = 0;
        $totTerbit = 0;
        $grandTotal = 0;
        foreach ($rows as $row) {
            $totBatal += (int) $row->stat_batal;
            $totTolak += (int) $row->stat_tolak;
            $totVerif += (int) $row->stat_verif;
            $totTerbit += (int) $row->stat_terbit;
            $grandTotal += (int) $row->total_per_row;
        }

        $listBulan = self::LIST_BULAN_KODE;
        $namaFileBln = $bulan !== '' ? '_'.$listBulan[$bulan] : '';
        $namaFileKec = $kecamatan !== '' ? '_'.str_replace(' ', '_', $kecamatan) : '';
        $filename = 'Izin_Nakes_Kecamatan_'.$tahun.$namaFileBln.$namaFileKec.'.xls';

        $txtPeriode = 'TAHUN: '.$tahun;
        if ($bulan !== '') {
            $txtPeriode .= ' | BULAN: '.strtoupper($listBulan[$bulan]);
        }
        if ($kecamatan !== '') {
            $txtPeriode .= ' | KECAMATAN: '.strtoupper($kecamatan);
        }

        return response()->streamDownload(function () use ($rows, $totBatal, $totTolak, $totVerif, $totTerbit, $grandTotal, $txtPeriode) {
            echo view('datakita.overview.mppd-profesi-per-kecamatan-export', [
                'rows' => $rows,
                'totBatal' => $totBatal,
                'totTolak' => $totTolak,
                'totVerif' => $totVerif,
                'totTerbit' => $totTerbit,
                'grandTotal' => $grandTotal,
                'txtPeriode' => $txtPeriode,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function permohonanPerBulan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);
        $bulan = (string) $request->query('bulan', '');

        $query = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('MONTH(waktu_input) as bln')
            ->selectRaw('profesi as jabatan')
            ->selectRaw("SUM(CASE WHEN status LIKE '%Batal%' THEN 1 ELSE 0 END) as stat_batal")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Verifikasi%' OR status LIKE '%Validasi%' THEN 1 ELSE 0 END) as stat_verif")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Terbit%' OR status LIKE '%Selesai%' OR status LIKE '%Cetak%' OR no_sip IS NOT NULL THEN 1 ELSE 0 END) as stat_terbit")
            ->selectRaw('COUNT(id) as total_per_row')
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(waktu_input) = ?', [$bulan]);
        }

        $rows = $query
            ->groupByRaw('MONTH(waktu_input), profesi')
            ->orderByRaw('MONTH(waktu_input)')
            ->orderBy('profesi')
            ->get();

        $totBatal = 0;
        $totTolak = 0;
        $totVerif = 0;
        $totTerbit = 0;
        $grandTotal = 0;
        foreach ($rows as $row) {
            $totBatal += (int) $row->stat_batal;
            $totTolak += (int) $row->stat_tolak;
            $totVerif += (int) $row->stat_verif;
            $totTerbit += (int) $row->stat_terbit;
            $grandTotal += (int) $row->total_per_row;
        }

        return view('datakita.overview.mppd-permohonan-per-bulan', [
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'listBulan' => self::LIST_BULAN_KODE,
            'rows' => $rows,
            'totBatal' => $totBatal,
            'totTolak' => $totTolak,
            'totVerif' => $totVerif,
            'totTerbit' => $totTerbit,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function exportPermohonanPerBulan(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));
        $bulan = (string) $request->query('bulan', '');

        $query = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('MONTH(waktu_input) as bln')
            ->selectRaw('profesi as jabatan')
            ->selectRaw("SUM(CASE WHEN status LIKE '%Batal%' THEN 1 ELSE 0 END) as stat_batal")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Verifikasi%' OR status LIKE '%Validasi%' THEN 1 ELSE 0 END) as stat_verif")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Terbit%' OR status LIKE '%Selesai%' OR status LIKE '%Cetak%' OR no_sip IS NOT NULL THEN 1 ELSE 0 END) as stat_terbit")
            ->selectRaw('COUNT(id) as total_per_row')
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(waktu_input) = ?', [$bulan]);
        }

        $rows = $query
            ->groupByRaw('MONTH(waktu_input), profesi')
            ->orderByRaw('MONTH(waktu_input)')
            ->orderBy('profesi')
            ->get();

        $totBatal = 0;
        $totTolak = 0;
        $totVerif = 0;
        $totTerbit = 0;
        $grandTotal = 0;
        foreach ($rows as $row) {
            $totBatal += (int) $row->stat_batal;
            $totTolak += (int) $row->stat_tolak;
            $totVerif += (int) $row->stat_verif;
            $totTerbit += (int) $row->stat_terbit;
            $grandTotal += (int) $row->total_per_row;
        }

        $listBulan = self::LIST_BULAN_KODE;
        $namaFileBln = $bulan !== '' ? '_'.$listBulan[$bulan] : '';
        $filename = 'Permohonan_Izin_Nakes_PerBulan_'.$tahun.$namaFileBln.'.xls';

        $txtPeriode = 'TAHUN: '.$tahun;
        if ($bulan !== '') {
            $txtPeriode .= ' | BULAN: '.strtoupper($listBulan[$bulan]);
        }

        return response()->streamDownload(function () use ($rows, $totBatal, $totTolak, $totVerif, $totTerbit, $grandTotal, $txtPeriode, $listBulan) {
            echo view('datakita.overview.mppd-permohonan-per-bulan-export', [
                'rows' => $rows,
                'totBatal' => $totBatal,
                'totTolak' => $totTolak,
                'totVerif' => $totVerif,
                'totTerbit' => $totTerbit,
                'grandTotal' => $grandTotal,
                'txtPeriode' => $txtPeriode,
                'listBulan' => $listBulan,
            ])->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function permohonanPerBulanHorizontalData($tahun)
    {
        $bulanAktif = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('DISTINCT MONTH(waktu_input) as bln')
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
            ->whereNotNull('waktu_input')
            ->orderBy('bln')
            ->pluck('bln')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        if (empty($bulanAktif)) {
            $bulanAktif = range(1, 12);
        }

        $dataMatrix = [];
        $totalPerBulan = [];
        foreach ($bulanAktif as $b) {
            $totalPerBulan[$b] = ['tolak' => 0, 'terbit' => 0];
        }

        $totTolakGlobal = 0;
        $totTerbitGlobal = 0;
        $grandTotalGlobal = 0;

        $rows = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('profesi as jabatan')
            ->selectRaw('MONTH(waktu_input) as bln')
            ->selectRaw("SUM(CASE WHEN status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
            ->selectRaw("SUM(CASE WHEN status LIKE '%Terbit%' OR status LIKE '%Selesai%' OR status LIKE '%Cetak%' OR no_sip IS NOT NULL THEN 1 ELSE 0 END) as stat_terbit")
            ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
            ->groupByRaw('profesi, MONTH(waktu_input)')
            ->orderBy('profesi')
            ->orderByRaw('MONTH(waktu_input)')
            ->get();

        foreach ($rows as $row) {
            $jabatan = !empty($row->jabatan) ? ucwords(strtolower(trim($row->jabatan))) : '(Tidak Disebutkan)';
            $bln = (int) $row->bln;
            $tolak = (int) $row->stat_tolak;
            $terbit = (int) $row->stat_terbit;

            if (!isset($dataMatrix[$jabatan])) {
                $dataMatrix[$jabatan] = [
                    'bulan' => [],
                    'total_tolak' => 0,
                    'total_terbit' => 0,
                    'grand_total' => 0,
                ];
                foreach ($bulanAktif as $b) {
                    $dataMatrix[$jabatan]['bulan'][$b] = ['tolak' => 0, 'terbit' => 0];
                }
            }

            if (in_array($bln, $bulanAktif, true)) {
                $dataMatrix[$jabatan]['bulan'][$bln]['tolak'] += $tolak;
                $dataMatrix[$jabatan]['bulan'][$bln]['terbit'] += $terbit;
                $totalPerBulan[$bln]['tolak'] += $tolak;
                $totalPerBulan[$bln]['terbit'] += $terbit;
            }

            $dataMatrix[$jabatan]['total_tolak'] += $tolak;
            $dataMatrix[$jabatan]['total_terbit'] += $terbit;
            $dataMatrix[$jabatan]['grand_total'] += ($tolak + $terbit);

            $totTolakGlobal += $tolak;
            $totTerbitGlobal += $terbit;
            $grandTotalGlobal += ($tolak + $terbit);
        }

        return [
            'bulanAktif' => $bulanAktif,
            'dataMatrix' => $dataMatrix,
            'totalPerBulan' => $totalPerBulan,
            'totTolakGlobal' => $totTolakGlobal,
            'totTerbitGlobal' => $totTerbitGlobal,
            'grandTotalGlobal' => $grandTotalGlobal,
        ];
    }

    public function permohonanPerBulanHorizontal(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        $data = $this->permohonanPerBulanHorizontalData($tahun);

        return view('datakita.overview.mppd-permohonan-per-bulan-horizontal', array_merge([
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'listBulanNama' => self::LIST_BULAN_NAMA,
        ], $data));
    }

    public function exportPermohonanPerBulanHorizontal(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        $data = $this->permohonanPerBulanHorizontalData($tahun);

        $filename = 'Permohonan_Izin_Nakes_Horizontal_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($tahun, $data) {
            echo view('datakita.overview.mppd-permohonan-per-bulan-horizontal-export', array_merge([
                'tahun' => $tahun,
                'listBulanNama' => self::LIST_BULAN_NAMA,
            ], $data))->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function frekuensiPerIndividuData($tahun)
    {
        $rows = DB::table('mppdig_permohonan_sip_semua')
            ->selectRaw('jumlah_kali_mengajukan')
            ->selectRaw('COUNT(*) as jumlah_orang')
            ->selectRaw('SUM(stat_batal) as tot_batal')
            ->selectRaw('SUM(stat_tolak) as tot_tolak')
            ->selectRaw('SUM(stat_verif) as tot_verif')
            ->selectRaw('SUM(stat_terbit) as tot_terbit')
            ->fromSub(function ($sub) use ($tahun) {
                $sub->from('mppdig_permohonan_sip_semua')
                    ->selectRaw('nama_lengkap')
                    ->selectRaw('nomor_hp')
                    ->selectRaw('COUNT(*) as jumlah_kali_mengajukan')
                    ->selectRaw("SUM(CASE WHEN status LIKE '%Batal%' THEN 1 ELSE 0 END) as stat_batal")
                    ->selectRaw("SUM(CASE WHEN status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
                    ->selectRaw("SUM(CASE WHEN status LIKE '%Verifikasi%' OR status LIKE '%Validasi%' THEN 1 ELSE 0 END) as stat_verif")
                    ->selectRaw("SUM(CASE WHEN status LIKE '%Terbit%' OR status LIKE '%Selesai%' OR status LIKE '%Cetak%' THEN 1 ELSE 0 END) as stat_terbit")
                    ->whereRaw('YEAR(waktu_input) = ?', [$tahun])
                    ->groupByRaw('nama_lengkap, nomor_hp');
            }, 'per_individu')
            ->groupBy('jumlah_kali_mengajukan')
            ->orderBy('jumlah_kali_mengajukan')
            ->get();

        $totalOrangGlobal = 0;
        $totalAplikasiGlobal = 0;
        $footerBatal = 0;
        $footerTolak = 0;
        $footerVerif = 0;
        $footerTerbit = 0;

        foreach ($rows as $row) {
            $totalOrangGlobal += (int) $row->jumlah_orang;
            $rowTotalApp = (int) $row->tot_batal + (int) $row->tot_tolak + (int) $row->tot_verif + (int) $row->tot_terbit;
            $totalAplikasiGlobal += $rowTotalApp;
            $footerBatal += (int) $row->tot_batal;
            $footerTolak += (int) $row->tot_tolak;
            $footerVerif += (int) $row->tot_verif;
            $footerTerbit += (int) $row->tot_terbit;
        }

        return [
            'rows' => $rows,
            'totalOrangGlobal' => $totalOrangGlobal,
            'totalAplikasiGlobal' => $totalAplikasiGlobal,
            'footerBatal' => $footerBatal,
            'footerTolak' => $footerTolak,
            'footerVerif' => $footerVerif,
            'footerTerbit' => $footerTerbit,
        ];
    }

    public function frekuensiPerIndividu(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);

        $data = $this->frekuensiPerIndividuData($tahun);

        return view('datakita.overview.mppd-frekuensi-per-individu', array_merge([
            'listTahun' => $listTahun,
            'tahun' => $tahun,
        ], $data));
    }

    public function exportFrekuensiPerIndividu(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));

        $data = $this->frekuensiPerIndividuData($tahun);

        $filename = 'Laporan_Frekuensi_Individu_'.$tahun.'.xls';

        return response()->streamDownload(function () use ($tahun, $data) {
            echo view('datakita.overview.mppd-frekuensi-per-individu-export', array_merge([
                'tahun' => $tahun,
            ], $data))->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }

    private function faskesPerKecamatanData($tahun, $bulan, $kecamatan)
    {
        $query = DB::table('mppdig_permohonan_sip_semua as a')
            ->join('mppdig_faskes as b', 'a.tempat_praktik', '=', 'b.nama')
            ->selectRaw('b.kelurahan')
            ->selectRaw('b.kategori')
            ->selectRaw('COUNT(a.id) as total_per_row')
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Batal%' THEN 1 ELSE 0 END) as stat_batal")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Tolak%' THEN 1 ELSE 0 END) as stat_tolak")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Verifikasi%' OR a.status LIKE '%Validasi%' THEN 1 ELSE 0 END) as stat_verif")
            ->selectRaw("SUM(CASE WHEN a.status LIKE '%Terbit%' OR a.status LIKE '%Selesai%' OR a.status LIKE '%Cetak%' OR a.no_sip IS NOT NULL THEN 1 ELSE 0 END) as stat_terbit")
            ->whereRaw('YEAR(a.waktu_input) = ?', [$tahun]);

        if ($bulan !== '') {
            $query->whereRaw('MONTH(a.waktu_input) = ?', [$bulan]);
        }
        if ($kecamatan !== '') {
            $query->where('b.kecamatan', $kecamatan);
        }

        $rows = $query
            ->groupBy('b.kelurahan', 'b.kategori')
            ->orderBy('b.kelurahan')
            ->orderBy('b.kategori')
            ->get();

        $grandTotal = 0;
        $totBatal = 0;
        $totTolak = 0;
        $totVerif = 0;
        $totTerbit = 0;
        foreach ($rows as $row) {
            $grandTotal += (int) $row->total_per_row;
            $totBatal += (int) $row->stat_batal;
            $totTolak += (int) $row->stat_tolak;
            $totVerif += (int) $row->stat_verif;
            $totTerbit += (int) $row->stat_terbit;
        }

        return [
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'totBatal' => $totBatal,
            'totTolak' => $totTolak,
            'totVerif' => $totVerif,
            'totTerbit' => $totTerbit,
        ];
    }

    public function faskesPerKecamatan(Request $request)
    {
        $listTahun = $this->listTahun();
        $tahun = $this->tahunDipilih($request, $listTahun);
        $bulan = (string) $request->query('bulan', '');
        $kecamatan = (string) $request->query('kecamatan', '');

        $listKecamatan = DB::table('mppdig_faskes')
            ->selectRaw('DISTINCT kecamatan')
            ->where('kecamatan', '!=', '')
            ->orderBy('kecamatan')
            ->pluck('kecamatan');

        $data = $this->faskesPerKecamatanData($tahun, $bulan, $kecamatan);

        $judulPeriode = 'Tahun '.$tahun;
        if ($bulan !== '' && isset(self::LIST_BULAN_KODE[$bulan])) {
            $judulPeriode .= ' Bulan '.self::LIST_BULAN_KODE[$bulan];
        }

        return view('datakita.overview.mppd-faskes-per-kecamatan', array_merge([
            'listTahun' => $listTahun,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'kecamatan' => $kecamatan,
            'listKecamatan' => $listKecamatan,
            'listBulan' => self::LIST_BULAN_KODE,
            'judulPeriode' => $judulPeriode,
        ], $data));
    }

    public function exportFaskesPerKecamatan(Request $request)
    {
        $tahun = $request->query('tahun', (int) date('Y'));
        $bulan = (string) $request->query('bulan', '');
        $kecamatan = (string) $request->query('kecamatan', '');

        $data = $this->faskesPerKecamatanData($tahun, $bulan, $kecamatan);

        $namaFileKec = $kecamatan !== '' ? '_'.str_replace(' ', '_', $kecamatan) : '';
        $filename = 'Sebaran_SIP_Kelurahan'.$namaFileKec.'_'.$tahun.'.xls';

        $txtPeriode = 'TAHUN: '.$tahun;
        if ($bulan !== '' && isset(self::LIST_BULAN_KODE[$bulan])) {
            $txtPeriode .= ' | BULAN: '.strtoupper(self::LIST_BULAN_KODE[$bulan]);
        }
        $txtLokasi = $kecamatan !== '' ? 'KECAMATAN: '.strtoupper($kecamatan) : 'SEMUA KECAMATAN';

        return response()->streamDownload(function () use ($data, $txtPeriode, $txtLokasi) {
            echo view('datakita.overview.mppd-faskes-per-kecamatan-export', array_merge($data, [
                'txtPeriode' => $txtPeriode,
                'txtLokasi' => $txtLokasi,
            ]))->render();
        }, $filename, ['Content-Type' => 'application/vnd-ms-excel']);
    }
}
