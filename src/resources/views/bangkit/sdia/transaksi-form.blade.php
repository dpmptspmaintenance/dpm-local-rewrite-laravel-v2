@extends('bangkit.partials.header')

@section('title', $transaksi ? 'Ubah Transaksi' : 'Tambah Transaksi')

@section('content')
    <h2 class="fw-bold mb-4">{{ $transaksi ? 'Ubah Transaksi' : 'Tambah Transaksi' }}</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ $transaksi ? route('bangkit.sdia.update-transaksi', $transaksi->Id) : route('bangkit.sdia.store-transaksi') }}" method="POST" class="row g-3">
                @csrf
                @if ($transaksi) @method('PUT') @endif
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Tanggal Transaksi</label>
                    <input required type="date" name="tgl_transaksi" class="form-control" value="{{ old('tgl_transaksi', optional($transaksi->tgl_transaksi ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Klasifikasi Transaksi</label>
                    <select name="klas_transaksi" class="form-select" required>
                        <option value="Barang Masuk" @selected(old('klas_transaksi', $transaksi->klas_transaksi ?? '') === 'Barang Masuk')>Barang Masuk</option>
                        <option value="Barang Keluar" @selected(old('klas_transaksi', $transaksi->klas_transaksi ?? '') === 'Barang Keluar')>Barang Keluar</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Barang (DPA)</label>
                    <select name="id_sdia_data_dpa" class="form-select" required>
                        @foreach ($dpa as $d)
                            <option value="{{ $d->Id }}" @selected((int) old('id_sdia_data_dpa', $transaksi->id_sdia_data_dpa ?? 0) === $d->Id)>{{ $d->nama_barang }} ({{ $d->satuan }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Jumlah Transaksi</label>
                    <input required type="number" step="0.01" name="jumlah_transaksi" class="form-control" value="{{ old('jumlah_transaksi', $transaksi->jumlah_transaksi ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Harga Satuan</label>
                    <input required type="number" step="0.01" name="harga_satuan" class="form-control" value="{{ old('harga_satuan', $transaksi->harga_satuan ?? '') }}">
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <a href="{{ route('bangkit.sdia.transaksi') }}" class="btn btn-danger">Kembali</a>
                    <button class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
