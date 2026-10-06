@extends('bangkit.partials.header')

@section('title', 'Form Permohonan Perbaikan')

@section('content')
    <h2 class="fw-bold mb-4">Form Permohonan Perbaikan Barang</h2>

    <div class="mb-3">
        @include('bangkit.permohonan.partials.info-barang', ['barang' => $barang])
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Form Permohonan</h5>
            <form action="{{ route('bangkit.permohonan.store', $barang->Id) }}" method="POST">
                @csrf
                <input type="hidden" name="kode_barang_register" value="{{ $barang->kode_barang_register }}">
                <label class="fw-semibold mb-2">Keterangan Kerusakan Barang</label>
                <textarea class="form-control" name="keterangan_kerusakan" rows="4" placeholder="Masukan keterangan kerusakan barang dengan lengkap">{{ old('keterangan_kerusakan') }}</textarea>
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" onclick="history.back()" class="btn btn-danger">Kembali</button>
                    <button type="submit" class="btn btn-success">Ajukan Permohonan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
