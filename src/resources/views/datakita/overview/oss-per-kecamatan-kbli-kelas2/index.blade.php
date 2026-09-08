<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Investasi Per Kecamatan & KBLI (Kelas 2 - 3 Digit) — Data Kita</title>
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
                <h1 class="card-title mb-0 fs-5"><i class="bi bi-funnel"></i> Filter KBLI 3 Digit</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.oss-per-kecamatan-kbli-kelas2.index') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tahun</label>
                            <select class="form-select" name="tahun">
                                @for ($t = (int) date('Y'); $t >= 2020; $t--)
                                    <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Bulan</label>
                            <select class="form-select" name="bulan">
                                <option value="">-- Semua Bulan --</option>
                                @foreach (['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Ags', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'] as $k => $v)
                                    <option value="{{ $k }}" {{ $k == $bulan ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Kecamatan</label>
                            <select class="form-select" name="kecamatan">
                                <option value="">-- Semua Kecamatan --</option>
                                @foreach ($kecamatanList as $k)
                                    <option value="{{ $k }}" {{ $k == $kecamatan ? 'selected' : '' }}>{{ $k }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-50">Tampil</button>
                            <a href="{{ route('datakita.overview.oss-per-kecamatan-kbli-kelas2.export', ['tahun' => $tahun, 'bulan' => $bulan, 'kecamatan' => $kecamatan]) }}" class="btn btn-success w-50">Excel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Data Investasi Detail KBLI 3 Digit</h5>
                <span class="badge bg-info text-dark">Kecamatan: {{ $kecamatan ?: 'Semua' }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-0" style="font-size: 0.85rem;">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Kat</th>
                                <th class="text-start">Nama Kategori</th>
                                <th>Kode</th>
                                <th class="text-start">Judul KBLI (3 Digit)</th>
                                <th>Proyek</th>
                                <th>Investasi (Rp)</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $i => $row)
                                @php
                                    $persen = ($grandInvestasi > 0) ? ($row->jumlah_investasi / $grandInvestasi) * 100 : 0;
                                    $isFirst = ($firstIdxMap[$row->kat_kode] === $i);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    @if ($isFirst)
                                        <td rowspan="{{ $rowspanMap[$row->kat_kode] }}" class="fw-bold bg-primary text-white">{{ $row->kat_kode }}</td>
                                        <td rowspan="{{ $rowspanMap[$row->kat_kode] }}" class="text-start bg-light fw-semibold" style="max-width:200px;">{{ $row->kat_nama }}</td>
                                    @endif
                                    <td class="fw-bold text-primary">{{ $row->kode_kbli }}</td>
                                    <td class="text-start">{{ $row->nama_kbli }}</td>
                                    <td class="text-end">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ $persen == 0 ? '0,00%' : number_format($persen, 2, ',', '.') . '%' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-5 text-muted">Data tidak ditemukan</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="5" class="text-start ps-3">TOTAL</td>
                                <td class="text-end">{{ number_format($grandProyek, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($grandInvestasi, 0, ',', '.') }}</td>
                                <td>100%</td>
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
