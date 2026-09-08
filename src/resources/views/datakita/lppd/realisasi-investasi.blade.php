<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realisasi vs Target Investasi (LPPD) — Data Kita</title>
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
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.lppd.realisasi-investasi.index') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tahun Pelaporan</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahunPilih ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                                @if ($listTahun->isEmpty())
                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.lppd.realisasi-investasi.export', ['tahun' => $tahunPilih]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1">KOP SURAT</h5>
                    <h5 class="fw-bold mb-0" style="color: #b5651d;">DINAS PENANAMAN MODAL KAB/KOTA SEMARANG</h5>
                    <hr style="border: 2px solid #000; opacity: 1; margin-top: 5px; margin-bottom: 5px;">
                    <h6 class="fw-bold mb-0">REKAPITULASI INVESTASI TAHUN {{ $tahunPilih }} (TAHUN PELAPORAN)</h6>
                    <h6 class="fw-bold mb-0">KAB/KOTA SEMARANG</h6>
                    <h6 class="fw-bold mb-0">TAHUN {{ $tahunPilih }}</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="border-color: #000;">
                        <thead class="text-center fw-bold align-middle">
                            <tr>
                                <th width="5%">No</th>
                                <th width="35%">Jenis Penanaman Modal</th>
                                <th width="25%">Realisasi Investasi<br>Tahun {{ $tahunPilih }} (Rp)</th>
                                <th width="25%">Target Investasi<br>Tahun {{ $tahunPilih }} (Rp)</th>
                                <th width="10%">Ket</th>
                            </tr>
                            <tr class="fst-italic" style="background-color: #f8f9fa;">
                                <td>(1)</td>
                                <td>(2)</td>
                                <td>(3)</td>
                                <td>(4)</td>
                                <td>(5)</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1.</td>
                                <td>Penanaman Modal Asing (PMA)</td>
                                <td class="text-end pe-3 text-nowrap">{{ number_format($pmaRealisasi, 2, ',', '.') }}</td>
                                <td class="text-end pe-3 text-nowrap">{{ $pmaTarget > 0 ? number_format($pmaTarget, 2, ',', '.') : '-' }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="text-center">2.</td>
                                <td>Penanaman Modal Dalam Negeri (PMDN)</td>
                                <td class="text-end pe-3 text-nowrap">{{ number_format($pmdnRealisasi, 2, ',', '.') }}</td>
                                <td class="text-end pe-3 text-nowrap">{{ $pmdnTarget > 0 ? number_format($pmdnTarget, 2, ',', '.') : '-' }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                        <tfoot class="fw-bold text-center">
                            <tr>
                                <td colspan="2" style="letter-spacing: 2px;">J u m l a h</td>
                                <td class="text-end pe-3 text-primary text-nowrap">{{ number_format($totalRealisasi, 2, ',', '.') }}</td>
                                <td class="text-end pe-3 text-primary text-nowrap">{{ $totalTarget > 0 ? number_format($totalTarget, 2, ',', '.') : '-' }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <small class="text-muted mt-2 d-block">* Data Target Investasi dapat disesuaikan jika sudah tersedia pada database.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
