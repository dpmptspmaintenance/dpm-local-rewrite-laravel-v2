<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap 10 KBLI Teratas — Data Kita</title>
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
                <h1 class="card-title">Cari 10 KBLI Teratas</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.rekap-10-kbli-teratas.index') }}" method="GET" class="row g-3">
                    <div class="col-6">
                        <label class="fw-semibold" for="tahun">Pilih Tahun</label>
                        <select class="form-select" name="tahun" id="tahun" required>
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ([2020, 2021, 2022, 2023, 2024, 2025] as $year)
                                <option value="{{ $year }}" @selected($tahun == $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="skala_usaha">Skala Usaha</label>
                        <select class="form-select" name="skala_usaha" id="skala_usaha">
                            <option value="">-- Pilih Semua Skala Usaha --</option>
                            <option value="UMK" @selected($skalaUsaha === 'UMK')>UMK (Usaha Mikro & Kecil)</option>
                            <option value="Non UMK" @selected($skalaUsaha === 'Non UMK')>Non UMK (Usaha Besar)</option>
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
                <a href="{{ route('datakita.rekap-10-kbli-teratas.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.rekap-10-kbli-teratas.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h2 class="card-title">10 KBLI Teratas Berdasarkan Skala Usaha dan Tahun</h2>
                </div>
                <div class="card-body table-container">
                    @if ($rows->count() > 0)
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun</th>
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
                                        <td>{{ $row->tahun }}</td>
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
