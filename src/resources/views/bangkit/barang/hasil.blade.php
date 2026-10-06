@extends('bangkit.partials.header')

@section('title', 'Hasil Cari Barang')

@php
    $isAdmin = (int) (auth()->user()->role ?? 0) === 1 || (int) (auth()->user()->is_admin_bangkit ?? 0) === 1;
    $role = (int) (auth()->user()->role ?? 0);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Hasil Cari Barang</h2>
        <a href="{{ route('bangkit.barang.cari') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Cari lagi</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Hasil untuk: “{{ $q }}” ({{ $barang->count() }} data)</h5>
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Identitas</th>
                            <th>Nama Barang</th>
                            <th>Atribut</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($barang as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <div class="small"><b>Kode:</b> {{ $row->kode_barang }}</div>
                                    <div class="small"><b>Register:</b> {{ $row->register }}</div>
                                    <div class="small"><b>Kode Register:</b> {{ $row->kode_barang_register }}</div>
                                </td>
                                <td>
                                    <div class="small"><b>Nama:</b> {{ $row->nama_barang }}</div>
                                    <div class="small"><b>Merk/Tipe:</b> {{ $row->merk_type }}</div>
                                    <div class="small"><b>Jenis:</b> {{ $row->jenis?->jenis_barang }}</div>
                                    <div class="small"><b>Bahan:</b> {{ $row->bahanBarang?->bahan }}</div>
                                    <div class="small"><b>Tahun:</b> {{ $row->tahun_pembelian }}</div>
                                </td>
                                <td>
                                    <div class="small"><b>Harga:</b> Rp {{ number_format($row->harga, 0, ',', '.') }}</div>
                                    <div class="small"><b>Keadaan:</b> {{ $row->keadaan?->keadaan_barang }}</div>
                                    <div class="small"><b>Lokasi:</b> {{ $row->lokasiBarang?->lokasi }}</div>
                                    <div class="small"><b>Pemegang:</b> {{ $row->pemegangPegawai?->nama }}</div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column gap-2">
                                        <a href="{{ route('bangkit.permohonan.form', $row->Id) }}" class="btn btn-success btn-sm">Perbaikan</a>
                                        <a href="{{ route('bangkit.permohonan.list', $row->Id) }}" class="btn btn-primary btn-sm">List</a>
                                        @if ($isAdmin)
                                            <a href="{{ route('bangkit.barang.history', $row->Id) }}" class="btn btn-secondary btn-sm">History</a>
                                            <a href="{{ route('bangkit.barang.ubah', $row->Id) }}" class="btn btn-warning btn-sm">Ubah</a>
                                            <form action="{{ route('bangkit.barang.hapus', $row->Id) }}" method="POST" onsubmit="return confirm('Yakin hapus barang ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-danger btn-sm w-100">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada barang ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>$(function(){ @if($barang->count() > 10) $('#dataTable').DataTable({"pageLength":10,"language":{"search":"Cari:","lengthMenu":"Tampilkan _MENU_ data","info":"Menampilkan _START_-_END_ dari _TOTAL_","paginate":{"first":"Pertama","last":"Terakhir","next":"Berikutnya","previous":"Sebelumnya"}}}); @endif });</script>
@endpush
