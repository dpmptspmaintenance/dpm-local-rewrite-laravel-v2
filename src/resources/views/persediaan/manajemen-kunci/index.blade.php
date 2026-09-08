@extends('persediaan.partials.header')

@section('title', 'Manajemen Kunci Laporan')

@section('content')
    @php
        $bulanNama = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    @endphp

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-danger"><i class="bi bi-lock-fill me-2"></i>Penguncian Laporan Bulanan</h5>
            <form method="GET" class="d-flex align-items-center" action="{{ route('persediaan.manajemen-kunci.index') }}">
                <label class="me-2 fw-bold">Tahun:</label>
                <input type="number" name="tahun" value="{{ $tahun }}" class="form-control form-control-sm w-auto" onchange="this.form.submit()">
            </form>
        </div>
        <div class="card-body">
            <div class="alert alert-warning small py-2"><i class="bi bi-info-circle me-1"></i> Bulan yang <b>terkunci</b> tidak akan bisa menerima input atau edit transaksi baru dari BPP. Hanya Admin yang bisa mengeditnya.</div>
            <div class="row mt-4">
                @foreach ($bulanNama as $i => $nama)
                    @php $isLocked = (int) ($kunci[$i] ?? 0) === 1; @endphp
                    <div class="col-md-3 mb-3">
                        <div class="card {{ $isLocked ? 'bg-danger bg-opacity-10 border-danger' : 'bg-success bg-opacity-10 border-success' }}">
                            <div class="card-body text-center">
                                <h5 class="fw-bold">{{ $nama }}</h5>
                                <p class="mb-3 small fw-bold {{ $isLocked ? 'text-danger' : 'text-success' }}">
                                    @if ($isLocked)
                                        <i class="bi bi-lock-fill"></i> Terkunci
                                    @else
                                        <i class="bi bi-unlock-fill"></i> Terbuka
                                    @endif
                                </p>
                                <form method="POST" action="{{ route('persediaan.manajemen-kunci.toggle') }}">
                                    @csrf
                                    <input type="hidden" name="bulan" value="{{ $i }}">
                                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                                    <input type="hidden" name="status_baru" value="{{ $isLocked ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-sm w-100 {{ $isLocked ? 'btn-outline-danger' : 'btn-primary' }}">
                                        {{ $isLocked ? 'Buka Akses' : 'Kunci Bulan Ini' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
