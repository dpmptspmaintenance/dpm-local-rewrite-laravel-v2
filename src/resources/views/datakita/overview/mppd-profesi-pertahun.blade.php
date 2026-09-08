<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Kategori Tenaga Kesehatan (Profesi) — Data Kita</title>
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
                <form action="{{ route('datakita.overview.mppd-profesi-pertahun') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tahun</label>
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
                            <label class="form-label fw-bold">Bulan</label>
                            <select class="form-select" name="bulan">
                                <option value="">-- Semua Bulan --</option>
                                @foreach ($listBulan as $kode => $nama)
                                    <option value="{{ $kode }}" {{ $kode == $bulan ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.overview.mppd-profesi-pertahun-export', ['tahun' => $tahun, 'bulan' => $bulan]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Profil Kategori Tenaga Kesehatan (Profesi)</h5>
                <small class="text-muted">Periode: {{ $labelPeriode }} | Total Permohonan: {{ number_format($grandTotal, 0, ',', '.') }}</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0 text-center">
                        <thead class="bg-secondary text-white fw-bold">
                            <tr>
                                <th rowspan="2" width="5%" class="align-middle bg-light text-dark">No</th>
                                <th rowspan="2" width="35%" class="align-middle bg-light text-dark text-start ps-4">Kategori Tenaga Kesehatan</th>
                                <th colspan="4" class="bg-light text-dark">Jumlah Status Permohonan</th>
                                <th colspan="2" class="bg-light text-dark">Total & Persentase</th>
                            </tr>
                            <tr>
                                <th width="10%" class="bg-light text-dark">Dibatalkan</th>
                                <th width="10%" class="bg-light text-dark">Ditolak</th>
                                <th width="10%" class="bg-light text-dark">Verifikasi<br>DPMPTSP</th>
                                <th width="10%" class="bg-light text-dark">SK<br>Diterbitkan</th>
                                <th width="10%" class="bg-light text-dark">Total</th>
                                <th width="10%" class="bg-light text-dark">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @if ($rows->isNotEmpty())
                                @foreach ($rows as $row)
                                    @php
                                        $namaProfesi = !empty($row->profesi) ? $row->profesi : '<em>(Tidak Disebutkan)</em>';
                                        $persen = ($grandTotal > 0) ? ($row->total_per_profesi / $grandTotal) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td class="text-start ps-4 fw-bold">{!! $namaProfesi !!}</td>
                                        <td>{{ $row->jml_batal == 0 ? '-' : $row->jml_batal }}</td>
                                        <td>{{ $row->jml_tolak == 0 ? '-' : $row->jml_tolak }}</td>
                                        <td>{{ $row->jml_verif == 0 ? '-' : $row->jml_verif }}</td>
                                        <td>{{ $row->jml_terbit == 0 ? '-' : number_format($row->jml_terbit, 0, ',', '.') }}</td>
                                        <td class="fw-bold">{{ number_format($row->total_per_profesi, 0, ',', '.') }}</td>
                                        <td>{{ round($persen) }}%</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="8" class="py-4 text-muted">Data tidak ditemukan.</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="fw-bold bg-light" style="border-top: 2px solid #000;">
                            <tr>
                                <td colspan="2" class="text-end pe-4">Total & Persentase</td>
                                <td>{{ $totalBatal }}</td>
                                <td>{{ $totalTolak }}</td>
                                <td>{{ $totalVerif }}</td>
                                <td>{{ number_format($totalTerbit, 0, ',', '.') }}</td>
                                <td class="fs-5 text-primary">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td class="bg-secondary"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                                <td>{{ ($grandTotal > 0) ? round(($totalBatal / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totalTolak / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totalVerif / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totalTerbit / $grandTotal) * 100) : 0 }}%</td>
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
