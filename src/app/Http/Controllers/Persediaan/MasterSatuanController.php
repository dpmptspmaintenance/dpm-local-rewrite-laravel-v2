<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Models\Persediaan\MasterSatuan;
use Illuminate\Http\Request;

class MasterSatuanController extends Controller
{
    use ChecksPersediaanAccess;

    public function index()
    {
        $this->abortIfNotPersediaanAdmin();

        $satuan = MasterSatuan::query()->orderBy('nama_satuan')->get();

        return view('persediaan.master-satuan.index', [
            'satuan' => $satuan,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate([
            'nama_satuan' => ['required', 'string', 'max:50', 'unique:persediaan.master_satuan,nama_satuan'],
        ]);

        MasterSatuan::create($validated);

        return back()->with('success', 'Satuan berhasil ditambahkan!');
    }

    public function update(Request $request, int $id)
    {
        $this->abortIfNotPersediaanAdmin();

        $satuan = MasterSatuan::findOrFail($id);

        $validated = $request->validate([
            'nama_satuan' => ['required', 'string', 'max:50', 'unique:persediaan.master_satuan,nama_satuan,'.$satuan->getKey()],
        ]);

        $satuan->update($validated);

        return back()->with('success', 'Satuan berhasil diperbarui!');
    }

    public function toggleStatus(int $id)
    {
        $this->abortIfNotPersediaanAdmin();

        $satuan = MasterSatuan::findOrFail($id);
        $satuan->update(['is_active' => ! $satuan->is_active]);

        return back()->with('success', 'Status satuan berhasil diubah!');
    }
}
