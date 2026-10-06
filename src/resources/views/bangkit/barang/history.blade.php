@extends('bangkit.partials.header')

@section('title', 'History Pemegang Barang')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">History Pemegang Barang</h2>
        <a href="{{ route('bangkit.barang.cari') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h5 class="fw-bold mb-2">{{ $barang->nama_barang }}</h5>
            <div class="small text-muted">Kode Register: {{ $barang->kode_barang_register }} — Merk/Tipe: {{ $barang->merk_type }}</div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr><th>No</th><th>Pemegang Lama</th><th>Pemegang Baru</th><th>Tanggal</th></tr>
                </thead>
                <tbody>
                    @forelse ($history as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ \App\Models\Bangkit\PegawaiKekuatan::find($row->pemegang_lama)?->nama ?? $row->pemegang_lama ?? '-' }}</td>
                            <td>{{ \App\Models\Bangkit\PegawaiKekuatan::find($row->pemegang_baru)?->nama ?? $row->pemegang_baru ?? '-' }}</td>
                            <td>{{ optional($row->created_at)->format('d-m-Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat perubahan pemegang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
