@extends('bangkit.partials.header')

@section('title', 'Data DPA')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data DPA</h2>
        <a href="{{ route('bangkit.sdia.tambah-dpa') }}" class="btn btn-success btn-sm">Tambah DPA</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('bangkit.sdia.dpa') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Tahun</label>
                    <select name="tahun" class="form-select">
                        <option value="all">Semua Tahun</option>
                        @for ($y = (int) date('Y') + 1; $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected((string) $tahun === (string) $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Klasifikasi</label>
                    <select name="klasifikasi" class="form-select">
                        <option value="all">Semua Klasifikasi</option>
                        @foreach (\App\Models\Bangkit\SdiaKlasPersediaan::where('is_aktif',1)->orderBy('rek_klas')->get() as $kl)
                            <option value="{{ $kl->Id }}" @selected((string) $klasifikasi === (string) $kl->Id)>{{ $kl->rek_klas }} — {{ $kl->nama_klas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-warning">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Tahun</th><th>Kegiatan</th><th>Rek. Klas</th><th>Nama Barang</th><th>Jumlah</th><th>Satuan</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($dpa as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->tahun }}</td>
                                <td>{{ $row->kegiatan?->nama_kegiatan }}</td>
                                <td>{{ $row->klasifikasi?->rek_klas }}</td>
                                <td>{{ $row->nama_barang }}</td>
                                <td>{{ $row->jumlah_barang }}</td>
                                <td>{{ $row->satuan }}</td>
                                <td class="text-center">
                                    <a href="{{ route('bangkit.sdia.ubah-dpa', $row->Id) }}" class="btn btn-sm btn-primary">Ubah</a>
                                    @if ($row->used_in_transaksi)
                                        <button class="btn btn-sm btn-danger" disabled title="Sedang digunakan">Hapus</button>
                                    @else
                                        <form action="{{ route('bangkit.sdia.delete-dpa', $row->Id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Pilih tahun/klasifikasi lalu Tampilkan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
