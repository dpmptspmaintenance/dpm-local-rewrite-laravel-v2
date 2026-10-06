<?php

namespace App\Http\Controllers\Bangkit;

use App\Http\Controllers\Bangkit\Concerns\ChecksBangkitAccess;
use App\Http\Controllers\Controller;
use App\Models\Bangkit\Barang;
use App\Models\Bangkit\HistoryBarang;
use App\Models\Bangkit\MasterBahanBarang;
use App\Models\Bangkit\MasterJenisBarang;
use App\Models\Bangkit\MasterKeadaanBarang;
use App\Models\Bangkit\MasterLokasi;
use App\Models\Bangkit\PegawaiKekuatan;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    use ChecksBangkitAccess;

    public function cari()
    {
        $this->abortIfNotBangkitUser();

        return view('bangkit.barang.cari');
    }

    public function aksiCari(Request $request)
    {
        $this->abortIfNotBangkitUser();
        $request->validate(['search_query' => 'required|string']);
        session(['bangkit_search' => $request->input('search_query')]);

        return redirect()->route('bangkit.barang.hasil');
    }

    public function hasil()
    {
        $this->abortIfNotBangkitUser();
        $q = (string) session('bangkit_search', '');

        $barang = Barang::query()
            ->with(['jenis', 'bahanBarang', 'keadaan', 'lokasiBarang', 'pemegangPegawai'])
            ->where('is_aktif', 1)
            ->where(function ($w) use ($q) {
                $w->where('nama_barang', 'like', "%{$q}%")
                    ->orWhere('merk_type', 'like', "%{$q}%")
                    ->orWhere('tahun_pembelian', 'like', "%{$q}%");
            })
            ->orderBy('nama_barang')
            ->get();

        return view('bangkit.barang.hasil', ['barang' => $barang, 'q' => $q]);
    }

    /** Barang yang pemegangnya = user login (role 6 legacy "data_barang"). */
    public function dataSaya()
    {
        $this->abortIfNotBangkitUser();
        $pegawaiId = $this->pegawaiId();

        $barang = $pegawaiId
            ? Barang::query()->with(['jenis', 'bahanBarang', 'keadaan', 'lokasiBarang'])
                ->where('pemegang', $pegawaiId)->where('is_aktif', 1)->orderBy('nama_barang')->get()
            : collect();

        return view('bangkit.barang.data-saya', compact('barang'));
    }

    public function kartuInventaris()
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403);

        return view('bangkit.barang.kartu-inventaris', ['lokasi' => MasterLokasi::orderBy('lokasi')->get()]);
    }

    public function aksiKartuInventaris(Request $request)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403);
        $request->validate(['lokasi' => 'required']);
        session(['bangkit_lokasi' => $request->input('lokasi')]);

        return redirect()->route('bangkit.barang.kartu-inventaris-hasil');
    }

    public function kartuInventarisHasil()
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5], true), 403);

        $lokasiId = session('bangkit_lokasi');
        $barang = Barang::query()->with(['jenis', 'bahanBarang', 'keadaan', 'lokasiBarang', 'pemegangPegawai'])
            ->where('is_aktif', 1)
            ->when($lokasiId, fn ($q) => $q->where('lokasi', $lokasiId))
            ->orderBy('nama_barang')
            ->get();

        $lokasi = MasterLokasi::find($lokasiId);

        return view('bangkit.barang.kartu-inventaris-hasil', compact('barang', 'lokasi'));
    }

    public function tambah()
    {
        $this->abortIfNotBangkitAdmin();

        return view('bangkit.barang.form', $this->masterData() + ['barang' => null]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotBangkitAdmin();
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $data['is_aktif'] = 1;

        Barang::create($data);

        return redirect()->route('bangkit.barang.cari')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function ubah(int $id)
    {
        $this->abortIfNotBangkitAdmin();
        $barang = Barang::findOrFail($id);

        return view('bangkit.barang.form', $this->masterData() + ['barang' => $barang]);
    }

    public function update(Request $request, int $id)
    {
        $this->abortIfNotBangkitAdmin();
        $barang = Barang::findOrFail($id);
        $data = $this->validated($request);

        // Catat history kalau pemegang berubah (legacy).
        if ((int) $barang->pemegang !== (int) $data['pemegang']) {
            HistoryBarang::create([
                'id_barang' => $barang->Id,
                'pemegang_lama' => $barang->pemegang,
                'pemegang_baru' => $data['pemegang'],
                'modified_by' => auth()->id(),
            ]);
        }

        $data['modified_by'] = auth()->id();
        $barang->update($data);

        return redirect()->route('bangkit.barang.cari')->with('success', 'Barang berhasil diubah.');
    }

    public function hapus(int $id)
    {
        $this->abortIfNotBangkitAdmin();
        Barang::whereKey($id)->update(['is_aktif' => 0]);

        return redirect()->route('bangkit.barang.cari')->with('success', 'Barang berhasil dinonaktifkan.');
    }

    public function history(int $id)
    {
        $this->abortIfNotBangkitUser();
        $barang = Barang::findOrFail($id);
        $history = HistoryBarang::where('id_barang', $id)->orderByDesc('created_at')->get();

        return view('bangkit.barang.history', compact('barang', 'history'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_barang' => 'required|string|max:255',
            'merk_type' => 'nullable|string|max:255',
            'kode_barang' => 'required|string|max:255',
            'register' => 'required|string|max:255',
            'kode_barang_register' => 'required|string|max:255',
            'Jenis' => 'required|integer',
            'bahan' => 'required|integer',
            'keadaan_barang' => 'required|integer',
            'lokasi' => 'required|integer',
            'pemegang' => 'required|integer',
            'tahun_pembelian' => 'required|string|max:4',
            'harga' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'link_foto' => 'nullable|string|max:255',
        ]);
    }

    private function masterData(): array
    {
        return [
            'jenis_barang' => MasterJenisBarang::orderBy('jenis_barang')->get(),
            'bahan_barang' => MasterBahanBarang::orderBy('bahan')->get(),
            'keadaan_barang' => MasterKeadaanBarang::orderBy('keadaan_barang')->get(),
            'lokasi' => MasterLokasi::orderBy('lokasi')->get(),
            'pemegang_barang' => PegawaiKekuatan::orderBy('nama')->get(),
        ];
    }

    private function pegawaiId(): ?int
    {
        $nama = trim((string) (auth()->user()->nama ?? ''));
        if ($nama === '') {
            return null;
        }

        return PegawaiKekuatan::query()
            ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($nama)])
            ->value('Id');
    }
}
