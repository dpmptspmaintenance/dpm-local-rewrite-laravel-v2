<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan SLF & PBG (Filter Status) — Data Kita</title>
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
                <h5 class="card-title mb-0"><i class="bi bi-calendar-event"></i> Filter Tahun</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.simbg-slf-pbg-pertahun') }}" method="GET">
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
                            <a href="{{ route('datakita.overview.simbg-slf-pbg-pertahun-export', ['tahun' => $tahun]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Rekapitulasi SLF & PBG Tahun {{ $tahun }}</h5>
                <small class="text-muted">
                    Kota Semarang | Filter: Status mengandung kata <strong>"SLF"</strong> & <strong>"PBG"</strong>
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0 text-center">
                        <thead class="table-secondary" style="border-bottom: 2px solid #000;">
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Bulan</th>
                                <th width="25%">SLF<br><small>(Like %slf%)</small></th>
                                <th width="25%">PBG<br><small>(Like %pbg%)</small></th>
                                <th width="20%">Jumlah Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $slf = $dataBulan[$m];
                                    $pbg = $dataBulanPbg[$m];
                                    $sum = $slf + $pbg;
                                @endphp
                                <tr>
                                    <td>{{ $m }}</td>
                                    <td class="text-start ps-4">{{ $namaBulan[$m] }}</td>
                                    <td>{{ $slf > 0 ? number_format($slf, 0, ',', '.') : '-' }}</td>
                                    <td>{{ $pbg > 0 ? number_format($pbg, 0, ',', '.') : '-' }}</td>
                                    <td class="fw-bold">{{ $sum > 0 ? number_format($sum, 0, ',', '.') : '-' }}</td>
                                </tr>
                            @endfor
                        </tbody>
                        <tfoot style="border-top: 2px solid #000;">
                            <tr class="fw-bold bg-light">
                                <td colspan="2" class="text-start ps-3">Total Tahunan</td>
                                <td>{{ number_format(array_sum($stats['slf']), 0, ',', '.') }}</td>
                                <td>{{ number_format(array_sum($stats['pbg']), 0, ',', '.') }}</td>
                                <td>{{ number_format(array_sum($stats['total']), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-start ps-3">Minimal / Bulan</td>
                                <td>{{ min($stats['slf']) }}</td>
                                <td>{{ min($stats['pbg']) }}</td>
                                <td>{{ min($stats['total']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-start ps-3">Maksimal / Bulan</td>
                                <td>{{ max($stats['slf']) }}</td>
                                <td>{{ max($stats['pbg']) }}</td>
                                <td>{{ max($stats['total']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-start ps-3">Rata-rata / Bulan</td>
                                <td>{{ round(array_sum($stats['slf']) / 12) }}</td>
                                <td>{{ round(array_sum($stats['pbg']) / 12) }}</td>
                                <td>{{ round(array_sum($stats['total']) / 12) }}</td>
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
