<nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-gradient px-5">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            DPMPTSP
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 w-100 d-flex justify-content-center">
                <li class="nav-item">
                    <a href="{{ route('rapatkita.jadwal.index') }}" class="nav-link {{ request()->routeIs('rapatkita.jadwal.*') ? 'active' : '' }}" aria-current="page">Jaket</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('rapatkita.notulen.index') }}" class="nav-link {{ request()->routeIs('rapatkita.notulen.*') ? 'active' : '' }}" aria-current="page">Daftar Notulen</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#downloadJadwalModal">Download Jadwal</a>
                </li>
            </ul>
            <div class="d-flex">
                <a href="{{ route('logout') }}" class="text-light fs-6 fw-bold text-decoration-none">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="modal fade" tabindex="-1" data-bs-backdrop="static" id="downloadJadwalModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('rapatkita.jadwal.download') }}" method="get">
                <div class="modal-header d-flex justify-content-between">
                    <div>
                        <h5 class="modal-title">Download Jadwal</h5>
                    </div>
                    <div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </span>
                                <input type="date" class="form-control form-control-lg" id="tanggal_awal" name="tanggal_awal" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </span>
                                <input type="date" class="form-control form-control-lg" id="tanggal_akhir" name="tanggal_akhir" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Download</button>
                </div>
            </form>
        </div>
    </div>
</div>
