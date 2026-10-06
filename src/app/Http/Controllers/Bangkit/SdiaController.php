<?php

namespace App\Http\Controllers\Bangkit;

use App\Http\Controllers\Bangkit\Concerns\ChecksBangkitAccess;
use App\Http\Controllers\Controller;
use App\Models\Bangkit\SdiaAnggaran;
use App\Models\Bangkit\SdiaAnggaranBulanan;
use App\Models\Bangkit\SdiaBulanan;
use App\Models\Bangkit\SdiaDataDpa;
use App\Models\Bangkit\SdiaKegiatan;
use App\Models\Bangkit\SdiaKlasPersediaan;
use App\Models\Bangkit\SdiaTransaksiBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SdiaController extends Controller
{
    use ChecksBangkitAccess;

    private const BULAN_LIST = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private function guard(): void
    {
        $this->abortIfNotBangkitUser();
        abort_unless($this->isBangkitAdmin() || in_array($this->roleBangkit(), [6, 5, 4, 3], true), 403, 'Akses Ditolak! SDIA Persediaan untuk role terkait.');
    }

    /** Id bidang yang dipakai untuk query & insert (legacy: session bidang). */
    private function bidang(): int
    {
        $id = $this->bidangId();
        abort_if($id === null, 422, 'Akun Anda belum dipetakan ke sebuah Bidang (cocokkan kolom Bidang pengguna dengan master katkit_bidang).');

        return (int) $id;
    }

    // ---------------------------------------------------------------- Kegiatan

    public function kegiatan()
    {
        $this->guard();
        $bidang = $this->scopeBidangId();
        $kegiatan = SdiaKegiatan::query()
            ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
            ->where('is_aktif', 1)->orderByDesc('tahun')->get();

        return view('bangkit.sdia.kegiatan', compact('kegiatan'));
    }

    public function tambahKegiatan()
    {
        $this->guard();

        return view('bangkit.sdia.kegiatan-form', ['kegiatan' => null]);
    }

    public function storeKegiatan(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'tahun' => 'required|integer',
            'nama_kegiatan' => 'required|string|max:255',
        ]);
        SdiaKegiatan::create($data + [
            'id_katkit_bidang' => $this->bidang(),
            'modified_by' => auth()->id(),
            'is_aktif' => 1,
        ]);

        return redirect()->route('bangkit.sdia.kegiatan')->with('success', 'Kegiatan ditambahkan.');
    }

    public function ubahKegiatan(int $id)
    {
        $this->guard();
        $kegiatan = SdiaKegiatan::findOrFail($id);

        return view('bangkit.sdia.kegiatan-form', compact('kegiatan'));
    }

    public function updateKegiatan(Request $request, int $id)
    {
        $this->guard();
        $data = $request->validate([
            'tahun' => 'required|integer',
            'nama_kegiatan' => 'required|string|max:255',
        ]);
        SdiaKegiatan::whereKey($id)->update($data);

        return redirect()->route('bangkit.sdia.kegiatan')->with('success', 'Kegiatan diubah.');
    }

    // ------------------------------------------------------------ Klasifikasi

    public function klasifikasi()
    {
        $this->guard();

        return view('bangkit.sdia.klasifikasi', ['klasifikasi' => SdiaKlasPersediaan::where('is_aktif', 1)->orderBy('rek_klas')->get()]);
    }

    // -------------------------------------------------------------- Anggaran

    public function anggaran()
    {
        $this->guard();
        $bidang = $this->scopeBidangId();
        $anggaran = SdiaAnggaran::with(['kegiatan', 'klasifikasi'])
            ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
            ->where('is_aktif', 1)->get();

        foreach ($anggaran as $item) {
            $item->used_in_bulanan = SdiaAnggaranBulanan::where('id_anggaran', $item->Id)->where('is_aktif', 1)->exists();
        }

        return view('bangkit.sdia.anggaran', compact('anggaran'));
    }

    public function tambahAnggaran()
    {
        $this->guard();

        return view('bangkit.sdia.anggaran-form', array_merge($this->anggaranMasterData(), ['anggaran' => null]));
    }

    public function storeAnggaran(Request $request)
    {
        $this->guard();
        $data = $this->validateAnggaran($request);
        SdiaAnggaran::create($data + [
            'id_katkit_bidang' => $this->bidang(),
            'is_aktif' => 1,
            'created_at' => now(),
            'modified_at' => now(),
            'modified_by' => auth()->user()->username ?? auth()->user()->nama,
        ]);

        return redirect()->route('bangkit.sdia.anggaran')->with('success', 'Anggaran ditambahkan.');
    }

    public function ubahAnggaran(int $id)
    {
        $this->guard();
        $anggaran = SdiaAnggaran::findOrFail($id);

        return view('bangkit.sdia.anggaran-form', array_merge($this->anggaranMasterData(), ['anggaran' => $anggaran]));
    }

    public function updateAnggaran(Request $request, int $id)
    {
        $this->guard();
        $data = $this->validateAnggaran($request) + ['modified_at' => now(), 'modified_by' => auth()->user()->username ?? auth()->user()->nama];
        SdiaAnggaran::whereKey($id)->update($data);

        return redirect()->route('bangkit.sdia.anggaran')->with('success', 'Anggaran diubah.');
    }

    public function deleteAnggaran(int $id)
    {
        $this->guard();
        if (SdiaAnggaranBulanan::where('id_anggaran', $id)->where('is_aktif', 1)->exists()) {
            return back()->with('error', 'Tidak bisa dihapus, anggaran sedang digunakan.');
        }
        SdiaAnggaran::whereKey($id)->update(['is_aktif' => 0]);

        return redirect()->route('bangkit.sdia.anggaran')->with('success', 'Anggaran dihapus.');
    }

    // ------------------------------------------------------------------- DPA

    public function dpa(Request $request)
    {
        $this->guard();
        $tahun = $request->query('tahun');
        $klasifikasi = $request->query('klasifikasi');
        $bidang = $this->scopeBidangId();
        $dpa = collect();

        if ($tahun !== null || $klasifikasi !== null) {
            $dpa = SdiaDataDpa::with(['kegiatan', 'klasifikasi'])
                ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
                ->where('is_aktif', 1)
                ->when($tahun && $tahun !== 'all', fn ($q) => $q->where('tahun', $tahun))
                ->when($klasifikasi && $klasifikasi !== 'all', fn ($q) => $q->where('rek_klas', $klasifikasi))
                ->orderByDesc('tahun')->orderBy('rek_klas')->get();

            foreach ($dpa as $row) {
                $row->used_in_transaksi = SdiaTransaksiBarang::where('id_sdia_data_dpa', $row->Id)->exists();
            }
        }

        return view('bangkit.sdia.dpa', [
            'dpa' => $dpa,
            'tahun' => $tahun,
            'klasifikasi' => $klasifikasi,
        ]);
    }

    public function tambahDpa()
    {
        $this->guard();

        return view('bangkit.sdia.dpa-form', array_merge($this->dpaMasterData(), ['dpa' => null]));
    }

    public function storeDpa(Request $request)
    {
        $this->guard();
        $data = $this->validateDpa($request);
        SdiaDataDpa::create($data + [
            'id_katkit_bidang' => $this->bidang(),
            'modified_by' => auth()->id(),
            'is_aktif' => 1,
        ]);

        return redirect()->route('bangkit.sdia.dpa', ['tahun' => $data['tahun'], 'klasifikasi' => $data['rek_klas']])->with('success', 'DPA ditambahkan.');
    }

    public function ubahDpa(int $id)
    {
        $this->guard();
        $dpa = SdiaDataDpa::findOrFail($id);

        return view('bangkit.sdia.dpa-form', array_merge($this->dpaMasterData(), ['dpa' => $dpa]));
    }

    public function updateDpa(Request $request, int $id)
    {
        $this->guard();
        $data = $this->validateDpa($request) + ['id_katkit_bidang' => $this->bidang(), 'modified_by' => auth()->id()];
        SdiaDataDpa::whereKey($id)->update($data);

        return redirect()->route('bangkit.sdia.dpa', ['tahun' => $data['tahun'], 'klasifikasi' => $data['rek_klas']])->with('success', 'DPA diubah.');
    }

    public function deleteDpa(int $id)
    {
        $this->guard();
        SdiaDataDpa::whereKey($id)->update(['is_aktif' => 0]);

        return redirect()->route('bangkit.sdia.dpa')->with('success', 'DPA dihapus.');
    }

    // -------------------------------------------------------------- Transaksi

    public function transaksi()
    {
        $this->guard();
        $bidang = $this->scopeBidangId();
        $transaksi = SdiaTransaksiBarang::with('dpa')
            ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
            ->where('is_aktif', 1)->orderByDesc('tgl_transaksi')->get();

        return view('bangkit.sdia.transaksi', compact('transaksi'));
    }

    public function tambahTransaksi()
    {
        $this->guard();
        $bidang = $this->scopeBidangId();

        return view('bangkit.sdia.transaksi-form', [
            'transaksi' => null,
            'dpa' => SdiaDataDpa::query()
                ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
                ->where('is_aktif', 1)->orderBy('nama_barang')->get(),
        ]);
    }

    public function storeTransaksi(Request $request)
    {
        $this->guard();
        $data = $this->validateTransaksi($request);
        $dpa = SdiaDataDpa::findOrFail($data['id_sdia_data_dpa']);
        SdiaTransaksiBarang::create($data + [
            'id_sdia_kegiatan' => $dpa->id_sdia_kegiatan,
            'id_katkit_bidang' => $this->bidang(),
            'modified_by' => auth()->id(),
            'is_aktif' => 1,
        ]);

        return redirect()->route('bangkit.sdia.transaksi')->with('success', 'Transaksi ditambahkan.');
    }

    public function ubahTransaksi(int $id)
    {
        $this->guard();
        $bidang = $this->scopeBidangId();

        return view('bangkit.sdia.transaksi-form', [
            'transaksi' => SdiaTransaksiBarang::findOrFail($id),
            'dpa' => SdiaDataDpa::query()
                ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
                ->where('is_aktif', 1)->orderBy('nama_barang')->get(),
        ]);
    }

    public function updateTransaksi(Request $request, int $id)
    {
        $this->guard();
        $data = $this->validateTransaksi($request);
        $dpa = SdiaDataDpa::findOrFail($data['id_sdia_data_dpa']);
        SdiaTransaksiBarang::whereKey($id)->update($data + [
            'id_sdia_kegiatan' => $dpa->id_sdia_kegiatan,
            'id_katkit_bidang' => $this->bidang(),
            'modified_by' => auth()->id(),
        ]);

        return redirect()->route('bangkit.sdia.transaksi')->with('success', 'Transaksi diubah.');
    }

    // ----------------------------------------------------------- SDIA Bulanan

    public function bulanan(Request $request)
    {
        $this->guard();
        $tahun = $request->input('tahun') ?? session('bangkit_sdia_tahun');
        $bulan = $request->input('bulan') ?? session('bangkit_sdia_bulan');
        if ($request->filled('tahun')) {
            session(['bangkit_sdia_tahun' => $tahun]);
        }
        if ($request->filled('bulan')) {
            session(['bangkit_sdia_bulan' => $bulan]);
        }

        $bidang = $this->scopeBidangId();
        $grouped = [];

        if ($tahun && $bulan) {
            $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
            $rows = SdiaBulanan::query()
                ->select('sdia_bulanan.*')
                ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
                ->where('tahun', $tahun)->where('bulan', $bulanPad)->where('is_aktif', 1)->get();

            $grouped = $this->buildBulanan($rows, $tahun, (int) $bulan, $bidang);
        }

        return view('bangkit.sdia.bulanan', [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'bulanList' => self::BULAN_LIST,
            'sdia' => $grouped,
        ]);
    }

    public function clearFilter()
    {
        session()->forget(['bangkit_sdia_tahun', 'bangkit_sdia_bulan']);

        return redirect()->route('bangkit.sdia.bulanan');
    }

    /** Hitung ulang saldo bulan terpilih dari transaksi (legacy hitung_sdia_ulang/hitung_sdia). */
    public function hitungUlang(Request $request)
    {
        $this->guard();
        $tahun = (int) $request->input('tahun');
        $bulan = (int) $request->input('bulan');
        $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $bidang = $this->bidang();
        session(['bangkit_sdia_tahun' => $tahun, 'bangkit_sdia_bulan' => $bulan]);

        $transaksi = SdiaTransaksiBarang::query()
            ->whereYear('tgl_transaksi', $tahun)->whereMonth('tgl_transaksi', $bulan)
            ->where('id_katkit_bidang', $bidang)->where('is_aktif', 1)->get();

        $grouped = $transaksi->groupBy(fn ($r) => $r->id_sdia_kegiatan.'-'.$r->id_sdia_data_dpa);

        foreach ($grouped as $item) {
            $saldoMasuk = 0;
            $saldoKeluar = 0;
            $idMasuk = null;
            $idKeluar = null;
            $hargaSatuan = 0;
            foreach ($item as $row) {
                if ($row->klas_transaksi === SdiaTransaksiBarang::MASUK) {
                    $saldoMasuk += $row->jumlah_transaksi;
                    $idMasuk = $row->Id;
                    $hargaSatuan = $row->harga_satuan;
                } elseif ($row->klas_transaksi === SdiaTransaksiBarang::KELUAR) {
                    $saldoKeluar += $row->jumlah_transaksi;
                    $idKeluar = $row->Id;
                }
            }

            $prevBulan = $bulan === 1 ? '12' : str_pad($bulan - 1, 2, '0', STR_PAD_LEFT);
            $prevTahun = $bulan === 1 ? $tahun - 1 : $tahun;
            $prev = SdiaBulanan::query()
                ->where('tahun', $prevTahun)->where('bulan', $prevBulan)->where('is_aktif', 1)
                ->whereIn('id_transaksi_barang_masuk', function ($q) use ($item) {
                    $q->select('Id')->from('sdia_transaksi_barang')
                        ->where('id_sdia_kegiatan', $item->first()->id_sdia_kegiatan)
                        ->where('id_sdia_data_dpa', $item->first()->id_sdia_data_dpa);
                })->first();
            $saldoAwal = $prev?->saldo_akhir ?? 0;
            $saldoAkhir = $saldoAwal + $saldoMasuk - $saldoKeluar;

            $insert = [
                'id_katkit_bidang' => $bidang,
                'tahun' => $tahun,
                'bulan' => $bulanPad,
                'id_transaksi_barang_masuk' => $idMasuk,
                'id_transaksi_barang_keluar' => $idKeluar,
                'saldo_awal' => $saldoAwal,
                'saldo_masuk' => $saldoMasuk,
                'harga_satuan' => $hargaSatuan,
                'saldo_keluar' => $saldoKeluar,
                'saldo_akhir' => $saldoAkhir,
                'is_aktif' => 1,
                'created_at' => now(),
                'modified_by' => auth()->id(),
            ];

            $existing = SdiaBulanan::query()->where('tahun', $tahun)->where('bulan', $bulanPad)
                ->where('id_katkit_bidang', $bidang)->where('id_transaksi_barang_masuk', $idMasuk)
                ->where('is_aktif', 1)->first();

            if (! $existing) {
                SdiaBulanan::create($insert);
            } else {
                $changed = (float) $existing->saldo_awal !== (float) $saldoAwal
                    || (float) $existing->saldo_masuk !== (float) $saldoMasuk
                    || (float) $existing->saldo_keluar !== (float) $saldoKeluar
                    || (float) $existing->saldo_akhir !== (float) $saldoAkhir
                    || (float) $existing->harga_satuan !== (float) $hargaSatuan;
                if ($changed) {
                    SdiaBulanan::whereKey($existing->Id)->update(['is_aktif' => 0]);
                    SdiaBulanan::create($insert);
                }
            }
        }

        $this->prosesAnggaranBulanan($tahun, (int) $bulan, $bidang);

        return redirect()->route('bangkit.sdia.bulanan')->with('success', 'SDIA dihitung ulang.');
    }

    // --------------------------------------------------------------- Saldo Awal

    public function saldoAwal(Request $request)
    {
        $this->guard();
        $tahun = (int) ($request->input('tahun') ?? date('Y'));
        $bidang = $this->scopeBidangId();
        $tahunSebelumnya = $tahun - 1;

        $rows = SdiaBulanan::query()
            ->select('sdia_bulanan.*')
            ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
            ->where('tahun', $tahunSebelumnya)->where('bulan', '12')->where('is_aktif', 1)->get();

        $saldo = [];
        foreach ($rows as $row) {
            $dpa = SdiaDataDpa::find($this->transaksiMasukDpa($row));
            if (! $dpa) {
                continue;
            }
            $keg = SdiaKegiatan::find($dpa->id_sdia_kegiatan);
            $klas = SdiaKlasPersediaan::find($dpa->rek_klas);
            $saldo[$keg?->nama_kegiatan][$dpa->rek_klas] ??= ['nama_klas' => $klas?->nama_klas, 'items' => []];
            $saldo[$keg?->nama_kegiatan][$dpa->rek_klas]['items'][] = [
                'nama_barang' => $dpa->nama_barang,
                'satuan' => $dpa->satuan,
                'saldo_awal' => $row->saldo_akhir,
                'harga_satuan' => $row->harga_satuan,
                'nilai_awal' => (float) $row->saldo_akhir * (float) $row->harga_satuan,
            ];
        }

        return view('bangkit.sdia.saldo-awal', [
            'tahun' => $tahun,
            'tahun_list' => range(2020, (int) date('Y') + 1),
            'saldo' => $saldo,
        ]);
    }

    // ------------------------------------------------------------- helpers

    private function buildBulanan($rows, $tahun, int $bulan, $bidang): array
    {
        $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $anggaranData = SdiaAnggaranBulanan::query()
            ->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))
            ->where('tahun', $tahun)->where('bulan', $bulanPad)->where('is_aktif', 1)->get();

        $out = [];
        foreach ($rows as $row) {
            $dpa = SdiaDataDpa::find($this->transaksiMasukDpa($row));
            if (! $dpa) {
                continue;
            }
            $keg = SdiaKegiatan::find($dpa->id_sdia_kegiatan);
            $klas = SdiaKlasPersediaan::find($dpa->rek_klas);
            $namaKeg = $keg?->nama_kegiatan ?? '-';
            if (! isset($out[$namaKeg][$dpa->rek_klas])) {
                $ang = $anggaranData->first(fn ($a) => $a->id_sdia_kegiatan == $dpa->id_sdia_kegiatan && $a->rek_klas == $dpa->rek_klas);
                $out[$namaKeg][$dpa->rek_klas] = [
                    'nama_klas' => $klas?->nama_klas,
                    'anggaran_terpakai' => $ang?->anggaran_terpakai ?? 0,
                    'sisa_anggaran' => $ang?->sisa_anggaran ?? 0,
                    'anggaran_awal' => $ang?->anggaran_awal ?? 0,
                    'items' => [],
                ];
            }
            $out[$namaKeg][$dpa->rek_klas]['items'][] = [
                'nama_barang' => $dpa->nama_barang,
                'satuan' => $dpa->satuan,
                'saldo_awal' => $row->saldo_awal,
                'saldo_masuk' => $row->saldo_masuk,
                'harga_satuan' => $row->harga_satuan,
                'saldo_keluar' => $row->saldo_keluar,
                'saldo_akhir' => $row->saldo_akhir,
            ];
        }

        return $out;
    }

    private function transaksiMasukDpa(SdiaBulanan $row): ?int
    {
        if (! $row->id_transaksi_barang_masuk) {
            return null;
        }

        return SdiaTransaksiBarang::whereKey($row->id_transaksi_barang_masuk)->value('id_sdia_data_dpa');
    }

    private function prosesAnggaranBulanan(int $tahun, int $bulan, int $bidang): void
    {
        $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $kombinasi = SdiaTransaksiBarang::query()
            ->join('sdia_data_dpa as sdd', 'sdia_transaksi_barang.id_sdia_data_dpa', '=', 'sdd.Id')
            ->whereYear('sdia_transaksi_barang.tgl_transaksi', $tahun)
            ->whereMonth('sdia_transaksi_barang.tgl_transaksi', $bulan)
            ->where('sdia_transaksi_barang.id_katkit_bidang', $bidang)
            ->where('sdia_transaksi_barang.is_aktif', 1)
            ->select('sdia_transaksi_barang.id_sdia_kegiatan as id_sdia_kegiatan', 'sdd.rek_klas as rek_klas')
            ->distinct()->get();

        foreach ($kombinasi as $row) {
            $usage = (float) SdiaBulanan::query()
                ->join('sdia_transaksi_barang as stb', 'sdia_bulanan.id_transaksi_barang_masuk', '=', 'stb.Id')
                ->join('sdia_data_dpa as sdd', 'stb.id_sdia_data_dpa', '=', 'sdd.Id')
                ->where('sdia_bulanan.tahun', $tahun)->where('sdia_bulanan.bulan', $bulanPad)
                ->where('sdia_bulanan.is_aktif', 1)->where('sdia_bulanan.id_katkit_bidang', $bidang)
                ->where('stb.id_sdia_kegiatan', $row->id_sdia_kegiatan)->where('sdd.rek_klas', $row->rek_klas)
                ->sum(DB::raw('sdia_bulanan.saldo_masuk * sdia_bulanan.harga_satuan'));

            $master = SdiaAnggaran::query()->where('id_katkit_bidang', $bidang)
                ->where('id_sdia_kegiatan', $row->id_sdia_kegiatan)->where('rek_klas', $row->rek_klas)
                ->where('is_aktif', 1)->first();
            $idAnggaran = $master?->Id;
            $nominal = $master?->anggaran ?? 0;

            $prevBulan = $bulan === 1 ? '12' : str_pad($bulan - 1, 2, '0', STR_PAD_LEFT);
            $prevTahun = $bulan === 1 ? $tahun - 1 : $tahun;
            $prev = SdiaAnggaranBulanan::query()->where('tahun', $prevTahun)->where('bulan', $prevBulan)
                ->where('id_katkit_bidang', $bidang)->where('id_sdia_kegiatan', $row->id_sdia_kegiatan)
                ->where('rek_klas', $row->rek_klas)->where('is_aktif', 1)->first();
            $anggaranAwal = $prev?->sisa_anggaran ?? $nominal;

            $existing = SdiaAnggaranBulanan::query()->where('tahun', $tahun)->where('bulan', $bulanPad)
                ->where('id_katkit_bidang', $bidang)->where('id_sdia_kegiatan', $row->id_sdia_kegiatan)
                ->where('rek_klas', $row->rek_klas)->where('is_aktif', 1)->first();

            $payload = [
                'id_katkit_bidang' => $bidang,
                'id_sdia_kegiatan' => $row->id_sdia_kegiatan,
                'rek_klas' => $row->rek_klas,
                'id_anggaran' => $idAnggaran,
                'tahun' => $tahun,
                'bulan' => $bulanPad,
                'anggaran_awal' => $anggaranAwal,
                'anggaran_terpakai' => $usage,
                'sisa_anggaran' => $anggaranAwal - $usage,
                'is_aktif' => 1,
                'created_at' => now(),
                'modified_at' => now(),
                'modified_by' => auth()->id(),
            ];

            if (! $existing) {
                SdiaAnggaranBulanan::create($payload);
            } elseif ((float) $existing->anggaran_terpakai !== $usage
                || (float) $existing->sisa_anggaran !== ($anggaranAwal - $usage)
                || $existing->id_anggaran != $idAnggaran
                || (float) $existing->anggaran_awal !== (float) $anggaranAwal) {
                SdiaAnggaranBulanan::whereKey($existing->Id)->update([
                    'anggaran_terpakai' => $usage,
                    'sisa_anggaran' => $anggaranAwal - $usage,
                    'id_anggaran' => $idAnggaran,
                    'anggaran_awal' => $anggaranAwal,
                    'modified_at' => now(),
                    'modified_by' => auth()->id(),
                ]);
            }
        }
    }

    private function validateAnggaran(Request $request): array
    {
        return $request->validate([
            'id_sdia_kegiatan' => 'required|integer',
            'rek_klas' => 'required|integer',
            'anggaran' => 'required|numeric|min:0',
        ]);
    }

    private function validateDpa(Request $request): array
    {
        return $request->validate([
            'tahun' => 'required|integer',
            'id_sdia_kegiatan' => 'required|integer',
            'rek_klas' => 'required|integer',
            'nama_barang' => 'required|string|max:255',
            'jumlah_barang' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:50',
        ]);
    }

    private function validateTransaksi(Request $request): array
    {
        return $request->validate([
            'tgl_transaksi' => 'required|date',
            'klas_transaksi' => 'required|in:Barang Masuk,Barang Keluar',
            'id_sdia_data_dpa' => 'required|integer',
            'jumlah_transaksi' => 'required|numeric|min:0',
            'harga_satuan' => 'required|numeric|min:0',
        ]);
    }

    private function anggaranMasterData(): array
    {
        $bidang = $this->scopeBidangId();

        return [
            'kegiatan' => SdiaKegiatan::query()->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))->where('is_aktif', 1)->get(),
            'klasifikasi' => SdiaKlasPersediaan::where('is_aktif', 1)->orderBy('rek_klas')->get(),
        ];
    }

    private function dpaMasterData(): array
    {
        $bidang = $this->scopeBidangId();

        return [
            'kegiatan' => SdiaKegiatan::query()->when($bidang !== null, fn ($q) => $q->where('id_katkit_bidang', $bidang))->where('is_aktif', 1)->get(),
            'klasifikasi' => SdiaKlasPersediaan::where('is_aktif', 1)->orderBy('rek_klas')->get(),
        ];
    }
}
