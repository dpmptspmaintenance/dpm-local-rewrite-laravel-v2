@php
    // Partial info barang (dipakai form permohonan / list / verifikasi).
@endphp
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Data Barang</h5>
        <div class="row">
            <div class="col-md-4">
                <ul class="list-unstyled small">
                    <li><b>Kode:</b> {{ $barang->kode_barang }}</li>
                    <li><b>Register:</b> {{ $barang->register }}</li>
                    <li><b>Kode Register:</b> {{ $barang->kode_barang_register }}</li>
                    <li><b>Tanggal Tambah:</b> {{ optional($barang->created_at)->format('d-m-Y H:i') }}</li>
                    <li><b>Oleh:</b> {{ $barang->modified_by ?? $barang->created_by }}</li>
                </ul>
            </div>
            <div class="col-md-4">
                <ul class="list-unstyled small">
                    <li><b>Nama Barang:</b> {{ $barang->nama_barang }}</li>
                    <li><b>Merk / Tipe:</b> {{ $barang->merk_type }}</li>
                    <li><b>Jenis:</b> {{ $barang->jenis?->jenis_barang }}</li>
                    <li><b>Bahan:</b> {{ $barang->bahanBarang?->bahan }}</li>
                    <li><b>Tahun Pembelian:</b> {{ $barang->tahun_pembelian }}</li>
                </ul>
            </div>
            <div class="col-md-4">
                <ul class="list-unstyled small">
                    <li><b>Harga:</b> Rp {{ number_format($barang->harga, 0, ',', '.') }}</li>
                    <li><b>Keadaan:</b> {{ $barang->keadaan?->keadaan_barang }}</li>
                    <li><b>Lokasi:</b> {{ $barang->lokasiBarang?->lokasi }}</li>
                    <li><b>Pemegang:</b> {{ $barang->pemegangPegawai?->nama }}</li>
                    <li><b>Keterangan:</b> {{ $barang->keterangan }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>
