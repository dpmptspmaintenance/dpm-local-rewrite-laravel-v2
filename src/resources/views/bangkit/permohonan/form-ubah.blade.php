@extends('bangkit.partials.header')

@section('title', 'Ubah Permohonan Perbaikan')

@section('content')
    <h2 class="fw-bold mb-4">Ubah Permohonan Perbaikan</h2>

    <div class="mb-3">
        @include('bangkit.permohonan.partials.info-barang', ['barang' => $barang])
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Form Ubah Permohonan</h5>
            <form action="{{ route('bangkit.permohonan.update', $permohonan->Id) }}" method="POST">
                @csrf @method('PUT')
                <label class="fw-semibold mb-2">Keterangan Kerusakan Barang</label>
                <textarea class="form-control" name="keterangan_kerusakan" rows="4">{{ old('keterangan_kerusakan', $permohonan->uraian_kerusakan) }}</textarea>
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" onclick="history.back()" class="btn btn-danger">Kembali</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
