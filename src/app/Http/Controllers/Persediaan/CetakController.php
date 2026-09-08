<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Persediaan\Concerns\ComputesLaporan;
use App\Models\Persediaan\StokOpnameDetail;
use App\Models\Persediaan\StokOpnameHeader;
use App\Models\Persediaan\TransaksiHeader;
use Illuminate\Http\Request;

class CetakController extends Controller
{
    use ComputesLaporan;

    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const HARI_NAMA = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
    ];

    /** Extract to a shared helper — legacy duplicated this in cetak_bon.php + cetak_baso.php. */
    public static function tglIndo(string $tanggal): string
    {
        [$tahun, $bulan, $hari] = explode('-', $tanggal);

        return ((int) $hari).' '.self::BULAN_NAMA[(int) $bulan].' '.$tahun;
    }

    /** Kop-surat logo: local assets/img/logo_pemkot.png if present, else remote URL (legacy logic). */
    public static function logoSource(): string
    {
        $path = public_path('assets/img/logo_pemkot.png');
        if (is_file($path) && filesize($path) > 0) {
            return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
        }

        return 'https://semarangkota.go.id/assets/img/logo-pemkot.png';
    }

    /** BAST (masuk/saldo_awal). */
    public function bast(int $id)
    {
        $header = TransaksiHeader::query()
            ->where('id', $id)
            ->whereIn('jenis_mutasi', ['masuk', 'saldo_awal'])
            ->firstOrFail();

        abort_unless($header, 404, 'Data BAST Tidak Ditemukan / Bukan Transaksi Masuk!');

        $details = $header->details()
            ->join('master_barang as b', 'transaksi_detail.id_barang', '=', 'b.id')
            ->join('master_satuan as s', 'b.id_satuan', '=', 's.id')
            ->select('transaksi_detail.*', 'b.nama_barang', 'b.kode_rekening', 's.nama_satuan')
            ->orderBy('b.nama_barang')
            ->get();

        $ts = $header->tanggal_transaksi;
        $dateParts = [
            'hari' => self::HARI_NAMA[$ts->format('l')] ?? $ts->format('l'),
            'tgl' => (int) $ts->format('j'),
            'bulan' => self::BULAN_NAMA[(int) $ts->format('n')],
            'tahun' => $ts->format('Y'),
        ];

        return view('persediaan.cetak.bast', [
            'header' => $header,
            'details' => $details,
            'logoSrc' => self::logoSource(),
            'd' => $dateParts,
        ]);
    }

    /** Bon keluar. */
    public function bon(int $id)
    {
        $header = TransaksiHeader::query()
            ->where('id', $id)
            ->where('jenis_mutasi', 'keluar')
            ->firstOrFail();

        abort_unless($header, 404, 'Dokumen Bon Permintaan Barang tidak ditemukan!');

        $details = $header->details()
            ->join('master_barang as b', 'transaksi_detail.id_barang', '=', 'b.id')
            ->join('master_satuan as s', 'b.id_satuan', '=', 's.id')
            ->select('transaksi_detail.*', 'b.nama_barang', 's.nama_satuan')
            ->orderBy('b.nama_barang')
            ->get();

        return view('persediaan.cetak.bon', [
            'header' => $header,
            'details' => $details,
        ]);
    }

    /** BASO (stok opname). */
    public function baso(int $id)
    {
        $opname = StokOpnameHeader::findOrFail($id);
        abort_unless($opname, 404, 'Dokumen Berita Acara Stok Opname tidak ditemukan!');

        $details = StokOpnameDetail::query()
            ->where('id_opname_header', $opname->id)
            ->join('master_barang as b', 'stok_opname_detail.id_barang', '=', 'b.id')
            ->join('master_satuan as s', 'b.id_satuan', '=', 's.id')
            ->select('stok_opname_detail.*', 'b.nama_barang', 's.nama_satuan')
            ->orderBy('b.nama_barang')
            ->get();

        return view('persediaan.cetak.baso', [
            'header' => $opname,
            'details' => $details,
        ]);
    }

    /** Print-view of the canonical laporan (reconciled to laporan.php semantics). */
    public function laporan(Request $request)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];
        $blocks = $this->laporanBlocks($bulan, $tahun);

        return view('persediaan.cetak.laporan', [
            'blocks' => $blocks,
            'periode' => self::BULAN_NAMA[$bulan].' '.$tahun,
        ]);
    }
}
