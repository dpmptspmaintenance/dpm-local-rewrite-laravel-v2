<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap PBG — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; } .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title h4">Cari Data Rekap PBG</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.rekap-pbg.index') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="fw-bold form-label">Filter Tanggal Berdasarkan:</label>
                        <select class="form-select" name="filter_berdasarkan">
                            <option value="tgl_dokumen_pbg" {{ $filterBerdasarkan == 'tgl_dokumen_pbg' ? 'selected' : '' }}>Tanggal Dokumen (PBG)</option>
                            <option value="tgl_registrasi" {{ $filterBerdasarkan == 'tgl_registrasi' ? 'selected' : '' }}>Tanggal Registrasi</option>
                            <option value="tgl_pengambilan_sk" {{ $filterBerdasarkan == 'tgl_pengambilan_sk' ? 'selected' : '' }}>Tanggal Pengambilan SK</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold form-label">Pilih Tahun</label>
                        <select class="form-select" name="tahun">
                            <option value="">-- Semua Tahun --</option>
                            @for ($y = (int) date('Y'); $y >= 2015; $y--)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold form-label">Pilih Bulan</label>
                        <select class="form-select" name="bulan">
                            <option value="">-- Semua Bulan --</option>
                            @foreach ([1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $num => $nm)
                                <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nm }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Cari Data</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($tahun !== '' || $bulan !== '')
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.rekap-pbg.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.rekap-pbg.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>
        @endif

        <div class="card mt-4 mb-5">
            <div class="card-header bg-info text-white">
                <h2 class="card-title h5">Hasil Pencarian</h2>
            </div>
            <div class="card-body table-responsive">
                @if ($rows->isNotEmpty())
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Pemilik</th>
                                <th>No Registrasi</th>
                                <th>No Dokumen PBG</th>
                                <th>Jenis Permohonan</th>
                                <th>Tgl Registrasi</th>
                                <th>Tgl Dokumen PBG</th>
                                <th>Tgl Pengambilan SK</th>
                                <th>Nama Pengambil SK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->nama_pemilik }}</td>
                                    <td>{{ $row->no_registrasi }}</td>
                                    <td>{{ $row->no_dokumen_pbg }}</td>
                                    <td>{{ $row->jenis_permohonan }}</td>
                                    <td>{{ $row->tgl_registrasi }}</td>
                                    <td>{{ $row->tgl_dokumen_pbg }}</td>
                                    <td>{{ $row->tgl_pengambilan_sk }}</td>
                                    <td>{{ $row->nama_pengambil_sk }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted">Tidak ada data ditemukan untuk kriteria tersebut.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
