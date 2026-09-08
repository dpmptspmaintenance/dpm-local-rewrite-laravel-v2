@extends('persediaan.partials.header')

@section('title', 'Master Barang')

@section('content')
    @php
        // grouped by kode_rekening with category header; prices display format
        $fmt = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    @endphp
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white fw-bold">Tambah Barang</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('persediaan.master-barang.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted small">Rekening</label>
                            <select name="kode_rekening" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Rekening --</option>
                                @foreach ($rekening as $r)
                                    <option value="{{ $r->kode_rekening }}">{{ $r->kode_rekening }} - {{ $r->nama_rekening }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control form-control-sm" value="{{ old('nama_barang') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Satuan</label>
                            <select name="id_satuan" class="form-select form-select-sm" required>
                                <option value="">-- Pilih Satuan --</option>
                                @foreach ($satuan as $s)
                                    <option value="{{ $s->id }}">{{ $s->nama_satuan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Harga Satuan (Rp)</label>
                            <input type="number" name="harga_satuan" class="form-control form-control-sm text-end" value="{{ old('harga_satuan', 0) }}" min="0" step="any" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-plus-circle me-1"></i> Simpan Barang</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2 text-primary"></i> Data Master Barang</span>
                    <span class="badge bg-primary rounded-pill">{{ $barangGrouped->flatten()->count() }} Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">Kode Rekening</th>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end pe-4" style="width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($barangGrouped as $kodeRek => $items)
                                    <tr>
                                        <td colspan="5" class="ps-4 bg-secondary bg-opacity-10 fw-bold text-dark">
                                            <i class="bi bi-folder2-open me-2"></i>{{ $kodeRek }} — {{ $items->first()->rekening->nama_rekening ?? '-' }}
                                        </td>
                                    </tr>
                                    @foreach ($items as $row)
                                        <tr>
                                            <td class="ps-4"><span class="badge bg-light text-dark border">{{ $row->kode_rekening }}</span></td>
                                            <td class="fw-bold text-dark">{{ $row->nama_barang }}</td>
                                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $row->satuan->nama_satuan ?? '-' }}</span></td>
                                            <td class="text-end text-success fw-bold">{{ $fmt($row->harga_satuan) }}</td>
                                            <td class="text-end pe-4">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="editModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h6 class="modal-title fw-bold text-primary">Edit Barang</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('persediaan.master-barang.update', $row->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label text-muted small">Rekening</label>
                                                                    <select name="kode_rekening" class="form-select form-select-sm" required>
                                                                        @foreach ($rekening as $r)
                                                                            <option value="{{ $r->kode_rekening }}" @selected($row->kode_rekening === $r->kode_rekening)>{{ $r->kode_rekening }} - {{ $r->nama_rekening }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label text-muted small">Nama Barang</label>
                                                                    <input type="text" name="nama_barang" class="form-control form-control-sm" value="{{ $row->nama_barang }}" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label text-muted small">Satuan</label>
                                                                    <select name="id_satuan" class="form-select form-select-sm" required>
                                                                        @foreach ($satuan as $s)
                                                                            <option value="{{ $s->id }}" @selected($row->id_satuan === $s->id)>{{ $s->nama_satuan }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label text-muted small">Harga Satuan (Rp)</label>
                                                                    <input type="number" name="harga_satuan" class="form-control form-control-sm text-end" value="{{ (int) $row->harga_satuan }}" min="0" step="any" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>Belum ada data barang.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
