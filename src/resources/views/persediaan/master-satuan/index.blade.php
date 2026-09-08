@extends('persediaan.partials.header')

@section('title', 'Master Satuan')

@section('content')
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white fw-bold">Tambah Satuan</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('persediaan.master-satuan.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Satuan</label>
                            <input type="text" name="nama_satuan" class="form-control form-control-sm" placeholder="Contoh: Lusin, Rim, Box" value="{{ old('nama_satuan') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-plus-circle me-1"></i> Simpan Satuan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-rulers me-2 text-primary"></i> Data Master Satuan</span>
                    <span class="badge bg-primary rounded-pill">{{ $satuan->count() }} Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 60px;">No</th>
                                    <th>Nama Satuan</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($satuan as $i => $row)
                                    <tr>
                                        <td class="ps-4 text-muted small">{{ $i + 1 }}</td>
                                        <td class="fw-bold text-dark">{{ $row->nama_satuan }}</td>
                                        <td>
                                            @if ($row->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Aktif</span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">Non-Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <form method="POST" action="{{ route('persediaan.master-satuan.toggle-status', $row->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $row->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="Ubah Status">
                                                    <i class="bi {{ $row->is_active ? 'bi-x-circle' : 'bi-check-circle' }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal Edit -->
                                    <div class="modal fade" id="editModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h6 class="modal-title fw-bold text-primary">Edit Satuan</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('persediaan.master-satuan.update', $row->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <label class="form-label text-muted small">Nama Satuan</label>
                                                        <input type="text" name="nama_satuan" class="form-control form-control-sm" value="{{ $row->nama_satuan }}" required>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>Belum ada data satuan.</td>
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
