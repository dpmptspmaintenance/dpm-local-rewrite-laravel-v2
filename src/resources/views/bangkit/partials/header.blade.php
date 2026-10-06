<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bangkit — Barang & Persediaan')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        html,
        body {
            font-family: "Moderustic", sans-serif;
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
            background-color: #f8be12;
            border-color: #f8be12;
            color: #222;
            font-weight: 600;
        }
    </style>
</head>

<body class="bg-light">

    @php
        $user = auth()->user();
        $isAdmin = ((int) ($user->role ?? 0) === 1 || (int) ($user->is_admin_bangkit ?? 0) === 1);
        $role = (int) ($user->role ?? 0);
        $isBendahara = $role === 5;
        $isSekdin = $role === 3;
        $isKasubag = $role === 4;
        $isKadin = $role === 2;
        $isUser = $role === 6;
        $bisaVerifikasi = $isAdmin || $isBendahara || $isSekdin || $isKasubag;
        $bisaSdia = $isAdmin || $isUser || $isBendahara || $isSekdin || $isKasubag;
    @endphp

    <!-- NAVBAR ATAS -->
    <nav class="navbar navbar-dark shadow-sm mb-4" style="background-color:#222;">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-light border-0 px-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarLeft" aria-controls="sidebarLeft">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <a class="navbar-brand fw-bold mb-0" href="{{ route('bangkit.index') }}">
                    <i class="bi bi-box-seam me-2"></i> Bangkit
                </a>
            </div>
            <div class="dropdown">
                <button class="btn btn-light bg-transparent border-0 rounded-circle p-0 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px;">
                    <i class="bi bi-three-dots-vertical fs-5 text-white"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
                    <li><span class="dropdown-item-text small text-muted">{{ $user->nama ?? $user->name }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2 small fw-semibold text-dark" href="{{ route('dashboard') }}">
                            <i class="bi bi-arrow-left-square text-warning me-2"></i>Kembali ke Portal Utama
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- LEFT SIDEBAR -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarLeft" aria-labelledby="sidebarLeftLabel">
        <div class="offcanvas-header text-white" style="background-color:#222;">
            <h5 class="offcanvas-title fw-bold" id="sidebarLeftLabel"><i class="bi bi-box-seam me-2"></i> Navigasi Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">

                <a href="{{ route('bangkit.index') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('bangkit.index') ? 'active' : '' }}">
                    <i class="bi bi-house-door me-2"></i> Beranda
                </a>

                @php $isBarang = request()->routeIs('bangkit.barang.*', 'bangkit.permohonan.*', 'bangkit.rekap.*'); @endphp
                <div class="list-group-item p-0">
                    <a class="d-flex justify-content-between align-items-center list-group-item list-group-item-action py-3 text-decoration-none {{ $isBarang ? 'bg-light fw-bold text-warning' : '' }}" data-bs-toggle="collapse" href="#menuBarang" role="button" aria-expanded="{{ $isBarang ? 'true' : 'false' }}">
                        <span><i class="bi bi-archive me-2"></i> Barang Kita</span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <div class="collapse {{ $isBarang ? 'show' : '' }}" id="menuBarang">
                        <div class="bg-light ps-4 py-1">
                            <a href="{{ route('bangkit.barang.cari') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.barang.cari', 'bangkit.barang.hasil') ? 'fw-bold text-warning' : 'text-dark' }}">Cari Barang</a>
                            <a href="{{ route('bangkit.barang.data-saya') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.barang.data-saya') ? 'fw-bold text-warning' : 'text-dark' }}">Barang Saya</a>
                            @if ($isAdmin || $isBendahara || $isKasubag)
                                <a href="{{ route('bangkit.barang.kartu-inventaris') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.barang.kartu-inventaris') ? 'fw-bold text-warning' : 'text-dark' }}">Kartu Inventaris Ruangan</a>
                            @endif
                            @if ($isAdmin)
                                <a href="{{ route('bangkit.barang.tambah') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.barang.tambah') ? 'fw-bold text-warning' : 'text-dark' }}">Tambah Barang</a>
                                <a href="{{ route('bangkit.permohonan.selesai-list') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.permohonan.selesai-list', 'bangkit.permohonan.selesai') ? 'fw-bold text-warning' : 'text-dark' }}">List Selesai</a>
                            @endif
                            @if ($bisaVerifikasi)
                                <a href="{{ route('bangkit.permohonan.verifikasi-list') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.permohonan.verifikasi-list', 'bangkit.permohonan.verifikasi') ? 'fw-bold text-warning' : 'text-dark' }}">List Verifikasi</a>
                            @endif
                            @if ($isAdmin || $isBendahara || $isKasubag)
                                <a href="{{ route('bangkit.rekap.perbulan', ['bulan' => now()->month]) }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.rekap.*') ? 'fw-bold text-warning' : 'text-dark' }}">Rekap Permohonan</a>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($bisaSdia)
                    @php $isSdia = request()->routeIs('bangkit.sdia.*'); @endphp
                    <div class="list-group-item p-0">
                        <a class="d-flex justify-content-between align-items-center list-group-item list-group-item-action py-3 text-decoration-none {{ $isSdia ? 'bg-light fw-bold text-warning' : '' }}" data-bs-toggle="collapse" href="#menuSdia" role="button" aria-expanded="{{ $isSdia ? 'true' : 'false' }}">
                            <span><i class="bi bi-cash-stack me-2"></i> SDIA Persediaan</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ $isSdia ? 'show' : '' }}" id="menuSdia">
                            <div class="bg-light ps-4 py-1">
                                <a href="{{ route('bangkit.sdia.kegiatan') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.kegiatan*') ? 'fw-bold text-warning' : 'text-dark' }}">Kegiatan</a>
                                <a href="{{ route('bangkit.sdia.klasifikasi') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.klasifikasi*') ? 'fw-bold text-warning' : 'text-dark' }}">Klasifikasi Persediaan</a>
                                <a href="{{ route('bangkit.sdia.anggaran') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.anggaran*') ? 'fw-bold text-warning' : 'text-dark' }}">Data Anggaran</a>
                                <a href="{{ route('bangkit.sdia.dpa') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.dpa*') ? 'fw-bold text-warning' : 'text-dark' }}">Data DPA</a>
                                <a href="{{ route('bangkit.sdia.transaksi') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.transaksi*') ? 'fw-bold text-warning' : 'text-dark' }}">Transaksi Barang</a>
                                <a href="{{ route('bangkit.sdia.bulanan') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.bulanan*') ? 'fw-bold text-warning' : 'text-dark' }}">SDIA Bulanan</a>
                                <a href="{{ route('bangkit.sdia.saldo-awal') }}" class="d-block py-2 text-decoration-none small {{ request()->routeIs('bangkit.sdia.saldo-awal') ? 'fw-bold text-warning' : 'text-dark' }}">Saldo Awal</a>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($isAdmin)
                    <a href="{{ route('bangkit.user.daftar') }}" class="list-group-item list-group-item-action py-3 {{ request()->routeIs('bangkit.user.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Daftar User
                    </a>
                @endif

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

        @include('bangkit.partials.footer')
