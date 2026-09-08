<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan SIP Per Kecamatan — Data Kita</title>
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
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Data</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.mppd-sip-perkecamatan') }}" method="GET">
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
                        <div class="col-md-5 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.overview.mppd-sip-perkecamatan-export', ['tahun' => $tahun]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Permohonan SIP per Kecamatan</h5>
                <small class="text-muted">Tahun: {{ $tahun }} | Berdasarkan Alamat/Tempat Praktik</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-secondary text-center" style="border-bottom: 2px solid #000;">
                            <tr>
                                <th width="5%">No</th>
                                <th width="50%" class="text-start ps-4">Kecamatan Fasilitas Kesehatan</th>
                                <th width="25%">Jumlah Permohonan</th>
                                <th width="20%">Presentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @if (count($rows) > 0)
                                @foreach ($rows as $row)
                                    @php $persen = ($total > 0) ? ($row['jumlah'] / $total) * 100 : 0; @endphp
                                    <tr>
                                        <td class="text-center">{{ $no++ }}</td>
                                        <td class="fw-bold ps-4">{{ $row['kecamatan'] }}</td>
                                        <td class="text-center">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                                        <td class="text-center">{{ round($persen) }}%</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="4" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="fw-bold bg-light" style="border-top: 2px solid #000;">
                            <tr>
                                <td colspan="2" class="text-center text-uppercase">TOTAL</td>
                                <td class="text-center fs-5 text-primary">{{ number_format($total, 0, ',', '.') }}</td>
                                <td class="text-center bg-secondary text-white">100%</td>
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
