<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sebaran SIP Per Kelurahan (Rapi) — Data Kita</title>
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
                <form action="{{ route('datakita.overview.mppd-faskes-per-kecamatan') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Tahun</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahun ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Bulan</label>
                            <select class="form-select" name="bulan">
                                <option value="">-- Semua Bulan --</option>
                                @foreach ($listBulan as $kode => $nama)
                                    <option value="{{ $kode }}" {{ $kode == $bulan ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Kecamatan Faskes</label>
                            <select class="form-select" name="kecamatan">
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach ($listKecamatan as $kec)
                                    <option value="{{ $kec }}" {{ $kec == $kecamatan ? 'selected' : '' }}>{{ ucwords(strtolower($kec)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.overview.mppd-faskes-per-kecamatan-export', ['tahun' => $tahun, 'bulan' => $bulan, 'kecamatan' => $kecamatan]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                </form>
                @if (empty($kecamatan))
                    <div class="alert alert-info py-2 mb-0"><small><i class="bi bi-info-circle"></i> Pilih Kecamatan untuk hasil yang lebih spesifik.</small></div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Sebaran SIP Faskes {{ $kecamatan }}</h5>
                <small class="text-muted">{{ $judulPeriode }} | Total Data: {{ number_format($grandTotal, 0, ',', '.') }}</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0 text-center" style="border-color: #999;">
                        <thead class="table-secondary fw-bold" style="border-bottom: 2px solid #000;">
                            <tr>
                                <th rowspan="2" width="5%" class="align-middle">No</th>
                                <th rowspan="2" width="20%" class="align-middle text-start ps-3">Kelurahan</th>
                                <th rowspan="2" width="25%" class="align-middle text-start ps-3">Kategori</th>
                                <th colspan="4">Status Permohonan</th>
                                <th rowspan="2" width="8%" class="align-middle">Total</th>
                                <th rowspan="2" width="10%" class="align-middle">Persentase</th>
                            </tr>
                            <tr>
                                <th width="8%" class="bg-light">Dibatalkan</th>
                                <th width="8%" class="bg-light">Ditolak</th>
                                <th width="8%" class="bg-light">Verifikasi<br>DPMPTSP</th>
                                <th width="8%" class="bg-light">SK<br>Diterbitkan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @if ($rows->isNotEmpty())
                                @foreach ($rows as $row)
                                    @php
                                        $persen = ($grandTotal > 0) ? ($row->total_per_row / $grandTotal) * 100 : 0;
                                        $kelurahanClean = ucwords(strtolower($row->kelurahan));
                                        $kategoriClean = ucwords(strtolower($row->kategori));
                                    @endphp
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td class="text-start ps-3">{{ $kelurahanClean }}</td>
                                        <td class="text-start ps-3">{{ $kategoriClean }}</td>
                                        <td>{{ $row->stat_batal == 0 ? '-' : $row->stat_batal }}</td>
                                        <td>{{ $row->stat_tolak == 0 ? '-' : $row->stat_tolak }}</td>
                                        <td>{{ $row->stat_verif == 0 ? '-' : $row->stat_verif }}</td>
                                        <td>{{ $row->stat_terbit == 0 ? '-' : $row->stat_terbit }}</td>
                                        <td class="fw-bold">{{ $row->total_per_row }}</td>
                                        <td>{{ round($persen) }}%</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="9" class="py-5 text-muted fst-italic">Data tidak ditemukan.</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="fw-bold bg-light" style="border-top: 2px solid #000;">
                            <tr>
                                <td colspan="3" class="text-start ps-3">Total Permohonan</td>
                                <td>{{ $totBatal }}</td>
                                <td>{{ $totTolak }}</td>
                                <td>{{ $totVerif }}</td>
                                <td>{{ $totTerbit }}</td>
                                <td class="fs-5 text-primary">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td class="bg-secondary"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-start ps-3">Persentase</td>
                                <td>{{ ($grandTotal > 0) ? round(($totBatal / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totTolak / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totVerif / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totTerbit / $grandTotal) * 100) : 0 }}%</td>
                                <td colspan="2" class="bg-secondary"></td>
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
