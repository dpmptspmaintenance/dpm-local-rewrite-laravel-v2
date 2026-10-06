@extends('bangkit.partials.header')

@section('title', 'Beranda Bangkit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Beranda Bangkit</h2>
        <span class="text-muted">Barang Kita &amp; SDIA Persediaan</span>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Barang</div>
                    <div class="fs-3 fw-bold">{{ number_format($stats['total_barang']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Barang Saya</div>
                    <div class="fs-3 fw-bold">{{ number_format($stats['barang_saya']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Permohonan Perbaikan</div>
                    <div class="fs-3 fw-bold">{{ number_format($stats['permohonan_aktif']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Perbaikan Selesai</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($stats['permohonan_selesai']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body">
            <h5 class="fw-bold">Mulai</h5>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('bangkit.barang.cari') }}" class="btn btn-warning"><i class="bi bi-search me-1"></i> Cari Barang</a>
                <a href="{{ route('bangkit.barang.data-saya') }}" class="btn btn-outline-dark"><i class="bi bi-archive me-1"></i> Barang Saya</a>
                <a href="{{ route('bangkit.sdia.bulanan') }}" class="btn btn-outline-dark"><i class="bi bi-cash-stack me-1"></i> SDIA Bulanan</a>
            </div>
        </div>
    </div>
@endsection
