<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beranda - DPMPTSP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .container-main {
            width: 100%;
            max-width: 1000px;
            padding: 2rem 1rem;
        }

        .menu-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            border: none;
            border-radius: 1rem;
            height: 100%;
        }

        .menu-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .menu-icon {
            font-size: 2.5rem;
            color: #0d6efd;
        }

        @media (max-width: 768px) {
            .menu-card .menu-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>

    <div class="container-main">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 text-center text-md-start">
            <div>
                <h4 class="fw-bold text-primary mb-1">Beranda</h4>
                <small class="text-muted">
                    Halo, {{ Auth::user()->name }} ({{ Auth::user()->email }})
                </small>
            </div>

        </div>

        <div class="row g-4 mb-5">
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/rapatkita') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-people-fill menu-icon mb-3"></i>
                            <h5 class="fw-semibold">Rapat Kita</h5>
                            <p class="text-muted mb-0 small">Informasi dan jadwal rapat</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/bangkit') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-box-seam menu-icon mb-3"></i>
                            <h5 class="fw-semibold">Barang Kita</h5>
                            <p class="text-muted mb-0 small">Inventaris barang kantor</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/persediaan') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5 border-success" style="border-width: 2px;">
                        <div class="card-body">
                            <i class="bi bi-cart-plus menu-icon mb-3 text-success"></i>
                            <h5 class="fw-semibold text-success">Smart Stok</h5>
                            <p class="text-muted mb-0 small">Manajemen stok dan laporan Buku Gudang</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/data-kita') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-bar-chart-line-fill menu-icon mb-3"></i>
                            <h5 class="fw-semibold">Data Kita</h5>
                            <p class="text-muted mb-0 small">Data internal DPMPTSP</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/sikenut') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-currency-dollar menu-icon mb-3"></i>
                            <h5 class="fw-semibold">Sikenut</h5>
                            <p class="text-muted mb-0 small">Kendali Uang Transport</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/botman') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-chat-dots-fill menu-icon mb-3"></i>
                            <h5 class="fw-semibold">Botman</h5>
                            <p class="text-muted mb-0 small">Chat dengan Bot</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/botman-manager') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5 border-primary" style="border-width: 2px;">
                        <div class="card-body">
                            <i class="bi bi-gear-wide-connected menu-icon mb-3 text-primary"></i>
                            <h5 class="fw-semibold text-primary">Bot Manager</h5>
                            <p class="text-muted mb-0 small">Kelola Knowledge Base Bot</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <a href="{{ url('/siperdafit') }}" class="text-decoration-none text-dark">
                    <div class="card menu-card shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-archive menu-icon mb-3"></i>
                            <h5 class="fw-semibold">Siperdafit</h5>
                            <p class="text-muted mb-0 small">Sistem Permintaan Informasi</p>
                        </div>
                    </div>
                </a>
            </div>

            @if (Auth::user()->role == 1)
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="{{ url('/user') }}" class="text-decoration-none text-dark">
                        <div class="card menu-card shadow-sm text-center py-5">
                            <div class="card-body">
                                <i class="bi bi-person-plus-fill menu-icon mb-3"></i>
                                <h5 class="fw-semibold">Add User</h5>
                                <p class="text-muted mb-0 small">Manajemen Pengguna Sistem</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endif

        </div>

        <div class="text-center">
            <a href="{{ route('logout') }}" class="btn btn-danger px-4 py-2">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
