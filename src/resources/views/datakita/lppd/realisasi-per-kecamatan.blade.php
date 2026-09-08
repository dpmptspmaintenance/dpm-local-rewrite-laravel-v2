<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realisasi Investasi Per Kecamatan (LPPD) — Data Kita</title>
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
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan Realisasi per Kecamatan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.lppd.realisasi-per-kecamatan.index') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tahun Pelaporan</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahunPilih ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.lppd.realisasi-per-kecamatan.export', ['tahun' => $tahunPilih]) }}" class="btn btn-success" target="_blank">
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
                    <h6 class="fw-bold mb-0">REALISASI INVESTASI PER KECAMATAN</h6>
                    <h6 class="fw-bold mb-0">TAHUN {{ $tahunPilih }} (TAHUN PELAPORAN)</h6>
                    <h6 class="fw-bold mb-0">KAB/KOTA SEMARANG</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #000;">
                        <thead class="text-center fw-bold align-middle bg-light">
                            <tr>
                                <th width="5%" rowspan="2">No</th>
                                <th width="35%" rowspan="2">Kecamatan</th>
                                <th colspan="2">Jenis Penanaman Modal (Rp)</th>
                                <th width="20%" rowspan="2">Total Investasi (Rp)</th>
                            </tr>
                            <tr>
                                <th width="20%">PMA (Asing)</th>
                                <th width="20%">PMDN (Dalam Negeri)</th>
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
                            @forelse ($dataLaporan as $row)
                                @php
                                    $kecamatanClean = ucwords(strtolower($row->nama_kecamatan));
                                    $kecClass = $row->nama_kecamatan == 'Tidak Terdeteksi' ? 'text-danger fst-italic' : 'fw-bold';
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="ps-4 {{ $kecClass }}">{{ $kecamatanClean }}</td>
                                    <td class="text-end pe-3 text-nowrap">{{ $row->pma > 0 ? number_format($row->pma, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end pe-3 text-nowrap">{{ $row->pmdn > 0 ? number_format($row->pmdn, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end pe-3 fw-bold text-nowrap">{{ number_format($row->total_investasi, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Data tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="fw-bold text-center bg-light">
                            <tr>
                                <td colspan="2" style="letter-spacing: 2px;">TOTAL KESELURUHAN</td>
                                <td class="text-end pe-3 text-nowrap">{{ number_format($totPma, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 text-nowrap">{{ number_format($totPmdn, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 fs-6 text-primary text-nowrap">{{ number_format($grandTotal, 0, ',', '.') }}</td>
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
