<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Per Kecamatan (Tabel Terpisah) — Data Kita</title>
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
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Data</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.simbg-rekap-perkecamatan-pertahun') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Pilih Tahun</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahun ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.overview.simbg-rekap-perkecamatan-pertahun.export', ['tahun' => $tahun]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Rekapitulasi (Per Kecamatan)</h5>
                <small class="text-muted">Tahun: {{ $tahun }} | Nomor Urut Berlanjut</small>
            </div>
            <div class="card-body">
                @if ($rows->isEmpty())
                    <div class="alert alert-warning">Data tidak ditemukan.</div>
                @else
                    @php
                        $currentKecamatan = '';
                        $no = 1;
                        $isFirst = true;
                    @endphp
                    @foreach ($rows as $row)
                        @php $kec = $row->kecamatan_bangunan; @endphp
                        @if ($kec != $currentKecamatan)
                            @if (!$isFirst)
                                </tbody></table></div><br>
                            @endif
                            @php
                                $currentKecamatan = $kec;
                                $isFirst = false;
                            @endphp
                            <h5 class="fw-bold text-primary mb-2 text-uppercase"><i class="bi bi-geo-alt-fill"></i> KECAMATAN: {{ $kec }}</h5>
                            <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-secondary text-center">
                            <tr>
                                <th width="5%">No</th>
                                <th width="40%">Status</th>
                                <th width="40%">Fungsi Bangunan</th>
                                <th width="15%">Jumlah</th>
                            </tr>
                            </thead>
                            <tbody>
                        @endif
                        @php $fungsi = !empty($row->fungsi_bangunan) ? $row->fungsi_bangunan : '-'; @endphp
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $fungsi }}</td>
                            <td class="text-center fw-bold">{{ number_format($row->jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
                @endif
            </div>
            <div class="card-footer bg-dark text-white fw-bold d-flex justify-content-between">
                <span>TOTAL KESELURUHAN (SEMUA KECAMATAN)</span>
                <span>{{ number_format($total, 0, ',', '.') }} Izin</span>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
