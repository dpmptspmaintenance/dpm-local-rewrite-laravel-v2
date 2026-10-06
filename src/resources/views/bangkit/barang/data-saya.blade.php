@extends('bangkit.partials.header')

@section('title', 'Barang Saya')

@section('content')
    <h2 class="fw-bold mb-4">Barang Saya</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Daftar Barang Yang Anda Pegang</h5>
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
                                </td>
                                <td>
                                    <div class="small"><b>Keadaan:</b> {{ $row->keadaan?->keadaan_barang }}</div>
                                    <div class="small"><b>Lokasi:</b> {{ $row->lokasiBarang?->lokasi }}</div>
                                    <div class="small"><b>Tahun:</b> {{ $row->tahun_pembelian }}</div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('bangkit.permohonan.form', $row->Id) }}" class="btn btn-success btn-sm">Ajukan Perbaikan</a>
                                    <a href="{{ route('bangkit.permohonan.list', $row->Id) }}" class="btn btn-primary btn-sm mt-1">List</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada barang atas nama Anda. (Pastikan nama akun sama dengan daftar pegawai.)</td></tr>
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
