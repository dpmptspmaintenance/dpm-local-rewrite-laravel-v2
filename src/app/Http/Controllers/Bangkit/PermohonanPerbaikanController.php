<?php

namespace App\Http\Controllers\Bangkit;

use App\Http\Controllers\Bangkit\Concerns\ChecksBangkitAccess;
use App\Http\Controllers\Controller;
use App\Models\Bangkit\Barang;
use App\Models\Bangkit\PermohonanPerbaikan;
use Illuminate\Http\Request;

class PermohonanPerbaikanController extends Controller
{
    use ChecksBangkitAccess;

    /** Form ajukan permohonan perbaikan untuk sebuah barang. */
    public function form(int $idBarang)
    {
        $this->abortIfNotBangkitUser();
        $barang = Barang::with(['jenis', 'bahanBarang', 'keadaan', 'lokasiBarang', 'pemegangPegawai'])->findOrFail($idBarang);

        return view('bangkit.permohonan.form', compact('barang'));
    }

    public function store(Request $request, int $idBarang)
    {
        $this->abortIfNotBangkitUser();
        $data = $request->validate([
            'keterangan_kerusakan' => 'nullable|string',
            'kode_barang_register' => 'nullable|string|max:255',
        ]);

        PermohonanPerbaikan::create([
            'id_barang' => $idBarang,
            'created_by' => auth()->id(),
            'kode_barang_register' => $data['kode_barang_register'] ?? null,
            'uraian_kerusakan' => $data['keterangan_kerusakan'] ?? null,
            'tanggal_permohonan' => now()->toDateString(),
            'is_aktif' => 1,
        ]);

        return redirect()->route('bangkit.barang.data-saya')->with('success', 'Permohonan perbaikan berhasil diajukan.');
    }

    /** Detail barang + daftar permohonannya. */
    public function list(int $idBarang)
    {
        $this->abortIfNotBangkitUser();
        $barang = Barang::with(['jenis', 'bahanBarang', 'keadaan', 'lokasiBarang', 'pemegangPegawai'])->findOrFail($idBarang);
        $list = PermohonanPerbaikan::where('id_barang', $idBarang)->where('is_aktif', 1)->orderByDesc('tanggal_permohonan')->get();

        return view('bangkit.permohonan.list', compact('barang', 'list'));
    }

    public function formUbah(int $idPermohonan)
    {
        $this->abortIfNotBangkitUser();
        $permohonan = PermohonanPerbaikan::with('barang')->findOrFail($idPermohonan);

        return view('bangkit.permohonan.form-ubah', ['permohonan' => $permohonan, 'barang' => $permohonan->barang]);
    }

    public function update(Request $request, int $idPermohonan)
    {
        $this->abortIfNotBangkitUser();
        $data = $request->validate(['keterangan_kerusakan' => 'nullable|string']);
        $permohonan = PermohonanPerbaikan::findOrFail($idPermohonan);
        $permohonan->update([
            'uraian_kerusakan' => $data['keterangan_kerusakan'] ?? null,
            'modified_by' => auth()->id(),
        ]);

        return redirect()->route('bangkit.permohonan.list', $permohonan->id_barang)->with('success', 'Permohonan berhasil diubah.');
    }

    public function hapus(int $idPermohonan)
    {
        $this->abortIfNotBangkitUser();
        $permohonan = PermohonanPerbaikan::findOrFail($idPermohonan);
        $idBarang = $permohonan->id_barang;
        $permohonan->update(['is_aktif' => 0]);

        return redirect()->route('bangkit.permohonan.list', $idBarang)->with('success', 'Permohonan dihapus.');
    }

