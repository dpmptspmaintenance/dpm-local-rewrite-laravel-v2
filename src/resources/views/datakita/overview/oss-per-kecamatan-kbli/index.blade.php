<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Investasi Per Kecamatan & KBLI — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: "Moderustic", sans-serif; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title mb-0 fs-5"><i class="bi bi-funnel"></i> Filter Data</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.oss-per-kecamatan-kbli.index') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Tahun</label>
                            <select class="form-select" name="tahun">
                                @for ($t = (int) date('Y'); $t >= 2020; $t--)
                                    <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Bulan</label>
                            <select class="form-select" name="bulan">
                                <option value="" {{ $bulan == '' ? 'selected' : '' }}>-- Semua Bulan --</option>
                                @foreach ($months as $k => $v)
                                    <option value="{{ $k }}" {{ $k == $bulan ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Pilih Kecamatan</label>
                            <select class="form-select" name="kecamatan">
                                <option value="" {{ $kecamatan == '' ? 'selected' : '' }}>-- Semua Kecamatan --</option>
                                @foreach ($kecamatanList as $kec)
                                    <option value="{{ $kec }}" {{ $kec == $kecamatan ? 'selected' : '' }}>{{ $kec }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-50">
                                <i class="bi bi-search"></i> Tampil
                            </button>

                            <a href="{{ route('datakita.overview.oss-per-kecamatan-kbli.export', ['tahun' => $tahun, 'bulan' => $bulan, 'kecamatan' => $kecamatan]) }}" target="_blank" class="btn btn-success w-50">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 fw-bold">
                    Data Investasi: {{ $kecamatan == '' ? 'Semua Kecamatan (Kota Semarang)' : 'Kecamatan ' . $kecamatan }}
                </h5>
                <small class="text-muted">Periode: {{ ($bulan == '' ? 'Tahun' : $months[$bulan]) . " $tahun" }} (Berdasarkan Tanggal Terbit OSS)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center mb-0 align-middle" style="font-size: 0.9rem;">
                        <thead class="table-secondary">
                            <tr>
                                <th width="10%">Kode Klas</th>
                                <th width="30%" class="text-start ps-3">Nama Klasifikasi</th>
                                <th width="15%" class="text-end pe-3">Jumlah Proyek</th>
                                <th width="25%" class="text-end pe-3">Jumlah Investasi (Rp)</th>
                                <th width="15%" class="text-end pe-3">Persentase (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $parts = explode('. ', $row->kategori_kbli, 2);
                                    $kode = $parts[0] ?? '?';
                                    $nama = $parts[1] ?? $row->kategori_kbli;
                                    $persentase = ($grandInvestasi > 0) ? ($row->jumlah_investasi / $grandInvestasi) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $kode }}</td>
                                    <td class="text-start ps-3 fw-medium">{{ $nama }}</td>
                                    <td class="text-end pe-3">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">{{ number_format($persentase, 2, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-5 text-center text-muted">Tidak ada data investasi ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary" style="border-top: 2px solid #6c757d;">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-start ps-3 py-3 text-uppercase">
                                    Total Investasi ({{ $kecamatan == '' ? 'KOTA SEMARANG' : $kecamatan }})
                                </td>
                                <td class="text-end pe-3 text-primary fs-6">{{ number_format($grandProyek, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 text-primary fs-6">{{ number_format($grandInvestasi, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 text-primary fs-6">100,00%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
