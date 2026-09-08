<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Pembayaran Retribusi PBG — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; } .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title h4">Validasi Pembayaran Retribusi PBG</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.validasi-pembayaran.index') }}" method="GET" class="row g-3">
                    <div class="col-12">
                        <label class="fw-semibold" for="no_registrasi">No Registrasi</label>
                        <input type="text" class="form-control" id="no_registrasi" name="no_registrasi" value="{{ $noReg }}" placeholder="Masukan No Registrasi">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($noReg !== '')
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.validasi-pembayaran.index') }}" class="btn btn-warning">Reset Filter</a>
                <a href="{{ route('datakita.validasi-pembayaran.tambah') }}" class="btn btn-success">Tambah Data</a>
            </div>
        @endif

        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h2 class="card-title h5">Hasil Pencarian Validasi Pembayaran Retribusi PBG</h2>
            </div>
            <div class="card-body table-responsive">
                @if ($rows->isNotEmpty())
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Bulan</th>
                                <th>Tanggal Validasi</th>
                                <th>No Registrasi</th>
                                <th>Nama Pemilik</th>
                                <th>Lokasi Bangunan</th>
                                <th>Id Biling</th>
                                <th>Tanggal Bayar</th>
                                <th>Nominal</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->bulan }}</td>
                                    <td>{{ $row->tanggal_validasi }}</td>
                                    <td>{{ $row->no_registrasi }}</td>
                                    <td>{{ $row->nama_pemilik }}</td>
                                    <td>{{ $row->lokasi_bangunan }}</td>
                                    <td>{{ $row->id_biling }}</td>
                                    <td>{{ $row->tgl_bayar }}</td>
                                    <td>{{ $row->nominal }}</td>
                                    <td>{{ $row->petugas }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center">Tidak Ada Data Yang Ditemukan</p>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
