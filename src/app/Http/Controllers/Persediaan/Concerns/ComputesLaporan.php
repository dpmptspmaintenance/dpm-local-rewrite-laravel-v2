<?php

namespace App\Http\Controllers\Persediaan\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Canonical "Laporan Persediaan Global" aggregation (per-rekening stock book).
 *
 * NOTE (reconciliation, per plan): legacy laporan.php and cetak_laporan.php each
 * had their OWN query for the same report, with divergent qty_awal logic. This
 * adopts laporan.php's semantics (saldo_awal rows always count toward the "awal"
 * balance regardless of their date; masuk/keluar before the period start offset
 * it), and is used by the AJAX rows, the server export AND the cetak_laporan
 * print view so all three always agree.
 */
trait ComputesLaporan
{
    /**
     * Raw per-(rekening, item, price-batch) rows for the period.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function laporanPeriodRows(int $bulan, int $tahun): \Illuminate\Support\Collection
    {
        $startDate = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = DB::connection('persediaan')
            ->table('transaksi_detail as d')
            ->join('transaksi_header as h', 'd.id_header', '=', 'h.id')
            ->join('master_barang as b', 'd.id_barang', '=', 'b.id')
            ->join('master_satuan as s', 'b.id_satuan', '=', 's.id')
            ->join('master_rekening as r', 'b.kode_rekening', '=', 'r.kode_rekening')
            ->selectRaw("
                r.kode_rekening, r.nama_rekening,
                b.id as id_barang, b.nama_barang, s.nama_satuan, d.harga_satuan,
                SUM(CASE
                    WHEN h.tanggal_transaksi <= ? AND h.jenis_mutasi = 'saldo_awal' THEN d.qty
                    WHEN h.tanggal_transaksi < ? AND h.jenis_mutasi = 'masuk' THEN d.qty
                    WHEN h.tanggal_transaksi < ? AND h.jenis_mutasi = 'keluar' THEN -d.qty
                    ELSE 0
                END) as qty_awal,
                SUM(CASE
                    WHEN h.tanggal_transaksi BETWEEN ? AND ? AND h.jenis_mutasi = 'masuk' THEN d.qty
                    ELSE 0
                END) as qty_masuk,
                SUM(CASE
                    WHEN h.tanggal_transaksi BETWEEN ? AND ? AND h.jenis_mutasi = 'keluar' THEN d.qty
                    ELSE 0
                END) as qty_keluar
            ", [$endDate, $startDate, $startDate, $startDate, $endDate, $startDate, $endDate])
            ->groupBy('r.kode_rekening', 'r.nama_rekening', 'b.id', 'b.nama_barang', 's.nama_satuan', 'd.harga_satuan')
            ->havingRaw('qty_awal > 0 OR qty_masuk > 0 OR qty_keluar > 0')
            ->orderBy('r.kode_rekening')
            ->orderBy('b.nama_barang')
            ->orderBy('d.harga_satuan')
            ->get()
            ->map(fn ($r) => (object) [
                'kode_rekening' => $r->kode_rekening,
                'nama_rekening' => $r->nama_rekening,
                'id_barang' => (int) $r->id_barang,
                'nama_barang' => $r->nama_barang,
                'nama_satuan' => $r->nama_satuan,
                'harga_satuan' => (float) $r->harga_satuan,
                'qty_awal' => (int) $r->qty_awal,
                'qty_masuk' => (int) $r->qty_masuk,
                'qty_keluar' => (int) $r->qty_keluar,
                'qty_akhir' => (int) $r->qty_awal + (int) $r->qty_masuk - (int) $r->qty_keluar,
            ]);

        return $rows;
    }

    /**
     * Ordered render model for the screen/print/export: 'rek' block opens a
     * rekening group, 'item' rows follow, 'sub' closes each group, 'total' ends.
     *
     * @return array<int, array<string, mixed>>
     */
    public function laporanBlocks(int $bulan, int $tahun): array
    {
        $rows = $this->laporanPeriodRows($bulan, $tahun);

        $blocks = [];
        $lastRek = null;
        $no = 1;

        $sub = ['awal' => 0, 'masuk' => 0, 'keluar' => 0, 'akhir' => 0];
        $grand = ['awal' => 0, 'masuk' => 0, 'keluar' => 0, 'akhir' => 0];

        foreach ($rows as $item) {
            $saldo = [
                'awal' => $item->qty_awal * $item->harga_satuan,
                'masuk' => $item->qty_masuk * $item->harga_satuan,
                'keluar' => $item->qty_keluar * $item->harga_satuan,
                'akhir' => $item->qty_akhir * $item->harga_satuan,
            ];

            if ($lastRek !== null && $lastRek !== $item->kode_rekening) {
                $blocks[] = ['type' => 'sub', 'kode' => $lastRek, 'sub' => $sub];
                $sub = ['awal' => 0, 'masuk' => 0, 'keluar' => 0, 'akhir' => 0];
                $no = 1;
            }

            if ($lastRek !== $item->kode_rekening) {
                $blocks[] = ['type' => 'rek', 'kode' => $item->kode_rekening, 'nama' => $item->nama_rekening];
                $lastRek = $item->kode_rekening;
            }

            $sub['awal'] += $saldo['awal'];
            $sub['masuk'] += $saldo['masuk'];
            $sub['keluar'] += $saldo['keluar'];
            $sub['akhir'] += $saldo['akhir'];

            $grand['awal'] += $saldo['awal'];
            $grand['masuk'] += $saldo['masuk'];
            $grand['keluar'] += $saldo['keluar'];
            $grand['akhir'] += $saldo['akhir'];

            $blocks[] = [
                'type' => 'item',
                'no' => $no++,
                'item' => $item,
                'saldo' => $saldo,
            ];
        }

        if ($lastRek !== null) {
            $blocks[] = ['type' => 'sub', 'kode' => $lastRek, 'sub' => $sub];
        }

        $blocks[] = ['type' => 'total', 'grand' => $grand];

        return $blocks;
    }
}
