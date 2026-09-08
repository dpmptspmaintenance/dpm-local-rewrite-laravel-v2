<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SimbgController extends Controller
{
    // ==========================================
    // MONITORING SIMBG
    // ==========================================

    public function monitoringIndex(Request $request)
    {
        $filters = [
            'tahun' => (string) $request->query('tahun', ''),
            'bulan' => (string) $request->query('bulan', ''),
            'input_antara' => (string) $request->query('input_antara', ''),
            'jenis_registrasi' => (string) $request->query('jenis_registrasi', ''),
            'jenis_konsultasi' => (string) $request->query('jenis_konsultasi', ''),
            'fungsi_bg' => (string) $request->query('fungsi_bg', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $rows = collect();
        if ($request->hasAny(['tahun', 'bulan', 'input_antara', 'jenis_registrasi', 'jenis_konsultasi', 'fungsi_bg', 'status'])) {
            $rows = $this->monitoringRows($filters);
        }

        $jenisRegist = DB::table('simbg_master_jenis_regist')
            ->where('is_aktif', 1)
            ->orderBy('jenis_registrasi')
            ->get();
        $jenisKonsultasi = DB::table('simbg_master_jenis_konsultasi')
            ->where('is_aktif', 1)
            ->orderBy('jenis_konsultasi')
            ->get();
        $fungsiBg = DB::table('simbg_master_fungsi_bangunan_gedung')
            ->where('is_aktif', 1)
            ->orderBy('fungsi_bg')
            ->get();
        $statuses = DB::table('simbg_monitoring')
            ->select('status')
            ->distinct()
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return view('datakita.monitoring-simbg.index', compact('filters', 'rows', 'jenisRegist', 'jenisKonsultasi', 'fungsiBg', 'statuses'));
    }

    private function monitoringRows(array $f)
    {
        $query = DB::table('simbg_monitoring as m')
            ->leftJoin('simbg_penyerahan_dokumen_pbg as p', 'm.no_registrasi', '=', 'p.no_registrasi')
            ->select(
                'p.tgl_pengambilan_sk',
                'p.nama_pengambil_sk',
                'm.no_registrasi',
                'm.nama_pemilik',
                'm.alamat',
                'm.fungsi_bangunan',
                'm.luas_bangunan',
                'p.no_dokumen_pbg',
                'p.tgl_dokumen_pbg'
            );

        if ($f['input_antara'] !== '') {
            $like = '%'.$f['input_antara'].'%';
            $query->where(function ($q) use ($like) {
                $q->where('m.nama_pemilik', 'like', $like)
                    ->orWhere('m.alamat', 'like', $like);
            });
        } else {
            $usePbgDate = $f['status'] === 'SK PBG Terbit';
            $dateCol = $usePbgDate ? 'p.tgl_dokumen_pbg' : 'm.tgl_registrasi';

            if ($f['tahun'] !== '') {
                $query->whereRaw('YEAR('.$dateCol.') = ?', [(int) $f['tahun']]);
            }
            if ($f['bulan'] !== '') {
                $query->whereRaw('MONTH('.$dateCol.') = ?', [(int) $f['bulan']]);
            }
            if ($f['jenis_registrasi'] !== '') {
                $query->where('m.jenis_registrasi', $f['jenis_registrasi']);
            }
            if ($f['jenis_konsultasi'] !== '') {
                $query->where('m.tipe_konsultasi_1', $f['jenis_konsultasi']);
            }
            if ($f['fungsi_bg'] !== '') {
                $query->where('m.fungsi_bangunan', $f['fungsi_bg']);
            }
            if ($f['status'] !== '') {
                $query->where('m.status', $f['status']);
            }
        }

        return $query->get();
    }

    public function monitoringExport(Request $request)
    {
        $filters = [
            'tahun' => (string) $request->query('tahun', ''),
            'bulan' => (string) $request->query('bulan', ''),
            'input_antara' => (string) $request->query('input_antara', ''),
            'jenis_registrasi' => (string) $request->query('jenis_registrasi', ''),
            'jenis_konsultasi' => (string) $request->query('jenis_konsultasi', ''),
            'fungsi_bg' => (string) $request->query('fungsi_bg', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $rows = $this->monitoringRows($filters);

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.monitoring-simbg.export', ['rows' => $rows])->render();
        }, 'Data Monitoring SIMBG.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    public function monitoringTambahUbah()
    {
        return view('datakita.monitoring-simbg.tambah-ubah');
    }

    public function monitoringGetSelectData(Request $request)
    {
        $search = (string) $request->query('search', '');

        $rows = DB::table('simbg_monitoring')
            ->select('no_registrasi', 'nama_pemilik')
            ->where('no_registrasi', 'like', "%{$search}%")
            ->orWhere('nama_pemilik', 'like', "%{$search}%")
            ->limit(100)
            ->get();

        $data = $rows->map(fn ($r) => [
            'id' => $r->no_registrasi,
            'text' => $r->no_registrasi.' - '.$r->nama_pemilik,
        ])->all();

        return response()->json($data);
    }

    public function monitoringGetData(Request $request)
    {
        $noReg = (string) $request->input('no_registrasi', '');

        $monitoring = DB::table('simbg_monitoring')
            ->where('no_registrasi', $noReg)
            ->first();
        $penyerahan = DB::table('simbg_penyerahan_dokumen_pbg')
            ->where('no_registrasi', $noReg)
            ->first();
        $tambahan = DB::table('simbg_data_tambahan')
            ->where('no_registrasi', $noReg)
            ->first();

        $base = $monitoring ? (array) $monitoring : [];
        $exists = $penyerahan !== null || $tambahan !== null;

        $response = array_merge($base, $penyerahan ? (array) $penyerahan : [], $tambahan ? (array) $tambahan : [], ['exists' => $exists]);

        return response()->json($response);
    }

    public function monitoringProsesTambahUbah(Request $request)
    {
        $action = (string) $request->input('action', 'tambah');
        $noReg = (string) $request->input('no_registrasi', '');
        $jenisPermohonan = (string) $request->input('jenis_permohonan', '');
        $tglRegistrasi = (string) $request->input('tgl_registrasi', '');
        $noDokumenPbg = (string) $request->input('no_dokumen_pbg', '');
        $tglDokumenPbg = (string) $request->input('tgl_dokumen_pbg', '');
        $namaPemilik = (string) $request->input('nama_pemilik', '');
        $dokumen = (string) $request->input('dokumen', '');
        $status = (string) $request->input('status', '');
        $tglAmbilData = (string) $request->input('tgl_ambil_data', '');
        $jamAmbilData = (string) $request->input('jam_ambil_data', '');
        $tglPengambilanSk = (string) $request->input('tgl_pengambilan_sk', '');
        $namaPengambilSk = (string) $request->input('nama_pengambil_sk', '');
        $nik = (string) $request->input('nik', '');
        $npwp = (string) $request->input('npwp', '');
        $hakAtasTanah = (string) $request->input('hak_atas_tanah', '');

        if ($noReg === '' || $jenisPermohonan === '' || $tglRegistrasi === '' || $namaPemilik === '') {
            return response()->json(['status' => 'error', 'message' => 'Semua field harus diisi']);
        }

        DB::beginTransaction();
        try {
            $penyerahanData = [
                'jenis_permohonan' => $jenisPermohonan,
                'tgl_registrasi' => $tglRegistrasi ?: null,
                'no_dokumen_pbg' => $noDokumenPbg,
                'tgl_dokumen_pbg' => $tglDokumenPbg ?: null,
                'nama_pemilik' => $namaPemilik,
                'dokumen' => $dokumen,
                'status' => $status,
                'tgl_ambil_data' => $tglAmbilData ?: null,
                'jam_ambil_data' => $jamAmbilData ?: null,
                'tgl_pengambilan_sk' => $tglPengambilanSk ?: null,
                'nama_pengambil_sk' => $namaPengambilSk,
            ];

            if ($action === 'tambah') {
                $penyerahanData['no_registrasi'] = $noReg;
                DB::table('simbg_penyerahan_dokumen_pbg')->insert($penyerahanData);
            } else {
                DB::table('simbg_penyerahan_dokumen_pbg')
                    ->where('no_registrasi', $noReg)
                    ->update($penyerahanData);
            }

            $tambahanData = [
                'nik' => $nik,
                'npwp' => $npwp,
                'hak_atas_tanah' => $hakAtasTanah,
            ];

            if ($action === 'tambah') {
                $tambahanData['no_registrasi'] = $noReg;
                DB::table('simbg_data_tambahan')->insert($tambahanData);
            } else {
                DB::table('simbg_data_tambahan')
                    ->where('no_registrasi', $noReg)
                    ->update($tambahanData);
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Data berhasil '.($action === 'tambah' ? 'ditambahkan' : 'diubah')]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memproses data: '.$e->getMessage()]);
        }
    }

    // ==========================================
    // REKAP PBG (simbg_penyerahan_dokumen_pbg)
    // ==========================================

    private const PBG_ALLOWED_FILTERS = ['tgl_registrasi', 'tgl_dokumen_pbg', 'tgl_pengambilan_sk'];

    public function rekapPbgIndex(Request $request)
    {
        $tahun = (string) $request->query('tahun', '');
        $bulan = (string) $request->query('bulan', '');
        $filterBerdasarkan = (string) $request->query('filter_berdasarkan', 'tgl_dokumen_pbg');

        if (! in_array($filterBerdasarkan, self::PBG_ALLOWED_FILTERS, true)) {
            $filterBerdasarkan = 'tgl_dokumen_pbg';
        }

        $rows = collect();
        if ($tahun !== '' || $bulan !== '') {
            $query = DB::table('simbg_penyerahan_dokumen_pbg')
                ->select(
                    'jenis_permohonan',
                    'no_registrasi',
                    'tgl_registrasi',
                    'no_dokumen_pbg',
                    'tgl_dokumen_pbg',
                    'nama_pemilik',
                    'dokumen',
                    'status',
                    'tgl_ambil_data',
                    'jam_ambil_data',
                    'tgl_pengambilan_sk',
                    'nama_pengambil_sk'
                );

            if ($tahun !== '') {
                $query->whereRaw('YEAR('.$filterBerdasarkan.') = ?', [(int) $tahun]);
            }
            if ($bulan !== '') {
                $query->whereRaw('MONTH('.$filterBerdasarkan.') = ?', [(int) $bulan]);
            }

            $rows = $query->get();
        }

        return view('datakita.rekap-pbg.index', compact('tahun', 'bulan', 'filterBerdasarkan', 'rows'));
    }

    public function rekapPbgExport(Request $request)
    {
        $tahun = (string) $request->query('tahun', '');
        $bulan = (string) $request->query('bulan', '');
        $filterBerdasarkan = (string) $request->query('filter_berdasarkan', 'tgl_dokumen_pbg');

        if (! in_array($filterBerdasarkan, self::PBG_ALLOWED_FILTERS, true)) {
            $filterBerdasarkan = 'tgl_dokumen_pbg';
        }

        $query = DB::table('simbg_penyerahan_dokumen_pbg')
            ->select(
                'jenis_permohonan',
                'no_registrasi',
                'tgl_registrasi',
                'no_dokumen_pbg',
                'tgl_dokumen_pbg',
                'nama_pemilik',
                'dokumen',
                'status',
                'tgl_ambil_data',
                'jam_ambil_data',
                'tgl_pengambilan_sk',
                'nama_pengambil_sk'
            );

        if ($tahun !== '') {
            $query->whereRaw('YEAR('.$filterBerdasarkan.') = ?', [(int) $tahun]);
        }
        if ($bulan !== '') {
            $query->whereRaw('MONTH('.$filterBerdasarkan.') = ?', [(int) $bulan]);
        }

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows, $filterBerdasarkan, $tahun, $bulan) {
            echo view('datakita.rekap-pbg.export', compact('rows', 'filterBerdasarkan', 'tahun', 'bulan'))->render();
        }, 'Data_Rekap_PBG_'.date('Y-m-d').'.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // ==========================================
    // VALIDASI PEMBAYARAN RETRIBUSI PBG
    // ==========================================

    public function validasiIndex(Request $request)
    {
        $noReg = (string) $request->query('no_registrasi', '');

        $rows = collect();
        if ($noReg !== '') {
            $rows = DB::table('datakita_validasi_pembayaran_retribusi_pbg')
                ->select('bulan', 'tanggal_validasi', 'no_registrasi', 'nama_pemilik', 'lokasi_bangunan', 'id_biling', 'tgl_bayar', 'nominal', 'petugas')
                ->where('no_registrasi', $noReg)
                ->get();
        }

        return view('datakita.validasi-pembayaran.index', compact('noReg', 'rows'));
    }

    public function validasiStore(Request $request)
    {
        $petugas = Auth::user()->nama ?? 'admin';

        DB::table('datakita_validasi_pembayaran_retribusi_pbg')->insert([
            'bulan' => (string) $request->input('bulan', ''),
            'tanggal_validasi' => $request->input('tanggal_validasi') ?: null,
            'no_registrasi' => (string) $request->input('no_registrasi', ''),
            'nama_pemilik' => (string) $request->input('nama_pemilik', ''),
            'lokasi_bangunan' => (string) $request->input('lokasi_bangunan', ''),
            'id_biling' => (string) $request->input('id_biling', ''),
            'tgl_bayar' => $request->input('tgl_bayar') ?: null,
            'nominal' => (float) $request->input('nominal', 0),
            'petugas' => $petugas,
        ]);

        return redirect()->route('datakita.validasi-pembayaran.index')->with('success', 'Data berhasil ditambahkan.');
    }

    public function validasiTambah()
    {
        return view('datakita.validasi-pembayaran.tambah');
    }

    // ==========================================
    // TRACKING SIMBG
    // ==========================================

    public function trackingIndex(Request $request)
    {
        $noReg = (string) $request->query('no_registrasi', '');

        $rows = collect();
        if ($noReg !== '') {
            $rows = DB::table('simbg_monitoring')
                ->where('no_registrasi', $noReg)
                ->get();
        }

        return view('datakita.simbg-tracking.index', compact('noReg', 'rows'));
    }
}
