<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Investasi Per Kecamatan & KBLI (Kelas 1 - 2 Digit) — Data Kita</title>
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
        <!-- Filter Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title mb-0 fs-5"><i class="bi bi-funnel"></i> Filter Data</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.oss-per-kecamatan-kbli-kelas1.index') }}" method="GET">
                    <div class="row">
                        <!-- Tahun -->
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Tahun</label>
                            <select class="form-select" name="tahun">
                                @for ($t = (int) date('Y'); $t >= 2020; $t--)
                                    <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Bulan -->
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Bulan</label>
                            <select class="form-select" name="bulan">
                                <option value="" {{ $bulan == '' ? 'selected' : '' }}>-- Semua Bulan --</option>
                                @foreach ($months as $k => $v)
                                    <option value="{{ $k }}" {{ $k == $bulan ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kecamatan -->
                        <div class="col-md-3 mb-3">
                            <label class="fw-semibold form-label">Pilih Kecamatan</label>
                            <select class="form-select" name="kecamatan">
                                <option value="" {{ $kecamatan == '' ? 'selected' : '' }}>-- Semua Kecamatan --</option>
                                @foreach ($kecamatanList as $kec)
                                    <option value="{{ $kec }}" {{ $kec == $kecamatan ? 'selected' : '' }}>{{ $kec }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tombol -->
                        <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-50">
                                <i class="bi bi-search"></i> Tampil
                            </button>
                            <a href="{{ route('datakita.overview.oss-per-kecamatan-kbli-kelas1.export', ['tahun' => $tahun, 'bulan' => $bulan, 'kecamatan' => $kecamatan]) }}"
                                target="_blank" class="btn btn-success w-50">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0 fw-bold">
                    Data Investasi (Detail KBLI 2 Digit): {{ $kecamatan == '' ? 'Semua Kecamatan (Kota Semarang)' : 'Kecamatan ' . $kecamatan }}
                </h5>
                <small class="text-muted">Periode: {{ ($bulan == '' ? 'Tahun' : $months[$bulan]) . " $tahun" }} &nbsp;|&nbsp; Total Kode KBLI: {{ count($rows) }} &nbsp;|&nbsp; (Berdasarkan Tanggal Terbit OSS)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center mb-0 align-middle" style="font-size: 0.875rem;">
                        <thead class="table-secondary">
                            <tr>
                                <th width="3%">No</th>
                                <th width="5%" class="text-center">Kode<br>Kat.</th>
                                <th width="18%" class="text-start ps-2">Nama Kategori KBLI</th>
                                <th width="6%" class="text-center">Kode<br>KBLI</th>
                                <th class="text-start ps-3">Judul KBLI</th>
                                <th width="12%" class="text-end pe-3">Jml Proyek</th>
                                <th width="20%" class="text-end pe-3">Jumlah Investasi (Rp)</th>
                                <th width="10%" class="text-end pe-3">Persen (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $i => $row)
                                @php
                                    $persentase = ($grandInvestasi > 0) ? ($row->jumlah_investasi / $grandInvestasi) * 100 : 0;
                                    $katKode = $row->kat_kode;
                                    $katNama = $row->kat_nama;
                                    $isFirst = ($firstIdxMap[$katKode] === $i);
                                    $span = $rowspanMap[$katKode];
                                    $katOrd = ord($katKode) - ord('A');
                                    $rowBg = ($katOrd % 2 === 0) ? '' : 'style="background:#f8f9fa;"';
                                @endphp
                                <tr {!! $rowBg !!}>
                                    <td>{{ $loop->iteration }}</td>

                                    @if ($isFirst)
                                        <!-- Merge cell Kode Kategori -->
                                        <td rowspan="{{ $span }}"
                                            class="fw-bold text-center align-middle"
                                            style="background:#1565C0;color:#fff;font-size:1.1rem;letter-spacing:1px;">
                                            {{ $katKode }}
                                        </td>
                                        <!-- Merge cell Nama Kategori -->
                                        <td rowspan="{{ $span }}"
                                            class="text-start ps-2 align-middle fw-semibold"
                                            style="background:#E3F2FD;font-size:0.8rem;line-height:1.3;">
                                            {{ $katNama }}
                                        </td>
                                    @endif

                                    <td class="fw-bold text-primary text-center">{{ $row->kode_kbli }}</td>
                                    <td class="text-start ps-3">{{ $row->judul_kbli }}</td>
                                    <td class="text-end pe-3">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3">{{ \App\Http\Controllers\DataKita\OverviewOssController::formatPersen($persentase) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Tidak ada data investasi ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-secondary" style="border-top: 2px solid #6c757d;">
                            <tr class="fw-bold">
                                <td colspan="5" class="text-start ps-3 py-3 text-uppercase">
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
