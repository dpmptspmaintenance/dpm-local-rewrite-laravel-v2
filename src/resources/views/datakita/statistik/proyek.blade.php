<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Proyek — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body { font-family: "Moderustic", sans-serif; }

        .tbl-report {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            font-family: Arial, sans-serif;
            text-align: center;
            font-size: 12px;
        }

        .tbl-report th,
        .tbl-report td {
            border: 1px solid #333;
            padding: 5px;
        }

        .bg-red { background-color: #e5536b; color: white; }
        .bg-yellow { background-color: #e5d836; font-weight: bold; }
        .bg-grey { background-color: #eaeaea; }

        .card-custom {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">

        <div class="mb-4 bg-light p-3 rounded shadow-sm d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('datakita.statistik.proyek') }}" class="d-flex align-items-center mb-0">
                <label for="tahun" class="me-2 fw-bold" style="font-size: 16px;">Tahun Data:</label>
                <select name="tahun" id="tahun" class="form-select w-auto" onchange="this.form.submit()">
                    @foreach ($yearsList as $thn)
                        <option value="{{ $thn }}" {{ $thn == $selectedYear ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                </select>
            </form>
            <div>
                <a href="{{ route('datakita.statistik.proyek-download-word', ['tahun' => $selectedYear]) }}" class="btn btn-primary me-2"><i class="bi bi-file-earmark-word"></i> Download Word</a>
                <a href="{{ route('datakita.statistik.proyek-download-excel', ['tahun' => $selectedYear]) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Download Excel</a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card-custom table-responsive">
                    <table class="tbl-report">
                        <thead>
                            <tr>
                                <td rowspan="16" class="bg-red text-center" style="width: 30%; vertical-align: middle; padding: 20px;">
                                    <h4 class="fw-bold">DATA<br>STATISTIK<br>PERUSAHAAN</h4>
                                    <p style="font-size: 11px;">Sumber Data : OSS - RBA<br>Sumber File : DP.Proyek.xlsx</p>
                                    <h5 class="fw-bold mt-4">JUMLAH PROYEK</h5>
                                </td>
                                <th rowspan="2" class="bg-grey">NO</th>
                                <th rowspan="2" class="bg-grey">TAHUN</th>
                                <th rowspan="2" class="bg-grey">BULAN</th>
                                <th colspan="1" class="bg-grey">JUMLAH</th>
                            </tr>
                            <tr>
                                <th class="bg-grey">Per Bulan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $jml = $dataProyek[$i] ?? 0;
                                    $pct = $totalProyek > 0 ? ($jml / $totalProyek) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $i }}</td>
                                    <td class="text-center">{{ $selectedYear }}</td>
                                    <td class="text-center">{{ $i }}</td>
                                    <td class="text-end">{{ $jml > 0 ? number_format($jml, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ $jml > 0 ? number_format($pct, 2, ',', '.').'%' : '-' }}</td>
                                </tr>
                            @endfor

                            <tr>
                                <td colspan="3" class="bg-yellow fw-bold text-start ps-3">JUMLAH</td>
                                <td colspan="2" class="bg-yellow text-end fw-bold pe-3">{{ number_format($totalProyek, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="bg-yellow fw-bold text-start ps-3">RATA - RATA</td>
                                <td colspan="2" class="bg-yellow text-end fw-bold pe-3">{{ number_format($statProyek['avg'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="bg-yellow fw-bold text-start ps-3">TERTINGGI</td>
                                <td colspan="2" class="bg-yellow text-end fw-bold pe-3">{{ number_format($statProyek['max'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="bg-yellow fw-bold text-start ps-3">TERENDAH</td>
                                <td colspan="2" class="bg-yellow text-end fw-bold pe-3">{{ $statProyek['min'] > 0 ? number_format($statProyek['min'], 0, ',', '.') : '0' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom" style="background: linear-gradient(to bottom, #d6495c, #e0d080); height: 100%; min-height: 400px; padding: 20px;">
                    <h5 class="text-center text-white fw-bold mt-2 mb-4">JUMLAH PROYEK PER BULAN</h5>
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="chartProyek"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card-custom table-responsive">
                    <table class="tbl-report">
                        <tr>
                            <td rowspan="10" colspan="2" class="bg-red" style="width: 35%;">
                                <h4>DATA<br>STATISTIK<br>PERUSAHAAN</h4>
                                <p style="font-size: 11px;">Sumber Data : OSS - RBA<br>Sumber File : DP.Proyek.xlsx</p>
                                <h5>NILAI INVESTASI (Rp.)</h5>
                            </td>
                            <th rowspan="2" class="bg-grey">NO</th>
                            <th rowspan="2" class="bg-grey">TAHUN</th>
                            <th rowspan="2" class="bg-grey">BULAN</th>
                            <th colspan="2" class="bg-grey">JUMLAH (Rp.)</th>
                        </tr>
                        <tr>
                            <th class="bg-grey">Per Bulan</th>
                            <th class="bg-grey">%</th>
                        </tr>
                        @for ($i = 1; $i <= 12; $i++)
                            @php
                                $inv = $dataInvestasi[$i];
                                $pctInv = $totalInvestasi > 0 ? ($inv / $totalInvestasi) * 100 : 0;
                            @endphp
                            <tr>
                                @if ($i == 9)
                                    <td class="bg-yellow text-start">JUMLAH</td>
                                    <td class="bg-yellow text-end">{{ number_format($totalInvestasi, 0, ',', '.') }}</td>
                                @elseif ($i == 10)
                                    <td class="bg-yellow text-start">RATA - RATA</td>
                                    <td class="bg-yellow text-end">{{ number_format($statInvestasi['avg'], 0, ',', '.') }}</td>
                                @elseif ($i == 11)
                                    <td class="bg-yellow text-start">TERTINGGI</td>
                                    <td class="bg-yellow text-end">{{ number_format($statInvestasi['max'], 0, ',', '.') }}</td>
                                @elseif ($i == 12)
                                    <td class="bg-yellow text-start">TERENDAH</td>
                                    <td class="bg-yellow text-end">{{ number_format($statInvestasi['min'], 0, ',', '.') }}</td>
                                @endif

                                <td>{{ $i }}</td>
                                <td>{{ $selectedYear }}</td>
                                <td>{{ $i }}</td>
                                <td class="text-end">{{ $inv > 0 ? number_format($inv, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ $inv > 0 ? number_format($pctInv, 2, ',', '.').'%' : '' }}</td>
                            </tr>
                        @endfor
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom" style="background: linear-gradient(to bottom, #d6495c, #94c180);">
                    <h5 class="text-center text-white fw-bold mt-2 mb-3">NILAI INVESTASI PER BULAN (Rp.)</h5>
                    <div class="chart-container">
                        <canvas id="chartInvestasi"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card-custom table-responsive">
                    <table class="tbl-report" style="min-width: 1000px;">
                        <tr>
                            <th colspan="20" class="bg-red">URAIAN RISIKO PROYEK (JUMLAH)<br><span style="font-size:10px; font-weight:normal;">Sumber Data : OSS - RBA | Sumber File : DP.Proyek.xlsx</span></th>
                        </tr>
                        <tr>
                            <th rowspan="3" class="bg-grey">No</th>
                            <th rowspan="3" class="bg-grey" style="min-width: 200px;">Uraian Risiko Proyek</th>
                            <th colspan="12" class="bg-grey">TAHUN : {{ $selectedYear }}</th>
                            <th rowspan="3" class="bg-yellow">Jumlah</th>
                            <th rowspan="3" class="bg-yellow">% JUMLAH</th>
                            <th rowspan="3" class="bg-yellow">RATA - RATA</th>
                            <th rowspan="3" class="bg-yellow">TERTINGGI</th>
                            <th rowspan="3" class="bg-yellow">TERENDAH</th>
                        </tr>
                        <tr>
                            <th colspan="12" class="bg-grey">BULAN</th>
                        </tr>
                        <tr>
                            @for ($i = 1; $i <= 12; $i++)
                                <th class="bg-grey">{{ $i }}</th>
                            @endfor
                        </tr>

                        @php $no = 1; @endphp
                        @foreach ($risikoList as $rs)
                            @php
                                $arrBulanan = $dataRisikoProyek[$rs];
                                $totRs = array_sum($arrBulanan);
                                $pctRs = $totalProyek > 0 ? ($totRs / $totalProyek) * 100 : 0;
                                $active = array_filter($arrBulanan, fn ($v) => $v > 0);
                                $statRs = [
                                    'avg' => count($active) > 0 ? round(array_sum($active) / count($active)) : 0,
                                    'max' => count($active) > 0 ? max($active) : 0,
                                    'min' => count($active) > 0 ? min($active) : 0,
                                ];
                            @endphp
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td class="text-start">{{ $rs }}</td>
                                @for ($i = 1; $i <= 12; $i++)
                                    <td class="text-end">{{ $arrBulanan[$i] > 0 ? number_format($arrBulanan[$i], 0, ',', '.') : '-' }}</td>
                                @endfor
                                <td class="bg-grey text-end fw-bold">{{ number_format($totRs, 0, ',', '.') }}</td>
                                <td class="bg-grey text-end">{{ number_format($pctRs, 2, ',', '.').'%' }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statRs['avg'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statRs['max'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ $statRs['min'] > 0 ? number_format($statRs['min'], 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <th colspan="2" class="bg-yellow text-center">JUMLAH TOTAL</th>
                            @for ($i = 1; $i <= 12; $i++)
                                <td class="bg-yellow text-end">{{ $dataProyek[$i] > 0 ? number_format($dataProyek[$i], 0, ',', '.') : '-' }}</td>
                            @endfor
                            <td class="bg-yellow text-end">{{ number_format($totalProyek, 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end"></td>
                            <td class="bg-yellow text-end">{{ number_format($statProyek['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ number_format($statProyek['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ $statProyek['min'] > 0 ? number_format($statProyek['min'], 0, ',', '.') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card-custom table-responsive">
                    <table class="tbl-report" style="min-width: 1000px;">
                        <tr>
                            <th colspan="20" class="bg-red">URAIAN RISIKO PROYEK (NILAI INVESTASI Rp.)<br><span style="font-size:10px; font-weight:normal;">Sumber Data : OSS - RBA | Sumber File : DP.Proyek.xlsx</span></th>
                        </tr>
                        <tr>
                            <th rowspan="3" class="bg-grey">No</th>
                            <th rowspan="3" class="bg-grey" style="min-width: 200px;">Uraian Risiko Proyek</th>
                            <th colspan="12" class="bg-grey">TAHUN : {{ $selectedYear }}</th>
                            <th rowspan="3" class="bg-yellow">Jumlah (Rp.)</th>
                            <th rowspan="3" class="bg-yellow">% JUMLAH</th>
                            <th rowspan="3" class="bg-yellow">RATA - RATA (Rp.)</th>
                            <th rowspan="3" class="bg-yellow">TERTINGGI (Rp.)</th>
                            <th rowspan="3" class="bg-yellow">TERENDAH (Rp.)</th>
                        </tr>
                        <tr>
                            <th colspan="12" class="bg-grey">BULAN</th>
                        </tr>
                        <tr>
                            @for ($i = 1; $i <= 12; $i++)
                                <th class="bg-grey">{{ $i }}</th>
                            @endfor
                        </tr>

                        @php $no = 1; @endphp
                        @foreach ($risikoList as $rs)
                            @php
                                $arrBulananInv = $dataRisikoInvestasi[$rs];
                                $totRsInv = array_sum($arrBulananInv);
                                $pctRsInv = $totalInvestasi > 0 ? ($totRsInv / $totalInvestasi) * 100 : 0;
                                $active = array_filter($arrBulananInv, fn ($v) => $v > 0);
                                $statRsInv = [
                                    'avg' => count($active) > 0 ? round(array_sum($active) / count($active)) : 0,
                                    'max' => count($active) > 0 ? max($active) : 0,
                                    'min' => count($active) > 0 ? min($active) : 0,
                                ];
                            @endphp
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td class="text-start">{{ $rs }}</td>
                                @for ($i = 1; $i <= 12; $i++)
                                    <td class="text-end">{{ $arrBulananInv[$i] > 0 ? number_format($arrBulananInv[$i], 0, ',', '.') : '-' }}</td>
                                @endfor
                                <td class="bg-grey text-end fw-bold">{{ number_format($totRsInv, 0, ',', '.') }}</td>
                                <td class="bg-grey text-end">{{ number_format($pctRsInv, 2, ',', '.').'%' }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statRsInv['avg'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statRsInv['max'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ $statRsInv['min'] > 0 ? number_format($statRsInv['min'], 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <th colspan="2" class="bg-yellow text-center">JUMLAH TOTAL</th>
                            @for ($i = 1; $i <= 12; $i++)
                                <td class="bg-yellow text-end">{{ $dataInvestasi[$i] > 0 ? number_format($dataInvestasi[$i], 0, ',', '.') : '-' }}</td>
                            @endfor
                            <td class="bg-yellow text-end">{{ number_format($totalInvestasi, 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end"></td>
                            <td class="bg-yellow text-end">{{ number_format($statInvestasi['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ number_format($statInvestasi['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ $statInvestasi['min'] > 0 ? number_format($statInvestasi['min'], 0, ',', '.') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const labelsBulan = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        const dataProyek = @json(array_values($dataProyek));
        const dataInvestasi = @json(array_values($dataInvestasi));

        Chart.defaults.scale.grid.display = false;
        Chart.defaults.plugins.legend.display = false;

        const formatNilaiBesar = (value) => {
            if (value >= 1e12) return (value / 1e12).toFixed(2) + ' T';
            if (value >= 1e9) return (value / 1e9).toFixed(2) + ' M';
            if (value >= 1e6) return (value / 1e6).toFixed(2) + ' Jt';
            return value.toLocaleString('id-ID');
        };

        const pluginDatalabelsProyek = {
            id: 'topLabelsProyek',
            afterDatasetsDraw(chart, args, pluginOptions) {
                const { ctx, data } = chart;
                chart.getDatasetMeta(0).data.forEach((datapoint, index) => {
                    const value = data.datasets[0].data[index];
                    if (value > 0) {
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
                        ctx.fillRect(datapoint.x - 20, datapoint.y - 25, 40, 20);
                        ctx.font = 'bold 11px Arial';
                        ctx.fillStyle = 'black';
                        ctx.textAlign = 'center';
                        ctx.fillText(value.toLocaleString('id-ID'), datapoint.x, datapoint.y - 11);
                    }
                });
            }
        };

        const pluginDatalabelsInvestasi = {
            id: 'topLabelsInvestasi',
            afterDatasetsDraw(chart, args, pluginOptions) {
                const { ctx, data } = chart;
                chart.getDatasetMeta(0).data.forEach((datapoint, index) => {
                    const value = data.datasets[0].data[index];
                    if (value > 0) {
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
                        ctx.fillRect(datapoint.x - 25, datapoint.y - 25, 50, 20);
                        ctx.font = 'bold 10px Arial';
                        ctx.fillStyle = 'black';
                        ctx.textAlign = 'center';
                        ctx.fillText(formatNilaiBesar(value), datapoint.x, datapoint.y - 11);
                    }
                });
            }
        };

        const getChartMax = (arr) => {
            let maxVal = Math.max(...arr);
            return maxVal > 0 ? maxVal * 1.2 : 10;
        };

        new Chart(document.getElementById('chartProyek'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    data: dataProyek,
                    backgroundColor: '#e5d836',
                    borderColor: '#c0a820',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { display: false, suggestedMax: getChartMax(dataProyek) },
                    x: { ticks: { color: '#e5536b', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabelsProyek]
        });

        new Chart(document.getElementById('chartInvestasi'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    data: dataInvestasi,
                    backgroundColor: '#569bd5',
                    borderColor: '#30699c',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { display: false, suggestedMax: getChartMax(dataInvestasi) },
                    x: { ticks: { color: 'green', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabelsInvestasi]
        });
    </script>
</body>

</html>
