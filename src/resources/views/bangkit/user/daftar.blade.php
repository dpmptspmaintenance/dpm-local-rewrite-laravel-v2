@extends('bangkit.partials.header')

@section('title', 'Daftar User Bangkit')

@section('content')
    <h2 class="fw-bold mb-4">Daftar User Bangkit</h2>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('bangkit.user.daftar') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="" @selected($status === null || $status === '')>Semua Status</option>
                        <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Bidang</label>
                    <select name="bidang" class="form-select" onchange="this.form.submit()">
                        <option value="" @selected($bidang === null || $bidang === '')>Semua Bidang</option>
                        <option value="__kosong__" @selected($bidang === '__kosong__')>(Belum ada bidang)</option>
                        @foreach ($bidangOptions as $b)
                            <option value="{{ $b }}" @selected($bidang === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <button class="btn btn-warning">Tampilkan</button>
                    @if ($status || $bidang)
                        <a href="{{ route('bangkit.user.daftar') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                    <span class="text-muted ms-2 small">{{ $users->count() }} user</span>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr><th>No</th><th>Nama</th><th>Email</th><th>Bidang</th><th>Role</th><th class="text-center">Admin</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->email }}</td>
                                <td>{{ $row->bidang ?? '-' }}</td>
                                <td>{{ $roles[$row->role] ?? ('Role '.$row->role) }}</td>
                                <td class="text-center">
                                    @if ((int) $row->role === 1 || (int) $row->is_admin_bangkit === 1)
                                        <span class="badge bg-success">Admin</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('bangkit.user.toggle', $row->id) }}" method="POST">
                                        @csrf
                                        @if ($row->is_aktif)
                                            <button class="btn btn-sm btn-success">Aktif</button>
                                        @else
                                            <button class="btn btn-sm btn-danger">Nonaktif</button>
                                        @endif
                                    </form>
                                </td>
                                <td class="text-center"><a href="{{ route('bangkit.user.ubah', $row->id) }}" class="btn btn-sm btn-primary">Ubah</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Tidak ada user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')<script>$(function(){@if($users->count()>10)$('#dataTable').DataTable({"pageLength":10});@endif});</script>@endpush
