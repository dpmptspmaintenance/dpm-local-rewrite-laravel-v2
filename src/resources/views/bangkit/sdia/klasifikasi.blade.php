@extends('bangkit.partials.header')

@section('title', 'Klasifikasi Persediaan')

@section('content')
    <h2 class="fw-bold mb-4">Daftar Klasifikasi Persediaan</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light"><tr><th>No</th><th>No Klasifikasi</th><th>Nama Klasifikasi</th></tr></thead>
                    <tbody>
                        @forelse ($klasifikasi as $i => $row)
                            <tr><td>{{ $i + 1 }}</td><td>{{ $row->rek_klas }}</td><td>{{ $row->nama_klas }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Tidak ada klasifikasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
