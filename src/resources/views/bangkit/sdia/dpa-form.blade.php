@extends('bangkit.partials.header')

@section('title', $dpa ? 'Ubah DPA' : 'Tambah DPA')

@section('content')
    <h2 class="fw-bold mb-4">{{ $dpa ? 'Ubah DPA' : 'Tambah DPA' }}</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ $dpa ? route('bangkit.sdia.update-dpa', $dpa->Id) : route('bangkit.sdia.store-dpa') }}" method="POST" class="row g-3">
                @csrf
                @if ($dpa) @method('PUT') @endif
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Tahun</label>
                    <input required type="number" name="tahun" class="form-control" value="{{ old('tahun', $dpa->tahun ?? date('Y')) }}">
                </div>
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Kegiatan</label>
                    <select name="id_sdia_kegiatan" class="form-select" required>
                        @foreach ($kegiatan as $k)
                            <option value="{{ $k->Id }}" @selected((int) old('id_sdia_kegiatan', $dpa->id_sdia_kegiatan ?? 0) === $k->Id)>{{ $k->nama_kegiatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="fw-semibold mb-2">Rekening Klasifikasi</label>
                    <select name="rek_klas" class="form-select" required>
                        @foreach ($klasifikasi as $kl)
                            <option value="{{ $kl->Id }}" @selected((int) old('rek_klas', $dpa->rek_klas ?? 0) === $kl->Id)>{{ $kl->rek_klas }} — {{ $kl->nama_klas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Nama Barang</label>
                    <input required type="text" name="nama_barang" class="form-control" value="{{ old('nama_barang', $dpa->nama_barang ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Jumlah Barang</label>
                    <input required type="number" step="0.01" name="jumlah_barang" class="form-control" value="{{ old('jumlah_barang', $dpa->jumlah_barang ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Satuan</label>
                    <input required type="text" name="satuan" class="form-control" value="{{ old('satuan', $dpa->satuan ?? '') }}">
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <a href="{{ route('bangkit.sdia.dpa') }}" class="btn btn-danger">Kembali</a>
                    <button class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
