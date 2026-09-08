<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Kantor — Data Kita</title>
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

        .bg-red {
            background-color: #e5536b;
            color: white;
        }

        .bg-yellow {
            background-color: #e5d836;
            font-weight: bold;
        }

        .bg-grey {
            background-color: #eaeaea;
        }

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
            height: 250px;
            width: 100%;
        }

        .chart-container-large {
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
            <form method="GET" action="{{ route('datakita.statistik.kantor') }}" class="d-flex align-items-center mb-0">
                <label for="tahun" class="me-2 fw-bold" style="font-size: 16px;">Tahun Data:</label>
                <select name="tahun" id="tahun" class="form-select w-auto" onchange="this.form.submit()">
                    @foreach ($yearsList as $thn)
                        <option value="{{ $thn }}" {{ $thn == $selectedYear ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                </select>
            </form>
            <div>
                <a href="{{ route('datakita.statistik.kantor-download-excel', ['tahun' => $selectedYear]) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Download Excel</a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card-custom table-responsive">
                    <table class="tbl-report">
                        <tr>
                            <td rowspan="10" colspan="2" class="bg-red" style="width: 35%;">
                                <h4>DATA<br>STATISTIK<br>PERUSAHAAN</h4>
                                <p style="font-size: 11px;">Sumber Data : OSS - RBA<br>Sumber File : DP.NIB Kantor</p>
                                <h5>JUMLAH NIB</h5>
                            </td>
                            <th rowspan="2" class="bg-grey">NO</th>
                            <th rowspan="2" class="bg-grey">TAHUN</th>
                            <th rowspan="2" class="bg-grey">BULAN</th>
                            <th colspan="2" class="bg-grey">JUMLAH</th>
                        </tr>
                        <tr>
                            <th class="bg-grey">Per Bulan</th>
                            <th class="bg-grey">%</th>
                        </tr>
                        @for ($i = 1; $i <= 12; $i++)
                            @php
                                $jml = $dataNib[$i];
                                $pct = $totalNib > 0 ? ($jml / $totalNib) * 100 : 0;
                            @endphp
                            <tr>
                                @if ($i == 9)
                                    <td class="bg-yellow text-start">JUMLAH</td>
                                    <td class="bg-yellow text-end">{{ number_format($totalNib, 0, ',', '.') }}</td>
                                @elseif ($i == 10)
                                    <td class="bg-yellow text-start">RATA - RATA</td>
                                    <td class="bg-yellow text-end">{{ number_format($statNib['avg'], 0, ',', '.') }}</td>
                                @elseif ($i == 11)
                                    <td class="bg-yellow text-start">TERTINGGI</td>
                                    <td class="bg-yellow text-end">{{ number_format($statNib['max'], 0, ',', '.') }}</td>
                                @elseif ($i == 12)
                                    <td class="bg-yellow text-start">TERENDAH</td>
                                    <td class="bg-yellow text-end">{{ number_format($statNib['min'], 0, ',', '.') }}</td>
                                @endif

                                <td>{{ $i }}</td>
                                <td>{{ $selectedYear }}</td>
                                <td>{{ $i }}</td>
                                <td class="text-end">{{ $jml > 0 ? number_format($jml, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ $jml > 0 ? number_format($pct, 2, ',', '.').'%' : '' }}</td>
                            </tr>
                        @endfor
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom" style="background: linear-gradient(to bottom, #d6495c, #e0d080);">
                    <h5 class="text-center text-white fw-bold mt-2 mb-3">JUMLAH NIB PER BULAN</h5>
                    <div class="chart-container-large">
                        <canvas id="chartNIB"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card-custom table-responsive">
                    <table class="tbl-report">
                        <tr>
                            <td rowspan="14" colspan="2" class="bg-red" style="width: 35%; padding: 20px; vertical-align: middle;">
                                <h4 style="font-weight: bold; margin-bottom: 20px; line-height: 1.4;">DATA<br>STATISTIK<br>PERUSAHAAN</h4>
                                <p style="font-size: 11px; font-weight: normal; margin-bottom: 20px;">Sumber Data : OSS - RBA<br>Sumber File : DP.NIB Kantor</p>
                                <h5 style="font-weight: bold; margin-bottom: 0;">JUMLAH STATUS<br>PENANAMAN MODAL</h5>
                            </td>
                            <th rowspan="2" class="bg-grey">No</th>
                            <th rowspan="2" class="bg-grey">TAHUN</th>
                            <th rowspan="2" class="bg-grey">BULAN</th>
                            <th colspan="4" class="bg-red">STATUS PENANAMAN MODAL</th>
                            <th rowspan="2" class="bg-red">JUMLAH</th>
                            <th rowspan="2" class="bg-red">% JUMLAH</th>
                        </tr>
                        <tr>
                            <th class="bg-red">PMA</th>
                            <th class="bg-red">% PMA</th>
                            <th class="bg-red">PMDN</th>
                            <th class="bg-red">% PMDN</th>
                        </tr>

                        @for ($i = 1; $i <= 12; $i++)
                            @php
                                $pma = $dataPma[$i];
                                $pmdn = $dataPmdn[$i];
                                $totalBaris = $pma + $pmdn;

                                $pctPma = $totalPma > 0 ? ($pma / $totalPma) * 100 : 0;
                                $pctPmdn = $totalPmdn > 0 ? ($pmdn / $totalPmdn) * 100 : 0;
                                $pctTotal = $totalNib > 0 ? ($totalBaris / $totalNib) * 100 : 0;
                            @endphp
                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $selectedYear }}</td>
                                <td>{{ $i }}</td>
                                <td class="text-end">{{ $pma > 0 ? number_format($pma, 0, ',', '.') : '-' }}</td>
                                <td class="text-end bg-grey">{{ $pma > 0 ? number_format($pctPma, 2, ',', '.').'%' : '' }}</td>
                                <td class="text-end">{{ $pmdn > 0 ? number_format($pmdn, 0, ',', '.') : '-' }}</td>
                                <td class="text-end bg-grey">{{ $pmdn > 0 ? number_format($pctPmdn, 2, ',', '.').'%' : '' }}</td>
                                <td class="text-end bg-grey fw-bold">{{ $totalBaris > 0 ? number_format($totalBaris, 0, ',', '.') : '-' }}</td>
                                <td class="text-end bg-red text-white">{{ $totalBaris > 0 ? number_format($pctTotal, 2, ',', '.').'%' : '' }}</td>
                            </tr>
                        @endfor

                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">JUMLAH</td>
                            <td class="bg-yellow text-end">{{ number_format($totalPma, 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                            <td class="bg-yellow text-end">{{ number_format($totalPmdn, 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                            <td class="bg-yellow text-end">{{ number_format($totalPma + $totalPmdn, 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">RATA - RATA</td>
                            <td class="bg-yellow text-end">{{ number_format($statPma['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                            <td class="bg-yellow text-end">{{ number_format($statPmdn['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                            <td class="bg-yellow text-end">{{ number_format($statNib['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">TERTINGGI</td>
                            <td class="bg-yellow text-end">{{ number_format($statPma['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                            <td class="bg-yellow text-end">{{ number_format($statPmdn['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                            <td class="bg-yellow text-end">{{ number_format($statNib['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">TERENDAH</td>
                            <td class="bg-yellow text-end">{{ $statPma['min'] > 0 ? number_format($statPma['min'], 0, ',', '.') : '-' }}</td>
                            <td class="bg-yellow"></td>

                            <td class="bg-yellow text-end">{{ $statPmdn['min'] > 0 ? number_format($statPmdn['min'], 0, ',', '.') : '-' }}</td>
                            <td class="bg-yellow"></td>

                            <td class="bg-yellow text-end">{{ isset($statNib['min']) && $statNib['min'] > 0 ? number_format($statNib['min'], 0, ',', '.') : '-' }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-custom d-flex flex-column gap-4" style="background: linear-gradient(to bottom, #d6495c, #94c180);">
                    <div style="flex: 1;">
                        <h5 class="text-center text-white fw-bold mt-2 mb-2">PMA</h5>
                        <div class="chart-container">
                            <canvas id="chartPMA"></canvas>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <h5 class="text-center text-white fw-bold mb-2">PMDN</h5>
                        <div class="chart-container">
                            <canvas id="chartPMDN"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card-custom table-responsive">
                    <table class="tbl-report" style="min-width: 1000px;">
                        <tr>
                            <th colspan="20" class="bg-red">DATA STATISTIK PERUSAHAAN<br><span style="font-size:10px; font-weight:normal;">Sumber Data : OSS - RBA | Sumber File : DP.NIB Kantor</span><br>JUMLAH JENIS PERUSAHAAN</th>
                        </tr>
                        <tr>
                            <th rowspan="3" class="bg-grey">No</th>
                            <th rowspan="3" class="bg-grey" style="min-width: 200px;">Uraian Jenis Perusahaan</th>
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
                        @foreach ($jenisList as $jp)
                            @php
                                $arrBulanan = $dataJenis[$jp];
                                $totJp = array_sum($arrBulanan);
                                $pctJp = $totalNib > 0 ? ($totJp / $totalNib) * 100 : 0;
                                $active = array_filter($arrBulanan, fn ($v) => $v > 0);
                                $statJp = [
                                    'avg' => count($active) > 0 ? round(array_sum($active) / count($active)) : 0,
                                    'max' => count($active) > 0 ? max($active) : 0,
                                    'min' => count($active) > 0 ? min($active) : 0,
                                ];
                            @endphp
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td class="text-start">{{ $jp }}</td>
                                @for ($i = 1; $i <= 12; $i++)
                                    <td class="text-end">{{ $arrBulanan[$i] > 0 ? number_format($arrBulanan[$i], 0, ',', '.') : '-' }}</td>
                                @endfor
                                <td class="bg-grey text-end fw-bold">{{ number_format($totJp, 0, ',', '.') }}</td>
                                <td class="bg-grey text-end">{{ $totJp > 0 ? number_format($pctJp, 2, ',', '.').'%' : '-' }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statJp['avg'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statJp['max'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ $statJp['min'] > 0 ? number_format($statJp['min'], 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach

                        <tr>
                            <th colspan="2" class="bg-yellow text-center">JUMLAH</th>
                            @for ($i = 1; $i <= 12; $i++)
                                <td class="bg-yellow text-end">{{ $dataNib[$i] > 0 ? number_format($dataNib[$i], 0, ',', '.') : '-' }}</td>
                            @endfor
                            <td class="bg-yellow text-end">{{ number_format($totalNib, 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ $totalNib > 0 ? '100%' : '0%' }}</td>
                            <td class="bg-yellow text-end">{{ number_format($statNib['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ number_format($statNib['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow text-end">{{ $statNib['min'] > 0 ? number_format($statNib['min'], 0, ',', '.') : '-' }}</td>
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
        const dataNIB = @json(array_values($dataNib));
        const dataPMA = @json(array_values($dataPma));
        const dataPMDN = @json(array_values($dataPmdn));

        Chart.defaults.scale.grid.display = false;
        Chart.defaults.plugins.legend.display = false;

        // Custom label di atas bar
        const pluginDatalabels = {
            id: 'topLabels',
            afterDatasetsDraw(chart, args, pluginOptions) {
                const { ctx, data } = chart;
                chart.getDatasetMeta(0).data.forEach((datapoint, index) => {
                    const value = data.datasets[0].data[index];
                    if (value > 0) {
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                        ctx.fillRect(datapoint.x - 15, datapoint.y - 25, 30, 20);
                        ctx.font = 'bold 11px Arial';
                        ctx.fillStyle = 'black';
                        ctx.textAlign = 'center';
                        ctx.fillText(value.toLocaleString('id-ID'), datapoint.x, datapoint.y - 12);
                    }
                });
            }
        };

        // Fungsi bantuan untuk mencari nilai max agar grafik ada ruang (headroom) 20% di atasnya
        const getChartMax = (arr) => {
            let maxVal = Math.max(...arr);
            return maxVal > 0 ? maxVal * 1.2 : 10;
        };

        // 1. Chart NIB
        new Chart(document.getElementById('chartNIB'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    data: dataNIB,
                    backgroundColor: '#e5d836',
                    borderColor: '#c0a820',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { display: false, suggestedMax: getChartMax(dataNIB) },
                    x: { ticks: { color: '#e5536b', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabels]
        });

        // 2. Chart PMA
        new Chart(document.getElementById('chartPMA'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    data: dataPMA,
                    backgroundColor: '#569bd5',
                    borderColor: '#30699c',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { display: false, suggestedMax: getChartMax(dataPMA) },
                    x: { ticks: { color: 'green', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabels]
        });

        // 3. Chart PMDN
        new Chart(document.getElementById('chartPMDN'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: [{
                    data: dataPMDN,
                    backgroundColor: '#569bd5',
                    borderColor: '#30699c',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { display: false, suggestedMax: getChartMax(dataPMDN) },
                    x: { ticks: { color: 'green', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabels]
        });
    </script>
</body>

</html>
