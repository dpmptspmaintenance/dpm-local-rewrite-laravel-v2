<style>
    /* ===== submenu (nested dropdown) — desktop ===== */
    @media all and (min-width: 992px) {
        .dropdown-menu li { position: relative; }
        .dropdown-menu .submenu {
            display: none;
            position: absolute;
            left: 100%;
            top: -7px;
        }
        .dropdown-menu>li:hover { background-color: #f1f1f1; }
        .dropdown-menu>li:hover>.submenu { display: block; }
    }
    /* ===== small devices ===== */
    @media (max-width: 991px) {
        .dropdown-menu .dropdown-menu {
            margin-left: 0.7rem;
            margin-right: 0.7rem;
            margin-bottom: .5rem;
        }
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.dropdown-menu').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        if (window.innerWidth < 992) {
            document.querySelectorAll('.navbar .dropdown').forEach(function(everydropdown) {
                everydropdown.addEventListener('hidden.bs.dropdown', function() {
                    this.querySelectorAll('.submenu').forEach(function(everysubmenu) {
                        everysubmenu.style.display = 'none';
                    });
                });
            });

            document.querySelectorAll('.dropdown-menu a').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    let nextEl = this.nextElementSibling;
                    if (nextEl && nextEl.classList.contains('submenu')) {
                        e.preventDefault();
                        nextEl.style.display = (nextEl.style.display == 'block') ? 'none' : 'block';
                    }
                });
            });
        }
    });
