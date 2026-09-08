<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Data Proyek Persektor Pembina — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: "Moderustic", sans-serif; }
        .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .table-container { overflow-x: auto; }
        .kbli-detail { font-size: 0.9em; color: #666; border-left: 3px solid #17a2b8; padding-left: 10px; margin-left: 5px; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title">Cari Rekap Data Proyek Persektor Pembina</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.rekap-sektor.index') }}" method="GET" class="row g-3">
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
                            <option value="">-- Pilih Bulan --</option>
                            @foreach ($bulanNama as $num => $nama)
                                <option value="{{ $num }}" @selected($filters['bulan'] == $num)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="sektor_pembina">Sektor Pembina</label>
                        <select class="form-select" name="sektor_pembina" id="sektor_pembina">
                            <option value="">-- Pilih Sektor Pembina --</option>
                            @foreach ($sektorPembina as $s)
                                <option value="{{ $s }}" @selected($filters['sektor_pembina'] === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="kecamatan">Kecamatan</label>
                        <select class="form-select" name="kecamatan" id="kecamatan">
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach ($kecamatan as $k)
                                <option value="{{ $k }}" @selected($filters['kecamatan'] === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($hasFilter)
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.rekap-sektor.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.rekap-sektor.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h2 class="card-title">Hasil Pencarian Rekap Data Proyek Persektor Pembina</h2>
                </div>
                <div class="card-body table-container">
                    @if ($rows->count() > 0)
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun</th>
                                    <th>Bulan</th>
                                    <th>Sektor Pembina &amp; KBLI</th>
                                    @if ($filters['kecamatan'] !== '')
                                        <th>Kecamatan</th>
                                    @endif
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row->tahun }}</td>
                                        <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                                        <td>
                                            <strong>{{ $row->sektor_pembina }}</strong>
                                            <div class="kbli-detail my-3">{!! $row->kbli_detail !!}</div>
                                        </td>
                                        @if ($filters['kecamatan'] !== '')
                                            <td>{{ $row->kecamatan }}</td>
                                        @endif
                                        <td>{{ $row->jumlah_sektor_pembina }}</td>
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
