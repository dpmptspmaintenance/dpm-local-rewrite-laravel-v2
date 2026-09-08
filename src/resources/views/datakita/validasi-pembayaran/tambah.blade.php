<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Validasi Pembayaran Retribusi PBG — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; } .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title h4">Tambah Data Validasi Pembayaran Retribusi PBG</h1>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form action="{{ route('datakita.validasi-pembayaran.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-6">
                        <label class="fw-semibold" for="bulan">Bulan</label>
                        <input type="text" class="form-control" id="bulan" name="bulan" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="tanggal_validasi">Tanggal Validasi</label>
                        <input type="date" class="form-control" id="tanggal_validasi" name="tanggal_validasi" required>
                    </div>
                    <div class="col-12">
                        <label class="fw-semibold" for="no_registrasi">No Registrasi</label>
                        <input type="text" class="form-control" id="no_registrasi" name="no_registrasi" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="nama_pemilik">Nama Pemilik</label>
                        <input type="text" class="form-control" id="nama_pemilik" name="nama_pemilik" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="lokasi_bangunan">Lokasi Bangunan</label>
                        <input type="text" class="form-control" id="lokasi_bangunan" name="lokasi_bangunan" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="id_biling">ID Billing</label>
                        <input type="text" class="form-control" id="id_biling" name="id_biling" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="tgl_bayar">Tanggal Bayar</label>
                        <input type="date" class="form-control" id="tgl_bayar" name="tgl_bayar" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="nominal">Nominal</label>
                        <input type="number" class="form-control" id="nominal" name="nominal" required>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="petugas">Petugas</label>
                        <input type="text" class="form-control" id="petugas" name="petugas" value="{{ Auth::user()->nama ?? 'admin' }}">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-success w-100">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
