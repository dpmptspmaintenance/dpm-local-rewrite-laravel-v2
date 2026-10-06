@extends('bangkit.partials.header')

@section('title', 'Daftar Kegiatan SDIA')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Daftar Kegiatan SDIA</h2>
        <a href="{{ route('bangkit.sdia.tambah-kegiatan') }}" class="btn btn-success btn-sm">Tambah Kegiatan</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Tahun</th><th>Nama Kegiatan</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($kegiatan as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->tahun }}</td>
                                <td>{{ $row->nama_kegiatan }}</td>
                                <td class="text-center"><a href="{{ route('bangkit.sdia.ubah-kegiatan', $row->Id) }}" class="btn btn-sm btn-primary">Ubah</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada kegiatan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')<script>$(function(){@if($kegiatan->count()>10)$('#dataTable').DataTable({"pageLength":10});@endif});</script>@endpush
