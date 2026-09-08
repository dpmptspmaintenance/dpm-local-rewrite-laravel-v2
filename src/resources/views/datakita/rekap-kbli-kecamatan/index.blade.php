<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap KBLI Perkecamatan — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: "Moderustic", sans-serif; }
        .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .table-container { overflow-x: auto; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title">Cari KBLI Perkecamatan</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.rekap-kbli-kecamatan.index') }}" method="GET" class="row g-3">
                    <div class="col-6">
                        <label class="fw-semibold" for="tahun">Pilih Tahun</label>
                        <select class="form-select" name="tahun" id="tahun" required>
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ([2020, 2021, 2022, 2023, 2024, 2025] as $year)
                                <option value="{{ $year }}" @selected($filters['tahun'] == $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="bulan">Pilih Bulan</label>
                        <select class="form-select" name="bulan" id="bulan">
                            <option value="">-- Pilih Semua Bulan --</option>
                            @foreach ($bulanNama as $num => $nama)
                                <option value="{{ $num }}" @selected($filters['bulan'] == $num)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="fw-semibold" for="kecamatan">Kecamatan</label>
                        <select class="form-select" name="kecamatan" id="kecamatan">
                            <option value="">-- Pilih Semua Kecamatan --</option>
                            @foreach ($kecamatanOptions as $k)
                                <option value="{{ $k }}" @selected($filters['kecamatan'] === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="fw-semibold" for="kelurahan">Kelurahan</label>
                        <select class="form-select" name="kelurahan" id="kelurahan">
                            <option value="">-- Pilih Semua Kelurahan --</option>
                            @foreach ($kelurahanOptions as $k)
                                <option value="{{ $k }}" @selected($filters['kelurahan'] === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="fw-semibold" for="skala_usaha">Skala Usaha</label>
                        <select class="form-select" name="skala_usaha" id="skala_usaha">
                            <option value="">-- Pilih Semua Skala --</option>
                            @foreach ($skalaUsahaOptions as $s)
                                <option value="{{ $s }}" @selected($filters['skala_usaha'] === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <hr class="mt-4">
                    <div class="col-12">
                        <label class="fw-semibold" for="judul_kbli">Judul KBLI</label>
                        <input type="text" class="form-control" id="judul_kbli" name="judul_kbli" value="{{ $filters['judul_kbli'] }}" placeholder="Masukan Judul KBLI">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($hasFilter)
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.rekap-kbli-kecamatan.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.rekap-kbli-kecamatan.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h2 class="card-title">Hasil Pencarian Rekap KBLI Perkecamatan</h2>
                </div>
                <div class="card-body table-container">
                    @if ($rows->count() > 0)
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kecamatan</th>
                                    <th>Kelurahan</th>
                                    <th>Tahun</th>
                                    <th>Bulan</th>
                                    <th>KBLI</th>
                                    <th>Judul KBLI</th>
                                    <th>Jumlah KBLI</th>
                                    <th>Total Investasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row->kecamatan }}</td>
                                        <td>{{ $row->kelurahan }}</td>
                                        <td>{{ $row->tahun }}</td>
                                        <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                                        <td>{{ $row->kbli }}</td>
                                        <td>{{ $row->judul_kbli }}</td>
                                        <td>{{ $row->jumlah_kbli }}</td>
                                        <td>Rp.{{ number_format($row->total_investasi, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-center">Tidak Ada Data Yang Ditemukan</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
