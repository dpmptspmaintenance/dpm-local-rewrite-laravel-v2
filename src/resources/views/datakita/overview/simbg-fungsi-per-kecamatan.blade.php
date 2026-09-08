<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Fungsi & Sub Fungsi (Per Kelurahan) — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Data (Semarang)</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.simbg-fungsi-per-kecamatan') }}" method="GET">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Tahun Reg.</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahun ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                                @if ($listTahun->isEmpty())
                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Kecamatan</label>
                            <select class="form-select" name="kecamatan" onchange="this.form.submit()">
                                <option value="">-- Semua Kecamatan --</option>
                                @foreach ($listKecamatan as $kec)
                                    <option value="{{ $kec }}" {{ $kec == $kecamatan ? 'selected' : '' }}>{{ $kec }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Kelurahan</label>
                            <select class="form-select" name="kelurahan" {{ $kecamatan == '' ? 'disabled' : '' }}>
                                <option value="">-- Semua Kelurahan --</option>
                                @foreach ($listKelurahan as $kel)
                                    <option value="{{ $kel }}" {{ $kel == $kelurahan ? 'selected' : '' }}>{{ $kel }}</option>
                                @endforeach
                            </select>
                            @if ($kecamatan == '')
                                <small class="text-muted" style="font-size: 0.75rem;">*Pilih Kecamatan dulu</small>
                            @endif
                        </div>

                        <div class="col-md-4 mb-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampil</button>
                            <a href="{{ route('datakita.overview.simbg-fungsi-per-kecamatan-export', ['tahun' => $tahun, 'kecamatan' => $kecamatan, 'kelurahan' => $kelurahan]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Rekap Fungsi & Sub Fungsi</h5>
                <small class="text-muted fw-bold text-primary">{{ $lblLokasi }} | Tahun: {{ $tahun }}</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-dark text-center">
                            <tr>
                                <th width="5%">No</th>
                                <th width="30%">Fungsi Bangunan</th>
                                <th width="45%">Sub Fungsi Bangunan</th>
                                <th width="20%">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @if ($rows->isNotEmpty())
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="text-center">{{ $no++ }}</td>
                                        <td class="fw-bold">{{ $row->fungsi_bangunan }}</td>
                                        <td>{{ $row->sub_fungsi_bangunan }}</td>
                                        <td class="text-center fw-bold">{{ number_format($row->jumlah, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Data tidak ditemukan.</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="3" class="text-end pe-3">TOTAL IZIN TERBIT</td>
                                <td class="text-center fs-5 text-primary">{{ number_format($total, 0, ',', '.') }}</td>
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
