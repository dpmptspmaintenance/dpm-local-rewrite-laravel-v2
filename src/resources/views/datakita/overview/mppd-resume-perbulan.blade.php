<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Permohonan SIP Per Bulan — Data Kita</title>
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
                <h5 class="card-title mb-0"><i class="bi bi-calendar-event"></i> Filter Tahun</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.mppd-resume-perbulan') }}" method="GET">
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
                            <a href="{{ route('datakita.overview.mppd-resume-perbulan-export', ['tahun' => $tahun]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Status Permohonan SIP per Bulan</h5>
                <small class="text-muted">Total Data Tahun {{ $tahun }}: <strong>{{ number_format($grandTotal, 0, ',', '.') }}</strong> Permohonan</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 text-center" style="border-color: #000;">
                        <thead class="bg-secondary text-white fw-bold">
                            <tr>
                                <th rowspan="2" width="5%" class="align-middle bg-light text-dark">No</th>
                                <th rowspan="2" width="15%" class="align-middle bg-light text-dark">Bulan</th>
                                <th colspan="4" class="bg-light text-dark">Jumlah Status Permohonan</th>
                                <th colspan="2" class="bg-light text-dark">Total & Persentase Permohonan</th>
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
                            @php
                                $totalBatal = 0; $totalTolak = 0; $totalVerif = 0; $totalTerbit = 0;
                            @endphp
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $d = $dataBulan[$m];
                                    $rowTotal = $d['batal'] + $d['tolak'] + $d['verif'] + $d['terbit'];
                                    $perc = ($grandTotal > 0) ? ($rowTotal / $grandTotal) * 100 : 0;
                                    $totalBatal += $d['batal']; $totalTolak += $d['tolak'];
                                    $totalVerif += $d['verif']; $totalTerbit += $d['terbit'];
                                @endphp
                                <tr>
                                    <td>{{ $m }}</td>
                                    <td class="text-start ps-3">{{ $namaBulan[$m] }}</td>
                                    <td>{{ $d['batal'] == 0 ? '-' : $d['batal'] }}</td>
                                    <td>{{ $d['tolak'] == 0 ? '-' : $d['tolak'] }}</td>
                                    <td>{{ $d['verif'] == 0 ? '-' : $d['verif'] }}</td>
                                    <td>{{ $d['terbit'] == 0 ? '-' : $d['terbit'] }}</td>
                                    <td class="fw-bold">{{ $rowTotal == 0 ? '-' : $rowTotal }}</td>
                                    <td>{{ $rowTotal == 0 ? '-' : round($perc).'%' }}</td>
                                </tr>
                            @endfor
                        </tbody>
                        <tfoot class="fw-bold" style="border-top: 2px solid #000;">
                            <tr class="bg-light">
                                <td colspan="2" rowspan="2" class="align-middle text-center">Total & Persentase Permohonan</td>
                                <td>{{ $totalBatal }}</td>
                                <td>{{ $totalTolak }}</td>
                                <td>{{ $totalVerif }}</td>
                                <td>{{ number_format($totalTerbit, 0, ',', '.') }}</td>
                                <td rowspan="2" class="align-middle fs-5 text-primary">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td rowspan="2" class="bg-secondary"></td>
                            </tr>
                            <tr class="bg-light">
                                <td>{{ ($grandTotal > 0) ? round(($totalBatal / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totalTolak / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totalVerif / $grandTotal) * 100) : 0 }}%</td>
                                <td>{{ ($grandTotal > 0) ? round(($totalTerbit / $grandTotal) * 100) : 0 }}%</td>
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
