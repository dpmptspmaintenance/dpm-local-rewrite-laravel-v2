@extends('bangkit.partials.header')

@section('title', $barang ? 'Ubah Barang' : 'Tambah Barang')

@section('content')
    <h2 class="fw-bold mb-4">{{ $barang ? 'Ubah Barang' : 'Tambah Barang' }}</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ $barang ? route('bangkit.barang.update', $barang->Id) : route('bangkit.barang.store') }}" method="POST" class="row g-3">
                @csrf
                @if ($barang) @method('PUT') @endif

                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Nama Barang</label>
                    <input required type="text" class="form-control" name="nama_barang" value="{{ old('nama_barang', $barang->nama_barang ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Merk dan Tipe</label>
                    <input type="text" class="form-control" name="merk_type" value="{{ old('merk_type', $barang->merk_type ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Kode Barang</label>
                    <input required type="text" class="form-control" name="kode_barang" value="{{ old('kode_barang', $barang->kode_barang ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Register Barang</label>
                    <input required type="text" class="form-control" name="register" value="{{ old('register', $barang->register ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Kode Barang + Register</label>
                    <input required type="text" class="form-control" name="kode_barang_register" value="{{ old('kode_barang_register', $barang->kode_barang_register ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Jenis Barang</label>
                    <select name="Jenis" class="form-select" required>
                        @foreach ($jenis_barang as $j)
                            <option value="{{ $j->Id }}" @selected((int) old('Jenis', $barang->Jenis ?? 0) === $j->Id)>{{ $j->jenis_barang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Bahan Barang</label>
                    <select name="bahan" class="form-select" required>
                        @foreach ($bahan_barang as $b)
                            <option value="{{ $b->Id }}" @selected((int) old('bahan', $barang->bahan ?? 0) === $b->Id)>{{ $b->bahan }}{{ $b->bahan_bakar ? ', Bahan Bakar '.$b->bahan_bakar : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Keadaan Barang</label>
                    <select name="keadaan_barang" class="form-select" required>
                        @foreach ($keadaan_barang as $k)
                            <option value="{{ $k->Id }}" @selected((int) old('keadaan_barang', $barang->keadaan_barang ?? 0) === $k->Id)>{{ $k->keadaan_barang }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Lokasi Barang</label>
                    <select name="lokasi" class="form-select" required>
                        @foreach ($lokasi as $l)
                            <option value="{{ $l->Id }}" @selected((int) old('lokasi', $barang->lokasi ?? 0) === $l->Id)>{{ $l->lokasi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Pemegang Barang</label>
                    <select name="pemegang" class="form-select" required>
                        @foreach ($pemegang_barang as $p)
                            <option value="{{ $p->Id }}" @selected((int) old('pemegang', $barang->pemegang ?? 0) === $p->Id)>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Tahun Pembelian</label>
                    <input required type="text" maxlength="4" class="form-control" name="tahun_pembelian" value="{{ old('tahun_pembelian', $barang->tahun_pembelian ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Harga Beli</label>
                    <input required type="number" step="0.01" class="form-control" name="harga" value="{{ old('harga', $barang->harga ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Keterangan</label>
                    <input type="text" class="form-control" name="keterangan" value="{{ old('keterangan', $barang->keterangan ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Link Foto</label>
                    <input type="text" class="form-control" name="link_foto" value="{{ old('link_foto', $barang->link_foto ?? '') }}">
                </div>

                <div class="col-12 d-flex justify-content-between mt-3">
                    <button type="button" onclick="history.back()" class="btn btn-danger">Kembali</button>
                    <button type="submit" class="btn btn-success">{{ $barang ? 'Simpan Perubahan' : 'Tambah Barang' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