    /** Antrean verifikasi sesuai level role yang login. */
    public function listVerifikasi()
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5, 3], true), 403, 'Akses Ditolak!');

        $query = PermohonanPerbaikan::with('barang')->where('is_aktif', 1);

        if (! $this->isBangkitAdmin()) {
            $query = match ($this->roleBangkit()) {
                5 => $query->where('verifikasi_b_barang', 0),
                4 => $query->where('verifikasi_b_barang', 1)->where(function ($w) {
                    $w->where('verifikasi_umpeg', 0)->orWhereNull('verifikasi_umpeg');
                }),
                3 => $query->where('verifikasi_b_barang', 1)->where('verifikasi_umpeg', 1)->where(function ($w) {
                    $w->where('verifikasi_sekdin', 0)->orWhereNull('verifikasi_sekdin');
                }),
                default => $query->whereRaw('1 = 0'),
            };
        }

        $list = $query->orderByDesc('tanggal_permohonan')->get();

        return view('bangkit.permohonan.verifikasi-list', compact('list'));
    }

    public function formVerifikasi(int $idPermohonan)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5, 3], true), 403, 'Akses Ditolak!');

        $permohonan = PermohonanPerbaikan::with(['barang.jenis', 'barang.keadaan', 'barang.lokasiBarang', 'barang.pemegangPegawai'])->findOrFail($idPermohonan);

        return view('bangkit.permohonan.form-verifikasi', compact('permohonan'));
    }

    /** Simpan keputusan verifikasi pada level role yang login. */
    public function verifikasi(Request $request, int $idPermohonan)
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [4, 5, 3], true), 403, 'Akses Ditolak!');

        $data = $request->validate([
            'verifikasi' => 'required|integer|in:0,1,2',
            'penjelasan' => 'nullable|string',
        ]);

        $permohonan = PermohonanPerbaikan::findOrFail($idPermohonan);
        $role = $this->isBangkitAdmin() ? null : $this->roleBangkit();

        // Admin boleh memilih level yang divalidasi lewat input `level`,
        // user biasa mengikuti role-nya sendiri.
        $level = $this->isBangkitAdmin()
            ? $request->validate(['level' => 'required|in:b,umpeg,sekdin'])['level']
            : match ($role) {
                5 => 'b',
                4 => 'umpeg',
                3 => 'sekdin',
                default => abort(403),
            };

        $kolom = match ($level) {
            'b' => ['verifikasi_b_barang' => $data['verifikasi'], 'penjelasan_b_barang' => $data['penjelasan'] ?? null, 'tanggal_verifikasi_b_barang' => now()->toDateString()],
            'umpeg' => ['verifikasi_umpeg' => $data['verifikasi'], 'penjelasan_umpeg' => $data['penjelasan'] ?? null, 'tanggal_verifikasi_umpeg' => now()->toDateString()],
            'sekdin' => ['verifikasi_sekdin' => $data['verifikasi'], 'penjelasan_sekdin' => $data['penjelasan'] ?? null, 'tanggal_verifikasi_sekdin' => now()->toDateString()],
        };
        $kolom['modified_by'] = auth()->id();

        $permohonan->update($kolom);

        return redirect()->route('bangkit.permohonan.list', $permohonan->id_barang)->with('success', 'Verifikasi tersimpan.');
    }

    public function listSelesai()
    {
        $this->abortIfNotBangkitAdmin();
        $list = PermohonanPerbaikan::with('barang')
            ->where('is_aktif', 1)
            ->where('verifikasi_b_barang', 1)
            ->where('verifikasi_umpeg', 1)
            ->where('verifikasi_sekdin', 1)
            ->whereNull('tanggal_selesai')
            ->orderByDesc('tanggal_permohonan')
            ->get();

        return view('bangkit.permohonan.selesai-list', compact('list'));
    }

    public function formSelesai(int $idPermohonan)
    {
        $this->abortIfNotBangkitAdmin();
        $permohonan = PermohonanPerbaikan::with(['barang.jenis', 'barang.keadaan', 'barang.lokasiBarang', 'barang.pemegangPegawai'])->findOrFail($idPermohonan);

        return view('bangkit.permohonan.form-selesai', compact('permohonan'));
    }

    public function selesai(Request $request, int $idPermohonan)
    {
        $this->abortIfNotBangkitAdmin();
        $data = $request->validate([
            'perbaikan_ke' => 'nullable|string|max:50',
            'pagu_anggaran' => 'nullable|numeric|min:0',
            'tanggal_pengerjaan' => 'nullable|date',
            'pengerjaan_oleh' => 'nullable|string|max:255',
            'tanggal_selesai' => 'nullable|date',
            'serah_terima_oleh' => 'nullable|string|max:255',
            'biaya' => 'nullable|numeric|min:0',
            'spj_tanggal' => 'nullable|date',
            'uraian_perbaikan' => 'nullable|string',
        ]);
        $data['modified_by'] = auth()->id();

        PermohonanPerbaikan::whereKey($idPermohonan)->update($data);

        return redirect()->route('bangkit.permohonan.selesai-list')->with('success', 'Permohonan diselesaikan.');
    }
}
