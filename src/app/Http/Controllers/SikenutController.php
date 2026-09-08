<?php

namespace App\Http\Controllers;

use App\Models\Sikenut;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SikenutController extends Controller
{
    private function canAdd(User $user): bool
    {
        return $user->role == 1 || $user->is_bpp == 1;
    }

    private function canManage(User $user, int $createdBy): bool
    {
        return $user->role == 1 || ($user->is_bpp == 1 && $createdBy === $user->id);
    }

    public function index()
    {
        return view('sikenut.index', [
            'canAdd' => $this->canAdd(Auth::user()),
        ]);
    }

    public function disposisi()
    {
        $users = User::where('is_aktif', 1)->orderBy('nama')->get(['id', 'nama']);

        return response()->json(['status' => 'success', 'data' => $users]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $draw = (int) $request->input('draw', 0);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));
        $filterBidang = trim((string) $request->input('filter_bidang', ''));

        $base = Sikenut::query()
            ->leftJoin('users as u', 'sikenut.disposisi', '=', 'u.id')
            ->leftJoin('users as pembuat', 'sikenut.created_by', '=', 'pembuat.id');

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('sikenut.nama_kegiatan', 'like', $like)
                    ->orWhere('sikenut.surat_tugas', 'like', $like)
                    ->orWhere('sikenut.tanggal_surat', 'like', $like)
                    ->orWhere('u.nama', 'like', $like)
                    ->orWhere('sikenut.tanggal_acara', 'like', $like)
                    ->orWhere('sikenut.bidang', 'like', $like)
                    ->orWhere('sikenut.tahun', 'like', $like)
                    ->orWhere('pembuat.nama', 'like', $like)
                    ->orWhere('sikenut.anggaran_bidang', 'like', $like);
            });
        }

        if ($filterBidang !== '') {
            $base->where('sikenut.bidang', $filterBidang);
        }

        $grouped = fn ($q) => $q->groupBy('sikenut.nama_kegiatan', 'sikenut.surat_tugas');

        $recordsTotal = $grouped(Sikenut::query()->selectRaw('MAX(id) as id'))->get()->count();
        $recordsFiltered = $grouped((clone $base)->selectRaw('MAX(sikenut.id) as id'))->get()->count();

        $rows = $grouped((clone $base)->selectRaw("
                MAX(sikenut.id) as id,
                sikenut.nama_kegiatan,
                sikenut.surat_tugas,
                MAX(sikenut.tanggal_surat) as tanggal_surat,
                MAX(sikenut.tanggal_acara) as tanggal_acara,
                MAX(sikenut.tipe_anggaran) as tipe_anggaran,
                MAX(sikenut.anggaran_bulan) as anggaran_bulan,
                MAX(sikenut.tahun) as tahun,
                MAX(sikenut.anggaran_bidang) as anggaran_bidang,
                MAX(sikenut.created_by) as created_by,
                GROUP_CONCAT(CONCAT(u.nama, ' (', sikenut.bidang, ')') SEPARATOR '||') as disposisi_bidang_list,
                MAX(pembuat.nama) as nama_pembuat
            "))
            ->orderByDesc(DB::raw('MAX(sikenut.created_at)'))
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        $nomor = $start + 1;
        foreach ($rows as $row) {
            $aksi = '';
            if ($this->canManage($user, (int) $row->created_by)) {
                $aksi .= '<button class="btn btn-warning btn-sm btn-edit me-1" data-id="'.$row->id.'">Edit</button>';
                $aksi .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'.$row->id.'">Hapus</button>';
            }

            $htmlList = '<ul class="mb-0 ps-3">';
            if ($row->disposisi_bidang_list) {
                foreach (explode('||', $row->disposisi_bidang_list) as $item) {
                    $htmlList .= '<li>'.e($item).'</li>';
                }
            }
            $htmlList .= '</ul>';

            $data[] = [
                'no' => $nomor++,
                'nama_kegiatan' => $row->nama_kegiatan,
                'surat_tugas' => $row->surat_tugas,
                'tanggal_surat' => $row->tanggal_surat,
                'disposisi' => $htmlList,
                'tanggal_acara' => $row->tanggal_acara,
                'anggaran_bulan' => $row->anggaran_bulan,
                'tipe_anggaran' => $row->tipe_anggaran,
                'anggaran_bidang' => $row->anggaran_bidang,
                'tahun' => $row->tahun,
                'pembuat' => $row->nama_pembuat,
                'aksi' => $aksi,
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function summary(Request $request)
    {
        $filterBidang = trim((string) $request->input('filter_bidang', ''));

        $query = Sikenut::query()
            ->select('tipe_anggaran')
            ->when($filterBidang !== '', fn ($q) => $q->where('bidang', $filterBidang))
            ->groupBy('nama_kegiatan', 'surat_tugas', 'tipe_anggaran');

        $summary = ['SPPD' => 0, 'UT 50000' => 0, 'UT 75000' => 0];
        foreach ($query->pluck('tipe_anggaran') as $tipe) {
            $key = strtoupper(trim((string) $tipe));
            if (array_key_exists($key, $summary)) {
                $summary[$key]++;
            }
        }

        return response()->json(['status' => 'success', 'data' => $summary]);
    }

    public function getSingleGroup(Request $request)
    {
        $user = Auth::user();
        $id = (int) $request->input('id', 0);

        $keys = Sikenut::find($id, ['id', 'nama_kegiatan', 'surat_tugas', 'created_by']);
        if (! $keys) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        if (! $this->canManage($user, (int) $keys->created_by)) {
            return response()->json(['status' => 'error', 'message' => 'Otorisasi gagal. Anda tidak dapat mengakses form edit untuk data ini.']);
        }

        $rows = Sikenut::where('nama_kegiatan', $keys->nama_kegiatan)
            ->where('surat_tugas', $keys->surat_tugas)
            ->get();

        $parent = $rows->first();
        $children = $rows->map(fn ($r) => ['disposisi' => $r->disposisi, 'bidang' => $r->bidang])->values();

        return response()->json([
            'status' => 'success',
            'parent_data' => [
                'nama_kegiatan' => $parent->nama_kegiatan,
                'surat_tugas' => $parent->surat_tugas,
                'tanggal_surat' => $parent->tanggal_surat?->format('Y-m-d'),
                'tanggal_acara' => $parent->tanggal_acara?->format('Y-m-d'),
                'tipe_anggaran' => $parent->tipe_anggaran,
                'anggaran_bulan' => $parent->anggaran_bulan,
                'tahun' => $parent->tahun,
                'anggaran_bidang' => $parent->anggaran_bidang,
            ],
            'child_data' => $children,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $this->canAdd($user)) {
            return response()->json(['status' => 'error', 'message' => 'Otorisasi gagal. Hanya Admin dan BPP yang dapat menambah data.']);
        }

        $dataUtama = $request->validate([
            'data_utama.nama_kegiatan' => ['required', 'string', 'max:255'],
            'data_utama.surat_tugas' => ['required', 'string', 'max:100'],
            'data_utama.tanggal_surat' => ['required', 'date'],
            'data_utama.tanggal_acara' => ['required', 'date'],
            'data_utama.tipe_anggaran' => ['required', 'string', 'max:50'],
            'data_utama.anggaran_bulan' => ['nullable', 'string', 'max:20'],
            'data_utama.tahun' => ['required', 'integer'],
            'data_utama.anggaran_bidang' => ['required', 'string', 'max:100'],
        ])['data_utama'];

        $dataDisposisi = $request->validate([
            'data_disposisi' => ['required', 'array', 'min:1'],
            'data_disposisi.*.disposisi_id' => ['required', 'integer', 'exists:users,id'],
            'data_disposisi.*.bidang' => ['required', 'string', 'max:100'],
        ])['data_disposisi'];

        foreach ($dataDisposisi as $item) {
            Sikenut::create([
                ...$dataUtama,
                'disposisi' => (int) $item['disposisi_id'],
                'bidang' => $item['bidang'],
                'created_by' => $user->id,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function updateMulti(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'original_nama_kegiatan' => ['required', 'string'],
            'original_surat_tugas' => ['required', 'string'],
            'data_utama.nama_kegiatan' => ['required', 'string', 'max:255'],
            'data_utama.surat_tugas' => ['required', 'string', 'max:100'],
            'data_utama.tanggal_surat' => ['required', 'date'],
            'data_utama.tanggal_acara' => ['required', 'date'],
            'data_utama.tipe_anggaran' => ['required', 'string', 'max:50'],
            'data_utama.anggaran_bulan' => ['nullable', 'string', 'max:20'],
            'data_utama.tahun' => ['required', 'integer'],
            'data_utama.anggaran_bidang' => ['required', 'string', 'max:100'],
            'data_disposisi' => ['required', 'array', 'min:1'],
            'data_disposisi.*.disposisi_id' => ['required', 'integer', 'exists:users,id'],
            'data_disposisi.*.bidang' => ['required', 'string', 'max:100'],
        ]);

        $dataUtama = $validated['data_utama'];
        $dataDisposisi = $validated['data_disposisi'];

        $original = Sikenut::where('nama_kegiatan', $validated['original_nama_kegiatan'])
            ->where('surat_tugas', $validated['original_surat_tugas'])
            ->first(['created_at', 'created_by']);

        if (! $original) {
            return response()->json(['status' => 'error', 'message' => 'Data asal tidak ditemukan.']);
        }

        if (! $this->canManage($user, (int) $original->created_by)) {
            return response()->json(['status' => 'error', 'message' => 'Otorisasi gagal. Anda tidak dapat menyimpan perubahan data ini.']);
        }

        DB::transaction(function () use ($validated, $dataUtama, $dataDisposisi, $original) {
            Sikenut::where('nama_kegiatan', $validated['original_nama_kegiatan'])
                ->where('surat_tugas', $validated['original_surat_tugas'])
                ->delete();

            foreach ($dataDisposisi as $item) {
                $record = new Sikenut([
                    ...$dataUtama,
                    'disposisi' => (int) $item['disposisi_id'],
                    'bidang' => $item['bidang'],
                    'created_by' => $original->created_by,
                ]);
                $record->created_at = $original->created_at;
                $record->save();
            }
        });

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        $id = (int) $request->input('id', 0);

        $keys = Sikenut::find($id, ['id', 'nama_kegiatan', 'surat_tugas', 'created_by']);
        if (! $keys) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        if (! $this->canManage($user, (int) $keys->created_by)) {
            return response()->json(['status' => 'error', 'message' => 'Otorisasi gagal. Anda tidak dapat menghapus data ini.']);
        }

        Sikenut::where('nama_kegiatan', $keys->nama_kegiatan)
            ->where('surat_tugas', $keys->surat_tugas)
            ->delete();

        return response()->json(['status' => 'success']);
    }

    public function import(Request $request)
    {
        $user = Auth::user();
        if (! $this->canAdd($user)) {
            return response()->json(['status' => 'error', 'message' => 'Otorisasi gagal. Hanya Admin dan BPP yang dapat import data.']);
        }

        $request->validate([
            'file_excel' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $users = User::pluck('id', 'nama')
            ->mapWithKeys(fn ($id, $nama) => [strtolower(trim((string) $nama)) => $id]);

        try {
            $spreadsheet = IOFactory::load($request->file('file_excel')->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestDataRow();

            DB::transaction(function () use ($worksheet, $highestRow, $users, $user) {
                for ($row = 2; $row <= $highestRow; $row++) {
                    $nama = trim((string) $worksheet->getCell('A'.$row)->getValue());
                    if ($nama === '') {
                        continue;
                    }

                    $cellTglSurat = $worksheet->getCell('C'.$row);
                    $tglSurat = Date::isDateTime($cellTglSurat)
                        ? date('Y-m-d', Date::excelToTimestamp($cellTglSurat->getValue()))
                        : trim((string) $cellTglSurat->getValue());

                    $cellTglAcara = $worksheet->getCell('D'.$row);
                    $tglAcara = Date::isDateTime($cellTglAcara)
                        ? date('Y-m-d', Date::excelToTimestamp($cellTglAcara->getValue()))
                        : trim((string) $cellTglAcara->getValue());

                    $namaDispo = strtolower(trim((string) $worksheet->getCell('H'.$row)->getValue()));

                    Sikenut::create([
                        'nama_kegiatan' => $nama,
                        'surat_tugas' => trim((string) $worksheet->getCell('B'.$row)->getValue()),
                        'tanggal_surat' => $tglSurat ?: null,
                        'tanggal_acara' => $tglAcara ?: null,
                        'tipe_anggaran' => trim((string) $worksheet->getCell('E'.$row)->getValue()),
                        'anggaran_bulan' => trim((string) $worksheet->getCell('F'.$row)->getValue()),
                        'tahun' => (int) $worksheet->getCell('G'.$row)->getValue(),
                        'disposisi' => $users[$namaDispo] ?? 0,
                        'bidang' => trim((string) $worksheet->getCell('I'.$row)->getValue()),
                        'anggaran_bidang' => trim((string) $worksheet->getCell('J'.$row)->getValue()),
                        'created_by' => $user->id,
                    ]);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Data berhasil diimport!']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
