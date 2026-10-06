@extends('bangkit.partials.header')

@section('title', 'Saldo Awal')

@section('content')
    <h2 class="fw-bold mb-4">Saldo Awal Persediaan</h2>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('bangkit.sdia.saldo-awal') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Tahun</label>
                    <select name="tahun" class="form-select">
                        @foreach ($tahun_list as $y)
                            <option value="{{ $y }}" @selected((int) $tahun === (int) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-warning">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>

    @forelse ($saldo as $namaKegiatan => $klasList)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="fw-bold">{{ $namaKegiatan }}</h5>
                @foreach ($klasList as $grup)
                    <div class="mt-3">
                        <span class="badge bg-secondary mb-2">Klas {{ $grup['nama_klas'] }}</span>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead class="table-light"><tr><th>Nama Barang</th><th>Satuan</th><th>Saldo Awal</th><th>Harga Satuan</th><th>Nilai</th></tr></thead>
                                <tbody>
                                    @foreach ($grup['items'] as $item)
                                        <tr>
                                            <td>{{ $item['nama_barang'] }}</td>
                                            <td>{{ $item['satuan'] }}</td>
                                            <td>{{ $item['saldo_awal'] }}</td>
                                            <td>Rp {{ number_format((float) $item['harga_satuan'], 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format((float) $item['nilai_awal'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="alert alert-info">Tidak ada saldo awal (saldo akhir Desember {{ $tahun - 1 }} kosong).</div>
    @endforelse
@endsection
