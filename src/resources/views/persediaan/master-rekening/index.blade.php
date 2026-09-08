@extends('persediaan.partials.header')

@section('title', 'Master Rekening')

@section('content')
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white fw-bold">Tambah Rekening</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('persediaan.master-rekening.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted small">Kode Rekening</label>
                            <input type="text" name="kode_rekening" class="form-control form-control-sm" placeholder="Contoh: 1.1.12.02" value="{{ old('kode_rekening') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Rekening</label>
                            <input type="text" name="nama_rekening" class="form-control form-control-sm" value="{{ old('nama_rekening') }}" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Induk Rekening (Opsional)</label>
                            <select name="parent_kode" class="form-select form-select-sm">
                                <option value="">-- Rekening Utama / Induk --</option>
                                @foreach ($parents as $p)
                                    <option value="{{ $p->kode_rekening }}">{{ $p->kode_rekening }} - {{ $p->nama_rekening }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-plus-circle me-1"></i> Simpan Rekening</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-folder2-open me-2 text-primary"></i> Data Master Rekening</span>
                    <span class="badge bg-primary rounded-pill">{{ $rekening->count() }} Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-hover table-borderless align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">Kode Rekening</th>
                                    <th>Nama Rekening</th>
                                    <th>Hierarki</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rekening as $row)
                                    @php
                                        $isSub = !empty($row->parent_kode);
                                        $modalId = str_replace('.', '_', $row->kode_rekening);
                                    @endphp
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-light text-dark border">{{ $row->kode_rekening }}</span></td>
                                        <td class="{{ $isSub ? 'sub-rekening' : 'fw-bold' }}">
                                            @if ($isSub)
                                                <i class="bi bi-arrow-return-right text-muted me-1"></i>
                                            @endif
                                            {{ $row->nama_rekening }}
                                        </td>
                                        <td>
                                            @if (! $isSub)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">Induk</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">Sub</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Aktif</span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">Non-Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $modalId }}" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <form method="POST" action="{{ route('persediaan.master-rekening.toggle-status', $row->kode_rekening) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $row->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="Ubah Status">
                                                    <i class="bi {{ $row->is_active ? 'bi-x-circle' : 'bi-check-circle' }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal Edit -->
                                    <div class="modal fade" id="editModal{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h6 class="modal-title fw-bold text-primary">Edit Rekening</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('persediaan.master-rekening.update', $row->kode_rekening) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label text-muted small">Kode Rekening</label>
                                                            <input type="text" name="kode_rekening" class="form-control form-control-sm" value="{{ $row->kode_rekening }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label text-muted small">Nama Rekening</label>
                                                            <input type="text" name="nama_rekening" class="form-control form-control-sm" value="{{ $row->nama_rekening }}" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label text-muted small">Induk Rekening</label>
                                                            <select name="parent_kode" class="form-select form-select-sm">
                                                                <option value="">-- Rekening Utama / Induk --</option>
                                                                @foreach ($parents as $p)
                                                                    @continue($p->kode_rekening === $row->kode_rekening)
                                                                    <option value="{{ $p->kode_rekening }}" @selected($row->parent_kode === $p->kode_rekening)>{{ $p->kode_rekening }} - {{ $p->nama_rekening }}</option>
                                                                @endforeach
                                                            </select>
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
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>Belum ada data rekening.</td>
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
