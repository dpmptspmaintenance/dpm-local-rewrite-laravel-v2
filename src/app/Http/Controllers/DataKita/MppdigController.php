<?php

namespace App\Http\Controllers\DataKita;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MppdigController extends Controller
{
    // ==========================================
    // PERMOHONAN MPP DIGITAL (server-side DataTable)
    // ==========================================

    private const SORTABLE = [
        'no_reg' => 'no_reg',
        'nama' => 'nama',
        'profesi' => 'profesi',
        'tempat_praktik' => 'tempat_praktik',
        'status_permohonan' => 'status_permohonan',
        'tgl_permohonan' => 'tgl_permohonan',
    ];

    public function permohonanIndex(Request $request)
    {
        $profesi = DB::table('mppdig_permohonan')
            ->select('profesi')
            ->distinct()
            ->whereNotNull('profesi')
            ->orderBy('profesi')
            ->pluck('profesi');
        $statuses = DB::table('mppdig_permohonan')
            ->select('status_permohonan')
            ->distinct()
            ->whereNotNull('status_permohonan')
            ->orderBy('status_permohonan')
            ->pluck('status_permohonan');

        return view('datakita.mppdig-permohonan.index', compact('profesi', 'statuses'));
    }

    private function permohonanQuery(Request $request)
    {
        $query = DB::table('mppdig_permohonan');

        $status = (string) $request->input('status_permohonan', '');
        if ($status !== '') {
            $query->where('status_permohonan', $status);
        }

        $start = (string) $request->input('start_date', '');
        $end = (string) $request->input('end_date', '');
        if ($start !== '' && $end !== '') {
            $query->whereBetween('tgl_permohonan', [$start, $end]);
        }

        $profesi = $request->input('profesi', []);
        if (is_string($profesi)) {
            $profesi = [$profesi];
        }
        $profesi = array_values(array_filter(array_map('strval', (array) $profesi)));
        if (count($profesi) > 0) {
            $query->whereIn('profesi', $profesi);
        }

        $search = trim((string) $request->input('search.value', $request->input('searchValue', '')));
        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('nama', 'like', $like)
                    ->orWhere('no_reg', 'like', $like)
                    ->orWhere('nik', 'like', $like);
            });
        }

        return $query;
    }

    public function permohonanAjax(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $orderName = (string) $request->input('order.0.name', '');
        $orderDir = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = self::SORTABLE[$orderName] ?? 'no_reg';

        $recordsTotal = DB::table('mppdig_permohonan')->count();
        $recordsFiltered = $this->permohonanQuery($request)->count();

        $rows = $this->permohonanQuery($request)
            ->select('nik', 'no_reg', 'tgl_permohonan', 'nama', 'profesi', 'status_permohonan', 'tempat_praktik')
            ->orderBy($orderColumn, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function permohonanExport(Request $request)
    {
        $rows = $this->permohonanQuery($request)
            ->select('nik', 'no_reg', 'tgl_permohonan', 'nama', 'profesi', 'status_permohonan', 'tempat_praktik')
            ->orderByDesc('no_reg')
            ->get();

        $start = (string) $request->query('start_date', '');
        $end = (string) $request->query('end_date', '');

        return response()->streamDownload(function () use ($rows, $start, $end) {
            echo view('datakita.mppdig-permohonan.export', compact('rows', 'start', 'end'))->render();
        }, 'Data Permohonan MPP Digital.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // ==========================================
    // PEMOHON MPP DIGITAL
    // ==========================================

    public function pemohonIndex(Request $request)
    {
        $nama = (string) $request->query('nama', '');
        $gender = (string) $request->query('gender', '');
        $alamat = (string) $request->query('alamat', '');

        $query = DB::table('mppdig_pemohon');
        if ($nama !== '') {
            $query->where('nama', 'like', "%{$nama}%");
        }
        if ($gender !== '') {
            $query->where('gender', $gender);
        }
        if ($alamat !== '') {
            $query->where('alamat', 'like', "%{$alamat}%");
        }
        $rows = $query->select('nama', 'telp', 'gender', 'alamat', 'email')->get();

        return view('datakita.mppdig-pemohon.index', compact('nama', 'gender', 'alamat', 'rows'));
    }

    public function pemohonExport(Request $request)
    {
        $nama = (string) $request->query('nama', '');
        $gender = (string) $request->query('gender', '');
        $alamat = (string) $request->query('alamat', '');

        $query = DB::table('mppdig_pemohon');
        if ($nama !== '') {
            $query->where('nama', 'like', "%{$nama}%");
        }
        if ($gender !== '') {
            $query->where('gender', $gender);
        }
        if ($alamat !== '') {
            $query->where('alamat', 'like', "%{$alamat}%");
        }
        $rows = $query->select('nama', 'telp', 'gender', 'alamat', 'email')->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.mppdig-pemohon.export', ['rows' => $rows])->render();
        }, 'Data Pemohon MPP Digital.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // ==========================================
    // KENDALA MPP DIGITAL
    // ==========================================

    public function kendalaIndex(Request $request)
    {
        $nama = (string) $request->query('nama', '');
        $kendala = (string) $request->query('kendala', '');
        $status = (string) $request->query('status', '');

        $query = DB::table('mppdig_kendala');
        if ($nama !== '') {
            $query->where('nama', 'like', "%{$nama}%");
        }
        if ($kendala !== '') {
            $query->where('kendala', 'like', "%{$kendala}%");
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        $rows = $query->select('nama', 'email', 'kendala', 'status', 'keterangan')->get();

        $statuses = DB::table('mppdig_kendala')
            ->select('status')
            ->distinct()
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return view('datakita.mppdig-kendala.index', compact('nama', 'kendala', 'status', 'rows', 'statuses'));
    }

    public function kendalaExport(Request $request)
    {
        $nama = (string) $request->query('nama', '');
        $kendala = (string) $request->query('kendala', '');
        $status = (string) $request->query('status', '');

        $query = DB::table('mppdig_kendala');
        if ($nama !== '') {
            $query->where('nama', 'like', "%{$nama}%");
        }
        if ($kendala !== '') {
            $query->where('kendala', 'like', "%{$kendala}%");
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        $rows = $query->select('nama', 'email', 'kendala', 'status', 'keterangan')->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.mppdig-kendala.export', ['rows' => $rows])->render();
        }, 'Data Kendala MPP Digital.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // ==========================================
    // FASKES MPP DIGITAL
    // ==========================================

    public function faskesIndex(Request $request)
    {
        $nama = (string) $request->query('nama', '');
        $alamat = (string) $request->query('alamat', '');
        $kategori = (string) $request->query('kategori', '');

        $query = DB::table('mppdig_faskes');
        if ($nama !== '') {
            $query->where('nama', 'like', "%{$nama}%");
        }
        if ($alamat !== '') {
            $query->where('alamat', 'like', "%{$alamat}%");
        }
        if ($kategori !== '') {
            $query->where('kategori', 'like', "%{$kategori}%");
        }
        $rows = $query->select('nama', 'email', 'alamat', 'kategori')->get();

        $kategoris = DB::table('mppdig_faskes')
            ->select('kategori')
            ->distinct()
            ->whereNotNull('kategori')
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('datakita.mppdig-faskes.index', compact('nama', 'alamat', 'kategori', 'rows', 'kategoris'));
    }

    public function faskesExport(Request $request)
    {
        $nama = (string) $request->query('nama', '');
        $alamat = (string) $request->query('alamat', '');
        $kategori = (string) $request->query('kategori', '');

        $query = DB::table('mppdig_faskes');
        if ($nama !== '') {
            $query->where('nama', 'like', "%{$nama}%");
        }
        if ($alamat !== '') {
            $query->where('alamat', 'like', "%{$alamat}%");
        }
        if ($kategori !== '') {
            $query->where('kategori', 'like', "%{$kategori}%");
        }
        $rows = $query->select('nama', 'email', 'alamat', 'kategori')->get();

        return response()->streamDownload(function () use ($rows) {
            echo view('datakita.mppdig-faskes.export', ['rows' => $rows])->render();
        }, 'Data Faskes MPP Digital.xls', ['Content-Type' => 'application/vnd-ms-excel']);
    }

    // ==========================================
    // GRAFIK MPP DIGITAL
    // ==========================================

    public function grafikIndex()
    {
        $faskesData = DB::table('mppdig_faskes')
            ->select('kategori')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kategori')
            ->get()
            ->map(fn ($r) => ['kategori' => $r->kategori, 'total' => (int) $r->total]);

        $statusData = DB::table('mppdig_permohonan')
            ->select('status_permohonan')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status_permohonan')
            ->get()
            ->map(fn ($r) => ['status_permohonan' => $r->status_permohonan, 'total' => (int) $r->total]);

        $profesiData = DB::table('mppdig_permohonan')
            ->select('profesi')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('profesi')
            ->get()
            ->map(fn ($r) => ['profesi' => $r->profesi, 'total' => (int) $r->total]);

        return view('datakita.mppdig-grafik.index', compact('faskesData', 'statusData', 'profesiData'));
    }

    // ==========================================
    // TRACKING MPP DIGITAL
    // ==========================================

    public function trackingIndex(Request $request)
    {
        $noReg = (string) $request->query('no_reg', '');

        $rows = collect();
        if ($noReg !== '') {
            $rows = DB::table('mppdig_permohonan')
                ->where('no_reg', $noReg)
                ->get();
        }

        return view('datakita.mppdig-tracking.index', compact('noReg', 'rows'));
    }
}
