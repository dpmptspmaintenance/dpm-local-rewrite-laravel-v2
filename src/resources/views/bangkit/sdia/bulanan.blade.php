@extends('bangkit.partials.header')

@section('title', 'SDIA Bulanan')

@section('content')
    <h2 class="fw-bold mb-4">SDIA Bulanan</h2>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('bangkit.sdia.bulanan') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Tahun</label>
                    <select name="tahun" class="form-select">
                        @for ($y = (int) date('Y') + 1; $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected((string) $tahun === (string) $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Bulan</label>
                    <select name="bulan" class="form-select">
                        @foreach ($bulanList as $n => $nm)
                            <option value="{{ $n }}" @selected((string) $bulan === (string) $n)>{{ $nm }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-warning">Tampilkan</button>
                    <a href="{{ route('bangkit.sdia.clear-filter') }}" class="btn btn-outline-secondary">Bersihkan</a>
                    @if ($tahun && $bulan)
                        <form action="{{ route('bangkit.sdia.hitung-ulang') }}" method="POST" class="d-inline" onsubmit="return confirm('Hitung ulang SDIA dari transaksi?')">
                            @csrf
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <input type="hidden" name="bulan" value="{{ $bulan }}">
                            <button class="btn btn-success">Hitung Ulang</button>
                        </form>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @forelse ($sdia as $namaKegiatan => $klasList)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="fw-bold">{{ $namaKegiatan }}</h5>
                @foreach ($klasList as $rekKlas => $grup)
                    <div class="mt-3">
                        <div class="d-flex flex-wrap gap-3 mb-2 small">
                            <span class="badge bg-secondary">Klas {{ $grup['nama_klas'] ?? $rekKlas }}</span>
                            <span class="text-muted">Anggaran Awal: Rp {{ number_format((float) $grup['anggaran_awal'], 0, ',', '.') }}</span>
                            <span class="text-muted">Terpakai: Rp {{ number_format((float) $grup['anggaran_terpakai'], 0, ',', '.') }}</span>
                            <span class="text-success">Sisa: Rp {{ number_format((float) $grup['sisa_anggaran'], 0, ',', '.') }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead class="table-light">
                                    <tr><th>Nama Barang</th><th>Satuan</th><th>Saldo Awal</th><th>Masuk</th><th>Harga Satuan</th><th>Keluar</th><th>Saldo Akhir</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($grup['items'] as $item)
                                        <tr>
                                            <td>{{ $item['nama_barang'] }}</td>
                                            <td>{{ $item['satuan'] }}</td>
                                            <td>{{ $item['saldo_awal'] }}</td>
                                            <td>{{ $item['saldo_masuk'] }}</td>
                                            <td>Rp {{ number_format((float) $item['harga_satuan'], 0, ',', '.') }}</td>
                                            <td>{{ $item['saldo_keluar'] }}</td>
                                            <td>{{ $item['saldo_akhir'] }}</td>
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
        <div class="alert alert-info">Pilih tahun &amp; bulan, lalu tekan Tampilkan. Kalau belum ada data, tekan Hitung Ulang untuk menghitung dari transaksi.</div>
    @endforelse
@endsection
