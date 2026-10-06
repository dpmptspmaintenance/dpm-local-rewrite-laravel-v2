@extends('bangkit.partials.header')

@section('title', 'Rekap Permohonan Perbulan')

@section('content')
    <h2 class="fw-bold mb-4">Rekap Permohonan Perbulan</h2>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form action="{{ route('bangkit.rekap.aksi-filter-bulan') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Pilih Bulan</label>
                    <select name="bulan" class="form-select">
                        @foreach ([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $n => $nm)
                            <option value="{{ $n }}" @selected((int) $bulan === $n)>{{ $nm }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-warning">Tampilkan</button>
                    <a href="{{ route('bangkit.rekap.print-perbulan', $bulan) }}" target="_blank" class="btn btn-outline-dark">Cetak</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Permohonan Bulan ke-{{ $bulan }} ({{ $list->count() }} data)</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr><th>No</th><th>Barang</th><th>Kerusakan</th><th>Biaya</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="small">{{ $d->barang?->nama_barang }} <br><span class="text-muted">{{ $d->barang?->merk_type }}</span></td>
                                <td class="small">{{ $d->uraian_kerusakan }}</td>
                                <td class="small">Rp {{ number_format((float) $d->biaya, 0, ',', '.') }}</td>
                                <td class="small"><span class="badge bg-{{ \App\Models\Bangkit\PermohonanPerbaikan::warnaVerifikasi($d->verifikasi_b_barang) }}">{{ \App\Models\Bangkit\PermohonanPerbaikan::labelVerifikasi($d->verifikasi_b_barang) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
