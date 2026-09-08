<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview Data Investasi — Data Kita</title>
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
                <h1 class="card-title mb-0 fs-5">
                    <i class="bi bi-funnel"></i> Filter Data Investasi (OSS)
                </h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.oss-per-kecamatan.index') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Pilih Tahun</label>
                            <select class="form-select" name="tahun">
                                @for ($t = (int) date('Y'); $t >= 2020; $t--)
                                    <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Pilih Bulan</label>
                            <select class="form-select" name="bulan">
                                <option value="" {{ $bulan == '' ? 'selected' : '' }}>-- Semua Bulan --</option>
                                @foreach ($months as $key => $val)
                                    <option value="{{ $key }}" {{ $key == $bulan ? 'selected' : '' }}>{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-50">
                                <i class="bi bi-search"></i> Cari Data
                            </button>

                            <a href="{{ route('datakita.overview.oss-per-kecamatan.export', ['tahun' => $tahun, 'bulan' => $bulan]) }}" target="_blank" class="btn btn-success w-50">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold">
                    Rekap Per Kecamatan ({{ $bulan == '' ? "Tahun $tahun (Semua Bulan)" : $months[$bulan] . " $tahun" }})
                </h5>
                <span class="badge bg-info text-dark">Data: Tanggal Terbit OSS</span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center mb-0 align-middle" style="font-size: 0.9rem;">
                        <thead class="table-secondary">
                            <tr>
                                <th width="35%" class="align-middle text-start ps-3">Kecamatan</th>
                                <th width="20%" class="align-middle text-end pe-3">Jumlah Proyek</th>
                                <th width="40%" class="align-middle text-end pe-3">Nilai Investasi (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php $isUndefined = $row->kecamatan_usaha === 'Kecamatan Tidak Terdefinisi'; @endphp
                                <tr>
                                    <td class="text-start ps-3 {{ $isUndefined ? 'text-danger fst-italic' : 'fw-medium' }}">
                                        {{ $row->kecamatan_usaha }}
                                    </td>
                                    <td class="text-end pe-3">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-5 text-center text-muted">
                                        <i class="bi bi-folder2-open display-6 d-block mb-2"></i>
                                        <span class="fst-italic">Belum ada data OSS terbit pada periode ini.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary" style="border-top: 2px solid #6c757d;">
                            <tr class="fw-bold">
                                <td colspan="1" class="text-center text-uppercase align-middle py-3">Total Keseluruhan</td>
                                <td class="text-end pe-3 align-middle fs-6 text-primary">{{ number_format($grandProyek, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 align-middle fs-6 text-primary">{{ number_format($grandInvestasi, 0, ',', '.') }}</td>
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
