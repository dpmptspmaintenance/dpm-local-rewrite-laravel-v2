@extends('persediaan.partials.header')

@section('title', 'Selamat Datang di Smart Stock')

@section('content')
    @php
        $namaUser = Auth::user()->nama ?? 'Pegawai';
        $bidangUser = Auth::user()->bidang ?? 'DPMPTSP';
    @endphp

    <div id="smart-preloader">
        <div class="preloader-content text-center">
            <img src="{{ asset('images/image_persediaan.png') }}" alt="Smart Stock DPMPTSP" class="img-fluid preloader-img mb-4">
            <div class="loading-bar-container mx-auto mb-3">
                <div class="loading-bar-fill"></div>
            </div>
            <small class="text-secondary fw-bold text-uppercase tracking-wider fs-7 text-pulse">
                <i class="bi bi-cpu-fill me-2"></i>Menyiapkan Ruang Logistik Kantor...
            </small>
        </div>
    </div>

    <style>
        #smart-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.6s ease-in-out, visibility 0.6s ease-in-out;
        }

        .preloader-img {
            max-width: 380px;
            width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            animation: scaleIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .loading-bar-container {
            width: 260px;
            height: 4px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .loading-bar-fill {
            height: 100%;
            width: 50%;
            background: linear-gradient(90deg, #0d6efd, #0b5ed7);
            border-radius: 10px;
            position: absolute;
            left: -50%;
            animation: loadingIndeterminate 1.6s infinite ease-in-out;
        }

        .text-pulse {
            letter-spacing: 0.8px;
            display: inline-block;
            animation: textPulseEffect 1.5s infinite ease-in-out;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0.9);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes loadingIndeterminate {
            0% {
                left: -50%;
                width: 30%;
            }
            50% {
                width: 40%;
            }
            100% {
                left: 100%;
                width: 30%;
            }
        }

        @keyframes textPulseEffect {
            0%,
            100% {
                opacity: 0.6;
            }
            50% {
                opacity: 1;
            }
        }

        .hero-gradient {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            position: relative;
            overflow: hidden;
        }

        .hero-glow {
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            filter: blur(50px);
            pointer-events: none;
        }

        .step-number {
            width: 36px;
            height: 36px;
            font-size: 11pt;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.15);
        }

        .text-tracking {
            letter-spacing: 0.5px;
        }

        .fs-7 {
            font-size: 0.825rem;
        }
    </style>

    <div class="card border-0 rounded-4 shadow-sm text-white mb-4 hero-gradient">
        <div class="hero-glow"></div>
        <div class="card-body p-4 p-md-5 text-center text-md-start d-md-flex align-items-center justify-content-between">
            <div class="mb-2 mb-md-0" style="z-index: 2;">
                <span class="badge bg-white text-primary fw-bold mb-2 px-3 py-2 rounded-pill text-uppercase text-tracking" style="font-size: 8pt;">Sistem Informasi Persediaan</span>
                <h1 class="display-6 fw-bold mb-1">Smart Stock DPMPTSP</h1>
                <p class="lead mb-0 opacity-75 fs-6">Halo, <b>{{ e($namaUser) }}</b> (Bidang {{ e($bidangUser) }}). Selamat datang di ruang tata kelola logistik kantor.</p>
            </div>
            <div class="d-none d-md-block" style="z-index: 2;">
                <i class="bi bi-box-seam display-2 opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
        <div class="mb-4 text-center text-md-start">
            <h6 class="fw-bold text-dark text-uppercase text-tracking m-0"><i class="bi bi-bezier2 text-primary me-2"></i>5 Langkah Alur Onboarding</h6>
            <small class="text-muted">Standardisasi operasional alur pengelolaan barang persediaan gudang dinas.</small>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md col-sm-6 text-center text-md-start">
                <div class="d-flex flex-column align-items-center align-items-md-start">
                    <div class="bg-primary bg-opacity-10 text-primary step-number mb-2">01</div>
                    <h6 class="fw-bold text-dark mb-1 small text-tracking">Cek Stok Riil</h6>
                    <p class="text-muted fs-7 mb-0">Sistem memetakan hak akses. Cek sisa kuantitas gudang secara berkala sebelum transaksi.</p>
                </div>
            </div>
            <div class="col-md col-sm-6 text-center text-md-start">
                <div class="d-flex flex-column align-items-center align-items-md-start">
                    <div class="bg-primary bg-opacity-10 text-primary step-number mb-2">02</div>
                    <h6 class="fw-bold text-dark mb-1 small text-tracking">Catat Transaksi</h6>
                    <p class="text-muted fs-7 mb-0">Input mutasi BAST Masuk (Vendor) atau eksekusi Bon Keluar (Bidang Internal).</p>
                </div>
            </div>
            <div class="col-md col-sm-6 text-center text-md-start">
                <div class="d-flex flex-column align-items-center align-items-md-start">
                    <div class="bg-warning bg-opacity-10 text-warning step-number mb-2">03</div>
                    <h6 class="fw-bold text-dark mb-1 small text-tracking">Upload Lampiran</h6>
                    <p class="text-muted fs-7 mb-0">Wajib unggah berkas fisik bukti dukung asli berformat JPG, PNG, atau PDF ke sistem.</p>
                </div>
            </div>
            <div class="col-md col-sm-6 text-center text-md-start">
                <div class="d-flex flex-column align-items-center align-items-md-start">
                    <div class="bg-success bg-opacity-10 text-success step-number mb-2">04</div>
                    <h6 class="fw-bold text-dark mb-1 small text-tracking">Lock &amp; Verifikasi</h6>
                    <p class="text-muted fs-7 mb-0">Admin melakukan verifikasi berkala. Periode laporan akan dikunci demi keamanan data.</p>
                </div>
            </div>
            <div class="col-md col-sm-6 text-center text-md-start">
                <div class="d-flex flex-column align-items-center align-items-md-start">
                    <div class="bg-danger bg-opacity-10 text-danger step-number mb-2">05</div>
                    <h6 class="fw-bold text-dark mb-1 small text-tracking">Cek &amp; Cetak</h6>
                    <p class="text-muted fs-7 mb-0">Cetak naskah dinas resmi presisi lengkap dengan formasi tanda tangan Keuangan dan BMD.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function() {
            const preloader = document.getElementById('smart-preloader');
            setTimeout(function() {
                preloader.style.opacity = '0';
                preloader.style.visibility = 'hidden';
            }, 1600);
        });
    </script>
@endpush
