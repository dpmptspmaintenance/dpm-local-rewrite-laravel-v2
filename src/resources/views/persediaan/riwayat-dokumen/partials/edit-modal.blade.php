@php
    $row = $doc['row'];
    $isMasuk = $jenis === 'masuk';
@endphp
<div class="modal fade" id="editModal{{ $row->id }}" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-light px-4">
                <h5 class="modal-title fw-bold {{ $isMasuk ? 'text-primary' : 'text-danger' }}">
                    {{ $isMasuk ? 'Perbaiki / Edit Dokumen Surat BAST' : 'Edit Dokumen Bon Pengeluaran' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('persediaan.riwayat-dokumen.edit') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id_header" value="{{ $row->id }}">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        @if ($isMasuk)
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold mb-1">Tanggal Transaksi</label>
                                <input type="date" name="tanggal_transaksi" class="form-control form-control-sm rounded-2" value="{{ $row->tanggal_transaksi->format('Y-m-d') }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">No. BAST Resmi</label>
                                <input type="text" name="no_bukti" class="form-control form-control-sm rounded-2" value="{{ $row->no_bukti }}" placeholder="No Nota Fisik">
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold mb-1">Vendor / Rekanan</label>
                                <input type="text" name="pihak_terkait" class="form-control form-control-sm rounded-2" value="{{ $row->pihak_terkait }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Penyerah (Vendor)</label>
                                <input type="text" name="ttd_kiri" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_kiri }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold mb-1">Penerima (Pengurus Barang)</label>
                                <input type="text" name="ttd_kanan" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_kanan }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Kasubbag Keuangan</label>
                                <input type="text" name="ttd_tengah" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_tengah }}">
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Tanggal Bon</label>
                                <input type="date" name="tanggal_transaksi" class="form-control form-control-sm rounded-2" value="{{ $row->tanggal_transaksi->format('Y-m-d') }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Bidang / Peminta</label>
                                <input type="text" name="pihak_terkait" class="form-control form-control-sm rounded-2" value="{{ $row->pihak_terkait }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Alasan Pengambilan Barang</label>
                                <input type="text" name="alasan_pengambilan" class="form-control form-control-sm rounded-2" value="{{ $row->alasan_pengambilan }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Sekretaris Dinas (Menyetujui)</label>
                                <input type="text" name="ttd_sekretaris" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_sekretaris ?: 'Anton Siswartono, S.Sos, M.M' }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Pengurus Barang</label>
                                <input type="text" name="ttd_kiri" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_kiri }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Ka Subbag Keuangan</label>
                                <input type="text" name="ttd_tengah" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_tengah }}" required>
                                <label class="small text-muted fw-bold mb-1 mt-2">Nama Peminta (Kanan)</label>
                                <input type="text" name="ttd_kanan" class="form-control form-control-sm rounded-2" value="{{ $row->ttd_kanan }}" required>
                            </div>
                        @endif
                    </div>

                    <div class="row g-3 bg-light p-3 rounded-3 mb-3 border mx-0">
                        <div class="col-md-6 border-end pe-md-4">
                            <label class="small text-dark fw-bold mb-2"><i class="bi bi-paperclip me-1 {{ $isMasuk ? 'text-primary' : 'text-danger' }}"></i>Lampiran Saat Ini (Klik X Untuk Hapus)</label>
                            @if ($doc['files']->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($doc['files'] as $f)
                                        <div class="badge bg-white text-dark border p-2 d-flex align-items-center gap-2 rounded-2 shadow-sm">
                                            <input type="hidden" name="existing_files[]" value="{{ $f['name'] }}">
                                            <span class="text-truncate fw-semibold" style="max-width: 160px;">{{ $f['name'] }}</span>
                                            <button type="button" class="btn btn-link link-danger p-0 border-0 lh-1" onclick="this.closest('div').remove()"><i class="bi bi-x-circle-fill"></i></button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="small text-muted mb-0">Tidak ada lampiran.</p>
                            @endif
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <label class="small text-dark fw-bold mb-2"><i class="bi bi-cloud-arrow-up me-1 text-success"></i>{{ $isMasuk ? 'Tambahkan File Baru' : 'Upload Bon Baru' }} (Maks 5MB per file)</label>
                            <input type="file" name="edit_lampiran_berkas[]" class="form-control form-control-sm rounded-2 bg-white" accept="image/*,application/pdf" multiple>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end border-bottom pb-2 mt-2 mb-2">
                        <h6 class="fw-bold m-0 {{ $isMasuk ? 'text-secondary' : 'text-danger' }}"><i class="bi bi-box-seam me-1"></i>{{ $isMasuk ? 'Rincian Komoditas Barang' : 'Pengaturan Barang Gudang' }}</h6>
                        <button type="button" class="btn btn-sm {{ $isMasuk ? 'btn-success' : 'btn-danger' }} rounded-pill px-3" onclick="{{ $isMasuk ? 'addRowMasuk' : 'addRowKeluar' }}({{ $row->id }})"><i class="bi bi-plus-circle me-1"></i> Tambah Item</button>
                    </div>

                    <table class="table table-sm align-middle text-center mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="text-start ps-2">Nama Uraian Barang</th>
                                <th style="width: 90px;">Qty</th>
                                <th style="width: 140px;">Harga Satuan (Rp)</th>
                                <th style="width: 50px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_{{ $jenis }}_{{ $row->id }}">
                            @php $totalEditAwal = 0; @endphp
                            @foreach ($doc['details'] as $dtl)
                                @php $totalEditAwal += $dtl->qty * $dtl->harga_satuan; @endphp
                                <tr>
                                    <td class="text-start">
                                        <input type="hidden" name="id_detail[]" value="{{ $dtl->id }}">
                                        <small class="fw-bold text-dark">{{ $dtl->barang->nama_barang ?? '-' }}</small>
                                    </td>
                                    <td><input type="number" name="qty_detail[]" class="form-control form-control-sm text-center py-0" value="{{ $dtl->qty }}" min="1" oninput="hitungTotalEdit({{ $row->id }})"></td>
                                    <td><input type="text" inputmode="numeric" name="harga_detail[]" class="form-control form-control-sm text-end py-0 {{ $isMasuk ? 'text-dark fw-bold' : 'text-muted' }}" value="{{ number_format((float) $dtl->harga_satuan, 0, ',', '.') }}" {{ $isMasuk ? '' : 'readonly' }} oninput="hitungTotalEdit({{ $row->id }})"></td>
                                    <td><button type="button" class="btn btn-sm p-0 border-0 text-danger" onclick="this.closest('tr').remove(); hitungTotalEdit({{ $row->id }});"><i class="bi bi-trash3 fs-5"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold border-top">
                                <td colspan="2" class="text-end py-2 text-dark small">{{ $isMasuk ? 'Total Akumulasi Berkas Edit:' : 'Total Nilai Bon Edit:' }}</td>
                                <td class="text-end {{ $isMasuk ? 'text-success' : 'text-danger' }} pe-2 py-2 fs-6" id="total_edit_text_{{ $row->id }}">Rp {{ number_format($totalEditAwal, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-secondary fw-semibold rounded-2 px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm {{ $isMasuk ? 'btn-primary' : 'btn-danger' }} fw-bold rounded-2 px-4">{{ $isMasuk ? 'Simpan Perubahan' : 'Simpan Perubahan Bon' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
