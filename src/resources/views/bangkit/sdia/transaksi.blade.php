@extends('bangkit.partials.header')

@section('title', 'Transaksi Barang')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Transaksi Barang</h2>
        <a href="{{ route('bangkit.sdia.tambah-transaksi') }}" class="btn btn-success btn-sm">Tambah Transaksi</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Tanggal</th><th>Klas</th><th>Barang (DPA)</th><th>Jumlah</th><th>Harga Satuan</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($transaksi as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ optional($row->tgl_transaksi)->format('d-m-Y') }}</td>
                                <td><span class="badge bg-{{ $row->klas_transaksi === 'Barang Masuk' ? 'success' : 'danger' }}">{{ $row->klas_transaksi }}</span></td>
                                <td>{{ $row->dpa?->nama_barang }}</td>
                                <td>{{ $row->jumlah_transaksi }}</td>
                                <td>Rp {{ number_format($row->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-center"><a href="{{ route('bangkit.sdia.ubah-transaksi', $row->Id) }}" class="btn btn-sm btn-primary">Ubah</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Tidak ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')<script>$(function(){@if($transaksi->count()>10)$('#dataTable').DataTable({"pageLength":10});@endif});</script>@endpush
