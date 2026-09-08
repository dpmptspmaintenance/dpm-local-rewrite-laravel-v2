<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ChecksPersediaanAccess;
use App\Http\Controllers\Persediaan\Concerns\ComputesStock;
use App\Models\Persediaan\StokOpnameDetail;
use App\Models\Persediaan\StokOpnameHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatOpnameController extends Controller
{
    use ChecksPersediaanAccess, ComputesStock;

    public function index()
    {
        $this->abortIfNotPersediaanAdmin();

        $opnames = StokOpnameHeader::query()
            ->with(['details.barang.satuan'])
            ->orderByDesc('tanggal_opname')
            ->orderByDesc('id')
            ->get()
            ->map(function (StokOpnameHeader $h) {
                return [
                    'row' => $h,
                    'total_item' => $h->details->count(),
                    'total_selisih_unit' => $h->details->sum(fn ($d) => abs($d->selisih)),
                    'details' => $h->details,
                ];
            });

        return view('persediaan.riwayat-opname.index', [
            'opnames' => $opnames,
        ]);
    }

    /** Hapus draft opname (draft only; admin may also delete). DB has ON DELETE CASCADE on details. */
    public function hapusOpname(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate(['id_opname' => ['required', 'integer']]);
        $opname = StokOpnameHeader::findOrFail($validated['id_opname']);

        if ($opname->status !== 'draft' && ! $this->isAdmin(auth()->user())) {
            return back()->with('error', 'Akses ditolak.');
        }

        $opname->delete();

        return back()->with('success', 'Dokumen Stok Opname berhasil dihapus.');
    }

    /** Finalisasi draft → final + otomatisasi stock adjustment. */
    public function finalkanOpname(Request $request)
    {
        $this->abortIfNotPersediaanAdmin();

        $validated = $request->validate(['id_opname' => ['required', 'integer']]);
        $opname = StokOpnameHeader::query()
            ->where('id', $validated['id_opname'])
            ->where('status', 'draft')
            ->firstOrFail();

        try {
            DB::connection('persediaan')->transaction(function () use ($opname) {
                $details = StokOpnameDetail::query()
                    ->where('id_opname_header', $opname->id)
                    ->get();

                $selisihRows = $details
                    ->filter(fn ($d) => $d->selisih != 0)
                    ->map(fn ($d) => (object) [
                        'id_barang' => $d->id_barang,
                        'qty' => $d->selisih,
                        'harga' => $d->harga_satuan,
                    ])
                    ->values();

                $opname->update(['status' => 'final']);

                if ($selisihRows->isNotEmpty()) {
                    $this->applyOpnameAdjustments(
                        $selisihRows,
                        $opname->tanggal_opname->format('Y-m-d'),
                        [
                            'ttd_kiri' => $opname->ttd_kiri,
                            'ttd_tengah' => $opname->ttd_tengah,
                            'ttd_kanan' => $opname->ttd_kanan,
                        ],
                        (int) auth()->id()
                    );
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memfinalisasi: '.$e->getMessage());
        }

        return back()->with('success', 'Dokumen Opname telah berhasil DIFINALISASI dan stok sistem otomatis disesuaikan!');
    }
}
