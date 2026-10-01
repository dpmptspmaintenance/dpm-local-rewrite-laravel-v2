@extends('persediaan.partials.header')

@section('title', 'Input Stok Opname Fleksibel')

@section('content')
    <style>
        .table-scroll {
            max-height: 55vh;
            overflow-y: auto;
        }

        .table-scroll th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 2;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        }

        .bg-row-diff {
            background-color: #fff3cd !important;
        }
    </style>

    <form method="POST" action="{{ route('persediaan.stok-opname.store') }}" id="formOpname">
        @csrf
        <input type="hidden" name="status_simpan" id="status_simpan" value="draft">

        <div class="card shadow-sm border-0 mb-3 rounded-4 border-top border-success border-3">
            <div class="card-body bg-light rounded-4">
                <h6 class="fw-bold mb-3 text-success border-bottom border-success-subtle pb-2"><i class="bi bi-clipboard-check me-2"></i>Header Stok Opname Insidental / Fleksibel</h6>

                <div class="row align-items-end g-2 mb-2">
                    <div class="col-md-3">
                        <label class="small text-muted fw-bold">Tanggal Perhitungan Opname</label>
                        <input type="date" name="tanggal_opname" class="form-control form-control-sm" value="{{ old('tanggal_opname', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="small text-muted fw-bold">Nama / Topik Kegiatan Audit Opname</label>
                        <input type="text" name="nama_kegiatan" class="form-control form-control-sm" value="{{ old('nama_kegiatan') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold">Ketua Tim Pemeriksa (Saksi)</label>
                        <input type="text" name="ttd_kanan" class="form-control form-control-sm" value="{{ old('ttd_kanan') }}" required>
                    </div>
                </div>

                <div class="row align-items-end g-2">
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold">Sekretaris Dinas (Menyetujui)</label>
                        <input type="text" name="ttd_sekretaris" class="form-control form-control-sm" value="{{ old('ttd_sekretaris', 'Anton Siswartono, S.Sos, M.M') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold">Pengurus Barang Persediaan</label>
                        <input type="text" name="ttd_kiri" class="form-control form-control-sm" value="{{ old('ttd_kiri', 'Naelu Shulhal Majid, A.Md.Ak') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold">Ka. Subbag Keuangan</label>
                        <input type="text" name="ttd_tengah" class="form-control form-control-sm" value="{{ old('ttd_tengah', 'Nany Marlina, SE') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold text-success"><i class="bi bi-boxes me-2"></i>Lembar Audit Hasil Perhitungan Fisik Gudang</span>
                <div class="w-25">
                    <input type="text" class="form-control form-control-sm" placeholder="Ketik cari uraian barang..." onkeyup="filterOpnameTable(this.value)">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-scroll">
                    <table class="table table-hover align-middle mb-0" id="tblOpname">
                        <thead class="text-muted small text-center">
                            <tr>
                                <th class="ps-4" style="width: 5%;">No</th>
                                <th class="text-start">Nama Uraian Barang</th>
                                <th style="width: 10%;">Satuan</th>
                                <th class="text-end" style="width: 14%;">Harga Batch</th>
                                <th style="width: 10%;">Stok Sistem</th>
                                <th style="width: 12%;">Stok Fisik (Riil)</th>
                                <th style="width: 10%;">Selisih</th>
                                <th class="pe-4" style="width: 20%;">Alasan / Keterangan Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($snapshot as $item)
                                <tr class="row-opname">
                                    <td class="ps-4 text-center text-muted small">{{ $loop->iteration }}</td>
                                    <td class="fw-bold tbl-name">
                                        <input type="hidden" name="id_barang[]" value="{{ $item->id_barang }}">
                                        <input type="hidden" name="harga_satuan[]" value="{{ $item->harga_satuan }}">
                                        <input type="hidden" name="stok_sistem[]" class="stk-sistem" value="{{ $item->sisa_stok }}">
                                        {{ $item->nama_barang }}
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $item->nama_satuan }}</span></td>
                                    <td class="text-end text-muted small">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="text-center fw-bold text-primary fs-6">{{ $item->sisa_stok }}</td>
                                    <td class="text-center">
                                        <input type="number" name="stok_fisik[]" class="form-control form-control-sm text-center fw-bold text-success stk-fisik" value="{{ $item->sisa_stok }}" min="0" oninput="hitungSelisih(this)" required>
                                    </td>
                                    <td class="text-center fw-bold fs-6 val-selisih">0</td>
                                    <td class="pe-4">
                                        <input type="text" name="alasan_selisih[]" class="form-control form-control-sm txt-alasan" placeholder="Isi jika ada selisih..." readonly>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Tidak ada saldo stok aktif gudang untuk di-opname.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-3 rounded-bottom-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-info-circle me-1"></i>Pilih <b>Terbitkan Final</b> jika ingin stok sistem otomatis disesuaikan.</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" onclick="submitOpname('draft')"><i class="bi bi-file-earmark-arrow-down me-1"></i> Simpan Draft</button>
                    <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" onclick="submitOpname('final')"><i class="bi bi-check2-all me-1"></i> Terbitkan &amp; Finalisasi Opname</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function hitungSelisih(input) {
        let tr = input.closest('tr');
        let stkSistem = parseInt(tr.querySelector('.stk-sistem').value) || 0;
        let stkFisik = parseInt(input.value) || 0;
        let selisih = stkFisik - stkSistem;

        let tdSelisih = tr.querySelector('.val-selisih');
        let inputAlasan = tr.querySelector('.txt-alasan');

        tdSelisih.innerText = (selisih > 0 ? '+' : '') + selisih;

        if (selisih !== 0) {
            tr.classList.add('bg-row-diff');
            tdSelisih.className = 'text-center fw-bold fs-6 val-selisih ' + (selisih < 0 ? 'text-danger' : 'text-warning');
            inputAlasan.readOnly = false;
        } else {
            tr.classList.remove('bg-row-diff');
            tdSelisih.className = 'text-center fw-bold fs-6 val-selisih text-muted';
            inputAlasan.readOnly = true;
        }
    }

    function filterOpnameTable(filterValue) {
        let filter = filterValue.toUpperCase();
        let tr = document.getElementById('tblOpname').getElementsByClassName('row-opname');
        for (let i = 0; i < tr.length; i++) {
            let td = tr[i].getElementsByClassName('tbl-name')[0];
            if (td) tr[i].style.display = (td.textContent || td.innerText).toUpperCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }

    function submitOpname(status) {
        let msg = status === 'final'
            ? 'Terbitkan & finalisasi dokumen Stok Opname ini? Stok sistem akan OTOMATIS disesuaikan sesuai selisih hasil perhitungan fisik!'
            : 'Simpan dokumen Stok Opname ini sebagai Draft?';
        if (!confirm(msg)) return;
        document.getElementById('status_simpan').value = status;
        document.getElementById('formOpname').submit();
    }
</script>
@endpush
