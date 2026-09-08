<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Models\Persediaan\MasterBarang;
use App\Models\Persediaan\MasterRekening;
use App\Models\Persediaan\MasterSatuan;
use Illuminate\Http\Request;

class MasterBarangController extends Controller
{
    use ChecksPersediaanAccess;

    public function index()
    {
        $this->abortIfNotPersediaanAdmin();

        $barang = MasterBarang::query()
            ->with(['rekening', 'satuan'])
            ->orderBy('nama_barang')
            ->get()
            ->groupBy('kode_rekening');

        $rekening = MasterRekening::query()
            ->whereNotNull('parent_kode')
            ->orderBy('kode_rekening')
            ->get(['kode_rekening', 'nama_rekening']);

        $satuan = MasterSatuan::query()
            ->where('is_active', 1)
            ->orderBy('nama_satuan')
            ->get(['id', 'nama_satuan']);

        return view('persediaan.master-barang.index', [
            'barangGrouped' => $barang,
            'rekening' => $rekening,
            'satuan' => $satuan,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate([
            'kode_rekening' => ['required', 'string', 'max:50'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'id_satuan' => ['required', 'integer'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        MasterBarang::create([
            'kode_rekening' => $validated['kode_rekening'],
            'nama_barang' => $validated['nama_barang'],
            'id_satuan' => (int) $validated['id_satuan'],
            // Legacy stripped the thousands-separator dots before storing.
            'harga_satuan' => $this->normalizeHarga($request->input('harga_satuan')),
        ]);

        return back()->with('success', 'Barang berhasil ditambahkan!');
    }

    public function update(Request $request, int $id)
    {
        $this->abortIfNotPersediaanAdmin();

        $barang = MasterBarang::findOrFail($id);

        $validated = $request->validate([
            'kode_rekening' => ['required', 'string', 'max:50'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'id_satuan' => ['required', 'integer'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        $barang->update([
            'kode_rekening' => $validated['kode_rekening'],
            'nama_barang' => $validated['nama_barang'],
            'id_satuan' => (int) $validated['id_satuan'],
            'harga_satuan' => $this->normalizeHarga($request->input('harga_satuan')),
        ]);

        return back()->with('success', 'Barang berhasil diperbarui!');
    }

    private function normalizeHarga(?string $harga): float
    {
        // Legacy: str_replace('.', '', $_POST['harga_satuan']) — strips thousand separators.
        return (float) str_replace('.', '', (string) $harga);
    }
}
