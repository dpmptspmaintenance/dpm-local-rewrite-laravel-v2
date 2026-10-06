@extends('bangkit.partials.header')

@section('title', $kegiatan ? 'Ubah Kegiatan' : 'Tambah Kegiatan')

@section('content')
    <h2 class="fw-bold mb-4">{{ $kegiatan ? 'Ubah Kegiatan' : 'Tambah Kegiatan' }}</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ $kegiatan ? route('bangkit.sdia.update-kegiatan', $kegiatan->Id) : route('bangkit.sdia.store-kegiatan') }}" method="POST" class="row g-3">
                @csrf
                @if ($kegiatan) @method('PUT') @endif
                <div class="col-md-4">
                    <label class="fw-semibold mb-2">Tahun</label>
                    <input required type="number" name="tahun" class="form-control" value="{{ old('tahun', $kegiatan->tahun ?? date('Y')) }}">
                </div>
                <div class="col-md-8">
                    <label class="fw-semibold mb-2">Nama Kegiatan</label>
                    <input required type="text" name="nama_kegiatan" class="form-control" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan ?? '') }}">
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <a href="{{ route('bangkit.sdia.kegiatan') }}" class="btn btn-danger">Kembali</a>
                    <button class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
