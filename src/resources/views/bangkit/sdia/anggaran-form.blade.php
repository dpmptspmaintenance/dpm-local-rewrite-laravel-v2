@extends('bangkit.partials.header')

@section('title', $anggaran ? 'Ubah Anggaran' : 'Tambah Anggaran')

@section('content')
    <h2 class="fw-bold mb-4">{{ $anggaran ? 'Ubah Anggaran' : 'Tambah Anggaran' }}</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ $anggaran ? route('bangkit.sdia.update-anggaran', $anggaran->Id) : route('bangkit.sdia.store-anggaran') }}" method="POST" class="row g-3">
                @csrf
                @if ($anggaran) @method('PUT') @endif
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Kegiatan</label>
                    <select name="id_sdia_kegiatan" class="form-select" required>
                        @foreach ($kegiatan as $k)
                            <option value="{{ $k->Id }}" @selected((int) old('id_sdia_kegiatan', $anggaran->id_sdia_kegiatan ?? 0) === $k->Id)>{{ $k->tahun }} — {{ $k->nama_kegiatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Rekening Klasifikasi</label>
                    <select name="rek_klas" class="form-select" required>
                        @foreach ($klasifikasi as $kl)
                            <option value="{{ $kl->Id }}" @selected((int) old('rek_klas', $anggaran->rek_klas ?? 0) === $kl->Id)>{{ $kl->rek_klas }} — {{ $kl->nama_klas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Anggaran (Rp)</label>
                    <input required type="number" step="0.01" name="anggaran" class="form-control" value="{{ old('anggaran', $anggaran->anggaran ?? '') }}">
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <a href="{{ route('bangkit.sdia.anggaran') }}" class="btn btn-danger">Kembali</a>
                    <button class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
