<?php

namespace App\Http\Controllers\Persediaan\Concerns;

use App\Models\Persediaan\PenguncianLaporan;
use App\Models\Persediaan\StokOpnameHeader;
use App\Models\Persediaan\TransaksiDetail;
use App\Models\Persediaan\TransaksiHeader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared ledger queries that legacy duplicated across transaksi_bon.php,
 * riwayat_dokumen.php, stok_opname.php and riwayat_opname.php.
 *
 * All "stock level" numbers come from approved (status='disetujui') rows in
 * transaksi_header/transaksi_detail grouped per (id_barang, harga_satuan) —
 * i.e. FIFO-style purchase batches. There is no stock table.
 */
trait ComputesStock
{
    /**
     * Remaining quantity per (item, price-batch), approved transactions only.
     * Exhausted batches (HAVING sisa_stok > 0) are dropped.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function stockBatches(): Collection
    {
        return DB::connection('persediaan')
            ->table('transaksi_detail as d')
            ->join('transaksi_header as h', 'd.id_header', '=', 'h.id')
            ->join('master_barang as b', 'd.id_barang', '=', 'b.id')
            ->join('master_satuan as s', 'b.id_satuan', '=', 's.id')
            ->select([
                'b.id as id_barang',
                'b.nama_barang',
                's.nama_satuan',
                'd.harga_satuan',
                DB::raw("SUM(CASE WHEN h.jenis_mutasi IN ('saldo_awal','masuk') THEN d.qty ELSE -d.qty END) as sisa_stok"),
            ])
            ->where('h.status', 'disetujui')
            ->groupBy('d.id_barang', 'd.harga_satuan', 'b.id', 'b.nama_barang', 's.nama_satuan')
            ->havingRaw('sisa_stok > 0')
            ->orderBy('b.nama_barang')
            ->orderBy('d.harga_satuan')
            ->get()
            ->map(fn ($r) => (object) [
                'id_barang' => (int) $r->id_barang,
                'nama_barang' => $r->nama_barang,
                'nama_satuan' => $r->nama_satuan,
                'harga_satuan' => (float) $r->harga_satuan,
                'sisa_stok' => (int) $r->sisa_stok,
            ]);
    }

    /**
     * Month-lock check. Admins always bypass (matches legacy isBulanDikunci()).
     * $tanggal is a Y-m-d string.
     */
    public function bulanDikunci(string $tanggal, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return false;
        }

        $date = explode('-', $tanggal);
        if (count($date) !== 3) {
            return true; // malformed date — treat as locked, refuse write
        }

        $thn = (int) $date[0];
        $bln = (int) $date[1];

        $lock = PenguncianLaporan::query()
            ->where('tahun', $thn)
            ->where('bulan', $bln)
            ->first();

        return (bool) ($lock->is_locked ?? false);
    }

    /**
     * Create ADJ-IN / ADJ-OUT transactions in transaksi_header/detail for an
     * opname detail set being finalized. Matches the legacy automation exactly:
     * same kode_transaksi prefixes, hardcoded pihak_terkait literal, and direct
     * status='disetujui' (auto-approved). Runs inside the caller's transaction.
     *
     * $selisihRows carry SIGNED selisih: qty > 0 → physical surplus (ADJ-IN),
     * qty < 0 → shortage (ADJ-OUT). Values reused verbatim are the source rows.
     *
     * @param  iterable<object{id_barang:int,qty:int,harga:float}>  $selisihRows  signed selisih rows
     * @param  array{ttd_kiri?:string,ttd_tengah?:string,ttd_kanan?:string}  $signatures
     */
    public function applyOpnameAdjustments(iterable $selisihRows, string $tanggal, array $signatures, int $userId): void
    {
        $rows = collect($selisihRows);

        $adjMasuk = $rows->filter(fn ($r) => (int) $r->qty > 0);
        if ($adjMasuk->isNotEmpty()) {
            $this->writeAdjustment(
                'ADJ-IN-', 'masuk', 'Penyesuaian Opname (Lebih)',
                $tanggal, $signatures, $userId, $adjMasuk->values()
            );
        }

        $adjKeluar = $rows->filter(fn ($r) => (int) $r->qty < 0);
        if ($adjKeluar->isNotEmpty()) {
            $this->writeAdjustment(
                'ADJ-OUT-', 'keluar', 'Penyesuaian Opname (Kurang)',
                $tanggal, $signatures, $userId, $adjKeluar->map(fn ($r) => (object) [
                    'id_barang' => $r->id_barang,
                    'qty' => abs((int) $r->qty),
                    'harga' => $r->harga,
                ])->values()
            );
        }
    }

    private function writeAdjustment(string $prefix, string $jenis, string $pihak, string $tanggal, array $signatures, int $userId, iterable $items): void
    {
        $kode = $prefix.date('YmdHis');

        $header = TransaksiHeader::create([
            'kode_transaksi' => $kode,
            'jenis_mutasi' => $jenis,
            'tanggal_transaksi' => $tanggal,
            'pihak_terkait' => $pihak,
            'ttd_kiri' => $signatures['ttd_kiri'] ?? null,
            'ttd_tengah' => $signatures['ttd_tengah'] ?? null,
            'ttd_kanan' => $signatures['ttd_kanan'] ?? null,
            'status' => 'disetujui',
            'created_by' => $userId,
        ]);

        foreach ($items as $item) {
            TransaksiDetail::create([
                'id_header' => $header->id,
                'id_barang' => (int) $item->id_barang,
                'qty' => (int) $item->qty,
                'harga_satuan' => (float) $item->harga,
            ]);
        }
    }
}
