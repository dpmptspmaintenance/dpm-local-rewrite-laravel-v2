<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Izin Nakes Per Bulan (Ditolak & Diterbitkan) — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.mppd-permohonan-per-bulan-horizontal') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Pilih Tahun</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahun ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                                @if ($listTahun->isEmpty())
                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.overview.mppd-permohonan-per-bulan-horizontal-export', ['tahun' => $tahun]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-uppercase mb-0">Permohonan SIP Per Bulan (Ditolak & Diterbitkan)</h5>
                    <small class="text-muted">Tahun: {{ $tahun }} | Total Jabatan: {{ count($dataMatrix) }}</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0 text-center" style="border-color: #aaa; font-size: 0.88rem;">
                        <thead class="table-secondary fw-bold" style="border-bottom: 2px solid #000;">
                            <tr>
                                <th rowspan="2" width="4%" class="align-middle">No</th>
                                <th rowspan="2" width="20%" class="align-middle text-start ps-3">Jabatan / Profesi</th>
                                @foreach ($bulanAktif as $b)
                                    <th colspan="2" class="align-middle bg-primary text-white">{{ $listBulanNama[$b] }}</th>
                                @endforeach
                                <th colspan="3" class="align-middle bg-dark text-white">Total Keseluruhan</th>
                            </tr>
                            <tr>
                                @foreach ($bulanAktif as $b)
                                    <th width="6%" class="align-middle bg-light text-danger">Ditolak</th>
                                    <th width="6%" class="align-middle bg-light text-success">Diterbitkan</th>
                                @endforeach
                                <th width="7%" class="align-middle bg-light text-danger">Ditolak</th>
                                <th width="7%" class="align-middle bg-light text-success">Diterbitkan</th>
                                <th width="8%" class="align-middle bg-light">Grand Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @if (!empty($dataMatrix))
                                @foreach ($dataMatrix as $jabatan => $row)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td class="text-start ps-3 fw-bold">{{ $jabatan }}</td>
                                        @foreach ($bulanAktif as $b)
                                            @php $tolakVal = $row['bulan'][$b]['tolak']; $terbitVal = $row['bulan'][$b]['terbit']; @endphp
                                            <td class="text-danger">{{ $tolakVal == 0 ? '-' : number_format($tolakVal, 0, ',', '.') }}</td>
                                            <td class="text-success">{{ $terbitVal == 0 ? '-' : number_format($terbitVal, 0, ',', '.') }}</td>
                                        @endforeach
                                        <td class="fw-bold text-danger">{{ $row['total_tolak'] == 0 ? '-' : number_format($row['total_tolak'], 0, ',', '.') }}</td>
                                        <td class="fw-bold text-success">{{ $row['total_terbit'] == 0 ? '-' : number_format($row['total_terbit'], 0, ',', '.') }}</td>
                                        <td class="fw-bold bg-light text-primary">{{ number_format($row['grand_total'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="{{ 2 + (count($bulanAktif) * 2) + 3 }}" class="py-5 text-muted fst-italic">
                                        Data tidak ditemukan pada periode tahun {{ $tahun }}.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="fw-bold bg-light" style="border-top: 2px solid #000;">
                            <tr>
                                <td colspan="2" class="text-end pe-3">TOTAL KESELURUHAN</td>
                                @foreach ($bulanAktif as $b)
                                    <td class="text-danger">{{ number_format($totalPerBulan[$b]['tolak'], 0, ',', '.') }}</td>
                                    <td class="text-success">{{ number_format($totalPerBulan[$b]['terbit'], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-danger fs-6">{{ number_format($totTolakGlobal, 0, ',', '.') }}</td>
                                <td class="text-success fs-6">{{ number_format($totTerbitGlobal, 0, ',', '.') }}</td>
                                <td class="fs-6 text-primary bg-warning text-dark">{{ number_format($grandTotalGlobal, 0, ',', '.') }}</td>
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
