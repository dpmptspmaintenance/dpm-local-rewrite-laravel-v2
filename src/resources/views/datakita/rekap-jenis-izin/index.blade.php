<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Jenis Izin — Data Kita</title>
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
                <h1 class="card-title">Cari Rekap Berdasarkan Tanggal Proyek</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.rekap-jenis-izin.index') }}" method="GET" class="row g-3">
                    <div class="col-6">
                        <label class="fw-semibold" for="tahun">Pilih Tahun</label>
                        <select class="form-select" name="tahun" id="tahun" required>
                            <option value="">-- Pilih Tahun --</option>
                            @foreach ($availableYears as $y)
                                <option value="{{ $y }}" @selected($filters['tahun'] == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="bulan">Pilih Bulan</label>
                        <select class="form-select" name="bulan" id="bulan">
                            <option value="">-- Semua Bulan --</option>
                            @foreach ($bulanNama as $num => $nama)
                                <option value="{{ $num }}" @selected($filters['bulan'] == $num)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="kd_resiko">Resiko</label>
                        <select class="form-select" name="kd_resiko" id="kd_resiko">
                            <option value="">-- Semua Resiko --</option>
                            @foreach ($resikoNama as $kode => $nama)
                                <option value="{{ $kode }}" @selected($filters['kd_resiko'] === $kode)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="fw-semibold" for="uraian_jenis_perizinan">Jenis Perizinan</label>
                        <select class="form-select" name="uraian_jenis_perizinan" id="uraian_jenis_perizinan">
                            <option value="">-- Semua Jenis --</option>
                            @foreach ($jenisPerizinanOptions as $j)
                                <option value="{{ $j }}" @selected($filters['uraian_jenis_perizinan'] === $j)>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari Data</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($filters['tahun'] !== '')
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.rekap-jenis-izin.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.rekap-jenis-izin.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h2 class="card-title">Hasil Pencarian</h2>
                </div>
                <div class="card-body table-container">
                    @if ($hasRows)
                        @foreach ($groups as $resiko => $perizinanGroups)
                            @foreach ($perizinanGroups as $perizinan => $rowsForGroup)
                                <div class="alert alert-secondary mt-4">
                                    <strong>{{ $perizinan }} - Resiko {{ $resikoNama[$resiko] ?? $resiko }}</strong>
                                </div>
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Tahun</th>
                                            <th>Bulan</th>
                                            <th>Status Respon (Nama Dokumen)</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rowsForGroup as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row->tahun }}</td>
                                                <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                                                <td>{{ $row->uraian_status_respon }}</td>
                                                <td><strong>{{ number_format($row->jumlah_izin) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endforeach
                        @endforeach
                    @else
                        <p class="text-center">Data tidak ditemukan untuk kriteria tersebut.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
