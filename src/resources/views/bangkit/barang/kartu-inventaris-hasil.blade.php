@extends('bangkit.partials.header')

@section('title', 'Hasil Kartu Inventaris Ruangan')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Kartu Inventaris Ruangan</h2>
        <a href="{{ route('bangkit.barang.kartu-inventaris') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Ganti ruangan</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Lokasi: {{ $lokasi->lokasi ?? '-' }} ({{ $barang->count() }} barang)</h5>
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode Register</th>
                            <th>Nama Barang</th>
                            <th>Merk/Tipe</th>
                            <th>Jenis</th>
                            <th>Keadaan</th>
                            <th>Pemegang</th>
                            <th>Tahun</th>
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($barang as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->kode_barang_register }}</td>
                                <td>{{ $row->nama_barang }}</td>
                                <td>{{ $row->merk_type }}</td>
                                <td>{{ $row->jenis?->jenis_barang }}</td>
                                <td>{{ $row->keadaan?->keadaan_barang }}</td>
                                <td>{{ $row->pemegangPegawai?->nama }}</td>
                                <td>{{ $row->tahun_pembelian }}</td>
                                <td>Rp {{ number_format($row->harga, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">Tidak ada barang di lokasi ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>$(function(){ @if($barang->count() > 10) $('#dataTable').DataTable({"pageLength":10}); @endif });</script>
@endpush