</script>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-gradient px-5">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/logo-datakita.png') }}" alt="Logo Data Kita" width="70">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse text-center" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 w-100 d-flex justify-content-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="dropdownOss" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Data OSS-RBA
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownOss">
                        <li>
                            <a href="{{ route('datakita.perizinan.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.perizinan.*') ? 'active' : '' }}">Pencarian Data Gabungan</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.ossrba.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.ossrba.*') ? 'active' : '' }}">Pencarian Perizinan OSS-RBA</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.perusahaan.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.perusahaan.*') ? 'active' : '' }}">Pencarian Data Perusahaan</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.proyek.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.proyek.*') ? 'active' : '' }}">Pencarian Data Proyek</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.ossrba-tracking.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.ossrba-tracking.*') ? 'active' : '' }}">Tracking OSS-RBA</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.grafik-kbli.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.grafik-kbli.*') ? 'active' : '' }}">Resume KBLI</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.realisasi-investasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.realisasi-investasi.*') ? 'active' : '' }}">Satu Data Realisasi Investasi</a>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> Rekap &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.rekap-10-kbli-teratas.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-10-kbli-teratas.*') ? 'active' : '' }}">10 KBLI Teratas</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.rekap-sektor.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-sektor.*') ? 'active' : '' }}">Rekap Persektor Pembina</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.rekap-nib.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-nib.*') ? 'active' : '' }}">Rekap NIB</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.rekap-kbli-kecamatan.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-kbli-kecamatan.*') ? 'active' : '' }}">Rekap Proyek Per KBLI</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.rekap-izin-kbli.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-izin-kbli.*') ? 'active' : '' }}">Rekap Izin Per KBLI</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.rekap-jenis-izin.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-jenis-izin.*') ? 'active' : '' }}">Rekap List Jenis Izin Dan Nama Dokumen</a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> Statistik OSS &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.statistik.kantor') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.statistik.kantor*') ? 'active' : '' }}">Statistik Kantor</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.statistik.proyek') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.statistik.proyek*') ? 'active' : '' }}">Statistik Proyek</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.statistik.izin') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.statistik.izin*') ? 'active' : '' }}">Statistik Izin</a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> Grafik Rekap Proyek &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.grafik-rekap-jumlah-investasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.grafik-rekap-jumlah-investasi*') ? 'active' : '' }}">Grafik jumlah Investasi Pertahun</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.grafik-rekap-jumlah-tki.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.grafik-rekap-jumlah-tki*') ? 'active' : '' }}">Grafik Serapan Tenaga Kerja Pertahun</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.grafik-rekap-kbli.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.grafik-rekap-kbli*') ? 'active' : '' }}">Grafik Rekap Kbli</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.grafik-rekap-list-izin.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.grafik-rekap-list-izin*') ? 'active' : '' }}">Grafik List Izin</a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> LPPD &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.lppd.realisasi-investasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.lppd.realisasi-investasi*') ? 'active' : '' }}">LPPD Realisasi Investasi</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.lppd.realisasi-per-kecamatan.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.lppd.realisasi-per-kecamatan*') ? 'active' : '' }}">LPPD Realisasi Per kecamatan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.lppd.rincian-realisasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.lppd.rincian-realisasi*') ? 'active' : '' }}">LPPD Rincian Realisasi</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.lppd.rekap-investasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.lppd.rekap-investasi*') ? 'active' : '' }}">LPPD Rekap Investasi</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.lppd.target-investasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.lppd.target-investasi*') ? 'active' : '' }}">LPPD Target Investasi</a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> Overview &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.overview.oss-per-kecamatan.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.oss-per-kecamatan*') ? 'active' : '' }}">Overview Data OSS Per Kecamatan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.oss-per-kecamatan-kbli.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.oss-per-kecamatan-kbli*') ? 'active' : '' }}">Overview Data OSS Per Kecamatan sesuai KBLI</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.oss-per-kecamatan-kbli-kelas1.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.oss-per-kecamatan-kbli-kelas1*') ? 'active' : '' }}">Overview Data OSS Per Kecamatan sesuai KBLI Kelas 1</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.oss-per-kecamatan-kbli-kelas2.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.oss-per-kecamatan-kbli-kelas2*') ? 'active' : '' }}">Overview Data OSS Per Kecamatan sesuai KBLI Kelas 2</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.oss-per-klasifikasi.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.oss-per-klasifikasi*') ? 'active' : '' }}">Overview Data OSS Per Klasifikasi</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="dropdownSimbg" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Data SIMBG
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownSimbg">
                        <li>
                            <a href="{{ route('datakita.monitoring-simbg.tambah-ubah') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.monitoring-simbg.tambah-ubah') ? 'active' : '' }}">Tambah/Ubah Pengambilan SK</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.monitoring-simbg.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.monitoring-simbg.index') ? 'active' : '' }}">Monitoring SIMBG</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.rekap-pbg.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.rekap-pbg*') ? 'active' : '' }}">Rekap PBG</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.validasi-pembayaran.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.validasi-pembayaran*') ? 'active' : '' }}">Validasi Pembayaran Retribusi PBG</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.simbg-tracking.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.simbg-tracking*') ? 'active' : '' }}">Tracking SIMBG</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.daftar-file.index', ['klasifikasi' => 'simbg']) }}" class="dropdown-item text-black {{ request()->routeIs('datakita.daftar-file*') ? 'active' : '' }}">Daftar File</a>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> Overview &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.overview.simbg-resume-pertahun') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.simbg-resume-pertahun*') ? 'active' : '' }}">Overview Simbg Pertahun</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.simbg-rekap-perkecamatan-pertahun') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.simbg-rekap-perkecamatan-pertahun*') ? 'active' : '' }}">Overview Simbg pertahun perkecamatan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.simbg-fungsi-per-kecamatan') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.simbg-fungsi-per-kecamatan*') ? 'active' : '' }}">Overview Simbg Fungsi Per Kecamatan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.simbg-slf-pbg-pertahun') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.simbg-slf-pbg-pertahun*') ? 'active' : '' }}">Overview Simbg SLF PBG Per tahun</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="dropdownMppd" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        MPP Digital
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMppd">
                        <li>
                            <a href="{{ route('datakita.mppdig-permohonan.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.mppdig-permohonan*') ? 'active' : '' }}">MPP Digital Permohonan</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.mppdig-pemohon.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.mppdig-pemohon*') ? 'active' : '' }}">MPP Digital Pemohon</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.mppdig-kendala.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.mppdig-kendala*') ? 'active' : '' }}">MPP Digital Kendala</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.mppdig-faskes.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.mppdig-faskes*') ? 'active' : '' }}">MPP Digital Faskes</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.mppdig-grafik.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.mppdig-grafik*') ? 'active' : '' }}">MPP Digital Grafik</a>
                        </li>
                        <li>
                            <a href="{{ route('datakita.mppdig-tracking.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.mppdig-tracking*') ? 'active' : '' }}">MPP Digital Tracking</a>
                        </li>
                        <li>
                            <a class="dropdown-item text-black" href="#"> Overview &raquo; </a>
                            <ul class="submenu dropdown-menu">
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-sip-perkecamatan') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-sip-perkecamatan*') ? 'active' : '' }}">MPPD SIP Per Kecamatan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-sip-per-fasilitas') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-sip-per-fasilitas*') ? 'active' : '' }}">MPPD SIP Per Fasilitas</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-resume-perbulan') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-resume-perbulan*') ? 'active' : '' }}">MPPD Resume Per Bulan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-profesi-pertahun') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-profesi-pertahun*') ? 'active' : '' }}">MPPD Resume Per Tahun</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-profesi-per-kecamatan') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-profesi-per-kecamatan*') ? 'active' : '' }}">MPPD Resume Per Kecamatan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-permohonan-per-bulan') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-permohonan-per-bulan*') ? 'active' : '' }}">MPPD Permohonan Per Bulan</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-permohonan-per-bulan-horizontal') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-permohonan-per-bulan-horizontal*') ? 'active' : '' }}">MPPD Permohonan Per Bulan (Horizontal)</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-frekuensi-per-individu') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-frekuensi-per-individu*') ? 'active' : '' }}">MPPD Frekuensi Per Individu</a>
                                </li>
                                <li>
                                    <a href="{{ route('datakita.overview.mppd-faskes-per-kecamatan') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.overview.mppd-faskes-per-kecamatan*') ? 'active' : '' }}">MPPD Faskes Per Kecamatan</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                @if ((int) (Auth::user()->role ?? 0) === 1)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="dropdownSync" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Synchronize Data
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownSync">
                            <li>
                                <a href="{{ route('datakita.import.index') }}" class="dropdown-item text-black {{ request()->routeIs('datakita.import*') ? 'active' : '' }}">Import Data</a>
                            </li>
                        </ul>
                    </li>
                @endif
            </ul>
            <div class="d-flex align-items-center">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white text-capitalize position-relative" href="#"
                        id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ Auth::user()->nama ?? Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li>
                            <a href="{{ route('logout') }}" class="dropdown-item text-black">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
