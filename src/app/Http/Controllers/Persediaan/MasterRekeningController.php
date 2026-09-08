<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Models\Persediaan\MasterRekening;
use Illuminate\Http\Request;

class MasterRekeningController extends Controller
{
    use ChecksPersediaanAccess;

    public function index()
    {
        $this->abortIfNotPersediaanAdmin();

        $rekening = MasterRekening::query()->orderBy('kode_rekening')->get();
        $parents = MasterRekening::query()->orderBy('kode_rekening')->get(['kode_rekening', 'nama_rekening']);

        return view('persediaan.master-rekening.index', [
            'rekening' => $rekening,
            'parents' => $parents,
        ]);
    }

    public function store(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate([
            'kode_rekening' => ['required', 'string', 'max:50', 'unique:persediaan.master_rekening,kode_rekening'],
            'nama_rekening' => ['required', 'string', 'max:255'],
            'parent_kode' => ['nullable', 'string', 'max:50'],
        ]);

        MasterRekening::create($validated);

        return back()->with('success', 'Rekening berhasil ditambahkan!');
    }

    public function update(Request $request, string $kode)
    {
        $this->abortIfNotPersediaanAdmin();

        $rekening = MasterRekening::query()->where('kode_rekening', $kode)->firstOrFail();

        $validated = $request->validate([
            'kode_rekening' => ['required', 'string', 'max:50', 'unique:persediaan.master_rekening,kode_rekening,'.$rekening->getKey()],
            'nama_rekening' => ['required', 'string', 'max:255'],
            'parent_kode' => ['nullable', 'string', 'max:50'],
        ]);

        $rekening->update($validated);

        return back()->with('success', 'Rekening berhasil diperbarui!');
    }

    public function toggleStatus(Request $request, string $kode)
    {
        $this->abortIfNotPersediaanAdmin();

        $rekening = MasterRekening::query()->where('kode_rekening', $kode)->firstOrFail();
        $rekening->update(['is_active' => ! $rekening->is_active]);

        return back()->with('success', 'Status rekening berhasil diubah!');
    }
}
