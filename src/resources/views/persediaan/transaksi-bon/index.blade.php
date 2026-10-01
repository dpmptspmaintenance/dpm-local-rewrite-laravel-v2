@extends('persediaan.partials.header')

@section('title', 'Input Bon Permintaan Barang')

@section('content')
    <style>
        .table-scroll {
            max-height: 52vh;
            overflow-y: auto;
        }

        .table-scroll th {
            background-color: #f8f9fa;
            z-index: 2;
            position: sticky;
            top: 0;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        }

        .row-active-danger {
            background-color: #fce8e8 !important;
        }
    </style>

    <form method="POST" action="{{ route('persediaan.transaksi-bon.store') }}" id="formKeluar" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin data alokasi Bon Pengeluaran barang ini sudah sesuai?')">
        @csrf
        <div class="card shadow-sm border-0 mb-3 rounded-4 border-top border-danger border-3">
            <div class="card-body bg-light rounded-4">
                <h6 class="fw-bold mb-3 text-danger border-bottom border-danger-subtle pb-2"><i class="bi bi-box-arrow-up-right me-2"></i>Header Permintaan Bon Pengeluaran</h6>

                <!-- BARIS 1: INFORMASI PEMINTA & KEPERLUAN -->
                <div class="row align-items-end g-2 mb-2">
                    <div class="col-md-2">
                        <label class="small text-muted fw-bold">Tanggal Permintaan</label>
                        <input type="date" name="tanggal_transaksi" class="form-control form-control-sm" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Nama Pegawai Peminta</label>
                        <select name="ttd_kanan" id="peminta_keluar" class="form-select form-select-sm" onchange="setBidang()" required>
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach ($pegawai as $p)
                                <option value="{{ $p->nama }}" data-bidang="{{ $p->bidang }}" @selected(old('ttd_kanan') === $p->nama)>
                                    {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted fw-bold">Bidang / Sub-Bagian</label>
                        <input type="text" name="pihak_terkait" id="bidang_keluar" class="form-control form-control-sm" placeholder="Otomatis..." value="{{ old('pihak_terkait') }}" required readonly style="background-color: #e9ecef;">
                    </div>
                    <!-- PENAMBAHAN FIELD ALASAN PENGAMBILAN BARANG -->
                    <div class="col-md-5">
                        <label class="small text-muted fw-bold">Alasan / Keperluan Pengambilan Barang</label>
                        <input type="text" name="alasan_pengambilan" class="form-control form-control-sm" placeholder="Contoh: Operasional Rapat / Kegiatan Sosialisasi Dinas" value="{{ old('alasan_pengambilan') }}" required>
                    </div>
                </div>

                <!-- BARIS 2: TANDA TANGAN & UPLOAD -->
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Sekretaris Dinas (ACC)</label>
                        <input type="text" name="ttd_sekretaris" class="form-control form-control-sm" value="{{ old('ttd_sekretaris', 'Anton Siswartono, S.Sos, M.M') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold">Pengurus Barang & Keuangan</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="ttd_kiri" class="form-control" value="{{ old('ttd_kiri', 'Naelu Shulhal Majid, A.Md.Ak') }}" title="Pengurus Barang">
                            <input type="text" name="ttd_tengah" class="form-control" value="{{ old('ttd_tengah', 'Nany Marlina, SE') }}" title="Ka. Subbag Keuangan">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold text-dark"><i class="bi bi-paperclip me-1"></i>Upload Bukti Bon Fisik Resmi (Maks 5MB)</label>
                        <input type="file" name="lampiran_berkas[]" class="form-control form-control-sm border-danger" accept="image/*,application/pdf" multiple required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold text-danger"><i class="bi bi-box-seam me-2"></i>Daftar Saldo Sisa Stok Gudang Aktif</span>
                <div class="w-25">
                    <input type="text" class="form-control form-control-sm" id="searchKeluar" placeholder="Ketik cari nama barang..." onkeyup="filterTable('tblKeluar', this.value)">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-scroll">
                    <table class="table table-hover align-middle mb-0" id="tblKeluar">
                        <thead class="text-muted small">
                            <tr>
                                <th class="ps-4" style="width: 5%;">No</th>
                                <th>Nama Uraian Barang</th>
                                <th class="text-center" style="width: 12%;">Satuan</th>
                                <th class="text-end" style="width: 15%;">Harga Batch</th>
                                <th class="text-center" style="width: 12%;">Sisa Stok</th>
                                <th style="width: 12px;" class="pe-4 text-center">Qty Diminta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stokBatch as $i => $s)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                    <td class="fw-bold tbl-name">
                                        <input type="hidden" name="id_barang_keluar[]" value="{{ $s->id_barang }}">
                                        <input type="hidden" name="harga_keluar[]" value="{{ $s->harga_satuan }}">
                                        {{ $s->nama_barang }}
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $s->nama_satuan }}</span></td>
                                    <td class="text-end text-muted small">Rp {{ number_format($s->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="text-center fw-bold text-success fs-6">{{ $s->sisa_stok }}</td>
                                    <td class="pe-4">
                                        <input type="number" name="qty_keluar[]" class="form-control form-control-sm text-center fw-bold text-danger input-qty" min="0" max="{{ $s->sisa_stok }}" placeholder="0" onkeyup="highlightRow(this)" onchange="highlightRow(this)">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3 rounded-bottom-4">
                <button type="submit" name="submit_keluar" class="btn btn-danger px-5 fw-bold shadow-sm"><i class="bi bi-save me-2"></i> Eksekusi Simpan Bon</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function setBidang() {
        let sel = document.getElementById("peminta_keluar");
        let opt = sel.options[sel.selectedIndex];
        document.getElementById("bidang_keluar").value = opt ? (opt.getAttribute("data-bidang") || '') : '';
    }

    function highlightRow(input) {
        let tr = input.closest('tr');
        let max = parseInt(input.getAttribute('max'), 10) || 0;
        let val = parseInt(input.value, 10) || 0;

        if (val > max) {
            alert('Kuantitas yang diminta melebihi sisa stok yang tersedia (maks ' + max + ')!');
            input.value = max;
            val = max;
        }

        if (val > 0) tr.classList.add('row-active-danger');
        else tr.classList.remove('row-active-danger');
    }

    function filterTable(tableId, filterValue) {
        let filter = filterValue.toUpperCase();
        let tr = document.getElementById(tableId).getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            let td = tr[i].getElementsByClassName("tbl-name")[0];
            if (td) tr[i].style.display = (td.textContent || td.innerText).toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
</script>
@endpush
