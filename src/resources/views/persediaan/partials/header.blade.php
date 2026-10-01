<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Persediaan')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        html,
        body {
            font-family: "Moderustic", sans-serif;
        }

        .sub-rekening {
            padding-left: 2.5rem !important;
            color: #495057;
        }

        .navbar {
            position: relative;
            z-index: 1050 !important;
        }

        .offcanvas-body .list-group-item {
            border-left: 0;
            border-right: 0;
            border-radius: 0;
        }

        .offcanvas-body .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>

<body class="bg-light">

    @php
        $user = auth()->user();
        $isAdmin = ((int) ($user->role ?? 0) === 1 || (int) ($user->is_admin_persediaan ?? 0) === 1);
        $isBpp = (int) ($user->is_bpp ?? 0) === 1;
        $isPegawaiBiasa = !$isAdmin && !$isBpp;
    @endphp

    <!-- NAVBAR ATAS -->
    <nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-light border-0 px-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarLeft" aria-controls="sidebarLeft">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <a class="navbar-brand fw-bold mb-0" href="{{ route('persediaan.index') }}">
                    <i class="bi bi-box-seam me-2"></i> Sistem Persediaan
                </a>
            </div>
            <div class="dropdown">
                <button class="btn btn-light bg-transparent border-0 rounded-circle p-0 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px;">
                    <i class="bi bi-three-dots-vertical fs-5 text-white"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
                    <li>
                        <a class="dropdown-item py-2 small fw-semibold text-dark" href="{{ route('dashboard') }}">
                            <i class="bi bi-arrow-left-square text-primary me-2"></i>Kembali ke Portal Utama
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- LEFT SIDEBAR -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarLeft" aria-labelledby="sidebarLeftLabel">
        <div class="offcanvas-header bg-primary text-white">
            <h5 class="offcanvas-title fw-bold" id="sidebarLeftLabel"><i class="bi bi-box-seam me-2"></i> Navigasi Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">

                <a href="{{ route('persediaan.index') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('persediaan.index') ? 'active' : '' }}">
                    <i class="bi bi-house-door me-2"></i> Beranda
                </a>

                @if ($isAdmin)
                    @php $isMaster = request()->routeIs('persediaan.master-rekening.*', 'persediaan.master-satuan.*', 'persediaan.master-barang.*', 'persediaan.manajemen-kunci.*'); @endphp
                    <div class="list-group-item p-0">
                        <a class="d-flex justify-content-between align-items-center list-group-item list-group-item-action py-3 text-decoration-none {{ $isMaster ? 'bg-light fw-bold text-primary' : '' }}" data-bs-toggle="collapse" href="#menuMaster" role="button" aria-expanded="{{ $isMaster ? 'true' : 'false' }}">
                            <span><i class="bi bi-database me-2"></i> Data Master &amp; Admin</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ $isMaster ? 'show' : '' }}" id="menuMaster">
                            <div class="bg-light ps-4 py-1">
                                <a href="{{ route('persediaan.master-rekening.index') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.master-rekening.*') ? 'fw-bold text-primary' : 'text-dark' }}">Master Rekening</a>
                                <a href="{{ route('persediaan.master-satuan.index') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.master-satuan.*') ? 'fw-bold text-primary' : 'text-dark' }}">Master Satuan</a>
                                <a href="{{ route('persediaan.master-barang.index') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.master-barang.*') ? 'fw-bold text-primary' : 'text-dark' }}">Master Barang</a>
                                <a href="{{ route('persediaan.manajemen-kunci.index') }}" class="d-block py-2 text-decoration-none small text-danger fw-bold {{ request()->routeIs('persediaan.manajemen-kunci.*') ? 'text-decoration-underline' : '' }}"><i class="bi bi-lock me-1"></i> Kunci Laporan Bulanan</a>
                            </div>
                        </div>
                    </div>
                @endif

                @if (! $isPegawaiBiasa)
                    <a href="{{ route('persediaan.transaksi-bast.index') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('persediaan.transaksi-bast.*') ? 'active' : '' }}">
                        <i class="bi bi-cart-plus me-2"></i> Input Transaksi
                    </a>
                @endif

                @if ($isAdmin)
                    <a href="{{ route('persediaan.transaksi-bon.index') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('persediaan.transaksi-bon.*') ? 'active' : '' }}">
                        <i class="bi bi-cart-plus me-2"></i> Input Bon
                    </a>

                    @php $isStokOpname = request()->routeIs('persediaan.stok-opname.*', 'persediaan.riwayat-opname.*'); @endphp
                    <div class="list-group-item p-0">
                        <a class="d-flex justify-content-between align-items-center list-group-item list-group-item-action py-3 text-decoration-none {{ $isStokOpname ? 'bg-light fw-bold text-primary' : '' }}" data-bs-toggle="collapse" href="#menuOpname" role="button" aria-expanded="{{ $isStokOpname ? 'true' : 'false' }}">
                            <span><i class="bi bi-clipboard-check me-2"></i> Stok Opname</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ $isStokOpname ? 'show' : '' }}" id="menuOpname">
                            <div class="bg-light ps-4 py-1">
                                <a href="{{ route('persediaan.stok-opname.index') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.stok-opname.*') ? 'fw-bold text-primary' : 'text-dark' }}">Stok Opname</a>
                                <a href="{{ route('persediaan.riwayat-opname.index') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.riwayat-opname.*') ? 'fw-bold text-primary' : 'text-dark' }}">Riwayat Opname</a>
                            </div>
                        </div>
                    </div>
                @endif

                @if (! $isPegawaiBiasa)
                    @php $isRiwayat = request()->routeIs('persediaan.riwayat-dokumen.*'); @endphp
                    <a class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center {{ $isRiwayat ? 'active' : '' }}" data-bs-toggle="collapse" href="#menuRiwayat" role="button">
                        <span><i class="bi bi-journal-text me-2"></i> Riwayat Dokumen</span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ $isRiwayat ? 'show' : '' }}" id="menuRiwayat">
                        <div class="bg-light ps-4 py-1">
                            <a href="{{ route('persediaan.riwayat-dokumen.index') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.riwayat-dokumen.index') ? 'fw-bold text-primary' : 'text-dark' }}">Riwayat BAST Masuk</a>
                            <a href="{{ route('persediaan.riwayat-dokumen.keluar') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('persediaan.riwayat-dokumen.keluar') ? 'fw-bold text-danger' : 'text-dark' }}">Riwayat Bon Keluar</a>
                        </div>
                    </div>
                @endif

                <a href="{{ route('persediaan.laporan-pemakaian.index') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('persediaan.laporan-pemakaian.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data me-2"></i> Persediaan Bidang
                </a>

                <a href="{{ route('persediaan.laporan.index') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('persediaan.laporan.*', 'persediaan.cetak.*') ? 'active' : '' }}">
                    <i class="bi bi-bank me-2"></i> Persediaan Global
                </a>

            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
    @if (session('success'))
        <div class="alert alert-success mt-3 py-2 rounded-4 shadow-sm">{!! session('success') !!}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3 py-2 rounded-4 shadow-sm">{!! session('error') !!}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mt-3 py-2 rounded-4 shadow-sm"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
    @endif

    @yield('content')

    @include('persediaan.partials.footer')
