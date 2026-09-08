<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap List Izin Per KBLI — Data Kita</title>
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
                <h1 class="card-title">Cari Rekap List Izin Per KBLI</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.rekap-izin-kbli.index') }}" method="GET" class="row g-3">
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
                    <div class="col-12">
                        <label class="fw-semibold" for="kd_resiko">Resiko</label>
                        <select class="form-select" name="kd_resiko" id="kd_resiko">
                            <option value="">-- Pilih Semua Resiko --</option>
                            @foreach ($resikoNama as $kode => $nama)
                                <option value="{{ $kode }}" @selected($filters['kd_resiko'] === $kode)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <hr class="mt-4">
                    <div class="col-12">
                        <label class="fw-semibold" for="kbli">KBLI</label>
                        <input type="text" class="form-control" id="kbli" name="kbli" value="{{ $filters['kbli'] }}" placeholder="Masukan KBLI">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($hasFilter)
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.rekap-izin-kbli.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.rekap-izin-kbli.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h2 class="card-title">Hasil Pencarian Rekap List Izin Per KBLI</h2>
                </div>
                <div class="card-body table-container">
                    @if ($rows->count() > 0)
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun</th>
                                    <th>Bulan</th>
                                    <th>Resiko</th>
                                    <th>KBLI</th>
                                    <th>Judul KBLI</th>
                                    <th>Jumlah KBLI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row->tahun ?? '-' }}</td>
                                        <td>{{ $row->bulan ? ($bulanNama[$row->bulan] ?? $row->bulan) : '-' }}</td>
                                        <td>{{ $resikoNama[$row->kd_resiko] ?? '-' }}</td>
                                        <td>{{ $row->kbli }}</td>
                                        <td>{{ $row->judul_kbli }}</td>
                                        <td>{{ $row->jumlah_kbli }}</td>
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
