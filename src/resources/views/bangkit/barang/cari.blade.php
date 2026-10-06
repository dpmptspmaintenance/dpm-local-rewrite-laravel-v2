@extends('bangkit.partials.header')

@section('title', 'Cari Barang')

@section('content')
    <h2 class="fw-bold mb-4">Cari Barang</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold">Cari Barang</h5>
            <form action="{{ route('bangkit.barang.aksi-cari') }}" method="POST" class="mt-3">
                @csrf
                <input required type="text" class="form-control" name="search_query" placeholder="Masukan nama barang / merk / tahun" value="{{ old('search_query') }}">
                <button class="btn btn-warning mt-3"><i class="bi bi-search me-1"></i> Cari</button>
            </form>
        </div>
    </div>
@endsection
