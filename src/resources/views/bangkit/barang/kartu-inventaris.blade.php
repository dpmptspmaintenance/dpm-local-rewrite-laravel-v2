@extends('bangkit.partials.header')

@section('title', 'Kartu Inventaris Ruangan')

@section('content')
    <h2 class="fw-bold mb-4">Kartu Inventaris Ruangan</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('bangkit.barang.aksi-kartu-inventaris') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="fw-semibold mb-2">Pilih Lokasi / Ruangan</label>
                    <select name="lokasi" class="form-select" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach ($lokasi as $lok)
                            <option value="{{ $lok->Id }}">{{ $lok->lokasi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning w-100">Tampilkan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
