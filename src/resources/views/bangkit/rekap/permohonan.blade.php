@extends('bangkit.partials.header')

@section('title', 'Rekap Permohonan per Jenis Barang')

@section('content')
    <h2 class="fw-bold mb-4">Rekap Permohonan per Jenis Barang</h2>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form action="{{ route('bangkit.rekap.aksi-permohonan') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Bulan</label>
                    <select name="filter_bulan" class="form-select">
                        @foreach ([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $n => $nm)
                            <option value="{{ $n }}" @selected((int) $bulan === $n)>{{ $nm }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Jenis Barang</label>
                    <select name="jenis_barang" class="form-select">
                        @foreach ($jenis_barang as $j)
                            <option value="{{ $j->Id }}">{{ $j->jenis_barang }}</option>
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
            <h5 class="fw-bold mb-3">Hasil ({{ $list->count() }} data)</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Barang</th><th>Kerusakan</th><th>Biaya</th></tr></thead>
                    <tbody>
                        @forelse ($list as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="small">{{ $d->barang?->nama_barang }}</td>
                                <td class="small">{{ $d->uraian_kerusakan }}</td>
                                <td class="small">Rp {{ number_format((float) $d->biaya, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
