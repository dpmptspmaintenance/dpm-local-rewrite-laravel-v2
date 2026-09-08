<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Izin — Data Kita</title>
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

        .chart-container-large {
            position: relative;
            height: 400px;
            width: 100%;
        }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">

        <div class="mb-4 bg-light p-3 rounded shadow-sm d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('datakita.statistik.izin') }}" class="d-flex align-items-center mb-0">
                <label for="tahun" class="me-2 fw-bold" style="font-size: 16px;">Tahun Data:</label>
                <select name="tahun" id="tahun" class="form-select w-auto" onchange="this.form.submit()">
                    @foreach ($yearsList as $thn)
                        <option value="{{ $thn }}" {{ $thn == $selectedYear ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                </select>
            </form>
            <div>
                <a href="{{ route('datakita.statistik.izin-download-excel', ['tahun' => $selectedYear]) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Download Excel</a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card-custom table-responsive">
                    <table class="tbl-report" style="min-width: 1300px;">
                        <tr>
                            <td rowspan="14" colspan="2" class="bg-red text-start p-3" style="width: 250px; vertical-align: middle;">
                                <h4 style="font-weight: bold; margin-bottom: 20px; line-height: 1.4;">DATA<br>STATISTIK<br>PERIZINAN</h4>
                                <h5 style="font-weight: bold; margin-bottom: 0;">JUMLAH PERMOHONAN<br>BERDASARKAN RISIKO</h5>
                            </td>
                            <th rowspan="2" class="bg-grey">No</th>
                            <th rowspan="2" class="bg-grey">TAHUN</th>
                            <th rowspan="2" class="bg-grey">BULAN</th>
                            <th colspan="{{ count($kategoriRisiko) * 2 }}" class="bg-red">STATUS RISIKO PERMOHONAN</th>
                            <th rowspan="2" class="bg-red">JUMLAH</th>
                            <th rowspan="2" class="bg-red">% JUMLAH</th>
                        </tr>
                        <tr>
                            @foreach ($kategoriRisiko as $kr)
                                <th class="bg-red">{{ $kr }}</th>
                                <th class="bg-red">% {{ $kr }}</th>
                            @endforeach
                        </tr>

                        @for ($i = 1; $i <= 12; $i++)
                            @php
                                $jmlBln = $dataTotal[$i];
                                $pctBln = $grandTotal > 0 ? ($jmlBln / $grandTotal) * 100 : 0;
                            @endphp
                            <tr>
                                <td>{{ $i }}</td>
                                <td>{{ $selectedYear }}</td>
                                <td>{{ $i }}</td>

                                @foreach ($kategoriRisiko as $kr)
                                    @php
                                        $val = $dataResiko[$kr][$i];
                                        $totKr = array_sum($dataResiko[$kr]);
                                        $pct = $totKr > 0 ? ($val / $totKr) * 100 : 0;
                                    @endphp
                                    <td class="text-end">{{ $val > 0 ? number_format($val, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end bg-grey">{{ $val > 0 ? number_format($pct, 2, ',', '.').'%' : '' }}</td>
                                @endforeach

                                <td class="text-end bg-grey fw-bold">{{ $jmlBln > 0 ? number_format($jmlBln, 0, ',', '.') : '-' }}</td>
                                <td class="text-end bg-red text-white">{{ $jmlBln > 0 ? number_format($pctBln, 2, ',', '.').'%' : '' }}</td>
                            </tr>
                        @endfor

                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">JUMLAH</td>
                            @foreach ($kategoriRisiko as $kr)
                                <td class="bg-yellow text-end">{{ number_format(array_sum($dataResiko[$kr]), 0, ',', '.') }}</td>
                                <td class="bg-yellow"></td>
                            @endforeach
                            <td class="bg-yellow text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">RATA - RATA</td>
                            @foreach ($kategoriRisiko as $kr)
                                <td class="bg-yellow text-end">{{ number_format($statResiko[$kr]['avg'], 0, ',', '.') }}</td>
                                <td class="bg-yellow"></td>
                            @endforeach
                            <td class="bg-yellow text-end">{{ number_format($statTotal['avg'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">TERTINGGI</td>
                            @foreach ($kategoriRisiko as $kr)
                                <td class="bg-yellow text-end">{{ number_format($statResiko[$kr]['max'], 0, ',', '.') }}</td>
                                <td class="bg-yellow"></td>
                            @endforeach
                            <td class="bg-yellow text-end">{{ number_format($statTotal['max'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="bg-yellow text-end pe-3">TERENDAH</td>
                            @foreach ($kategoriRisiko as $kr)
                                <td class="bg-yellow text-end">{{ number_format($statResiko[$kr]['min'], 0, ',', '.') }}</td>
                                <td class="bg-yellow"></td>
                            @endforeach
                            <td class="bg-yellow text-end">{{ number_format($statTotal['min'], 0, ',', '.') }}</td>
                            <td class="bg-yellow"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card-custom" style="background: linear-gradient(to bottom, #d6495c, #94c180);">
                    <h5 class="text-center text-white fw-bold mt-2 mb-3">GRAFIK PERMOHONAN BERDASARKAN RISIKO PER BULAN</h5>
                    <div class="chart-container-large">
                        <canvas id="chartAllRisiko"></canvas>
                    </div>
                </div>
            </div>
        </div>

        @php
            $detailTables = [
                ['title' => 'JUMLAH PERMOHONAN JENIS PERIZINAN', 'uraian' => 'Uraian Jenis Perizinan', 'list' => $jenisList, 'data' => $dataJenis, 'chartId' => 'chartJenisPerizinan', 'gradient' => 'linear-gradient(to bottom, #d6495c, #e0d080)'],
                ['title' => 'JUMLAH PERMOHONAN NAMA DOKUMEN', 'uraian' => 'Nama Dokumen', 'list' => $dokumenList, 'data' => $dataDokumen, 'chartId' => 'chartNamaDokumen', 'gradient' => 'linear-gradient(to bottom, #d6495c, #94c180)'],
                ['title' => 'JUMLAH PERMOHONAN STATUS RESPON', 'uraian' => 'Uraian Status Respon', 'list' => $responList, 'data' => $dataRespon, 'chartId' => 'chartStatusRespon', 'gradient' => 'linear-gradient(to bottom, #d6495c, #e0d080)'],
            ];
        @endphp

        @foreach ($detailTables as $detail)
            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="card-custom table-responsive">
                        <table class="tbl-report" style="min-width: 1000px;">
                            <tr>
                                <th colspan="20" class="bg-red text-start p-3">
                                    <h4 class="mb-1">DATA STATISTIK PERIZINAN</h4>
                                    <p style="font-size: 11px; font-weight: normal; margin-bottom: 5px;">Sumber Data : OSS - RBA | Sumber File : List izin.xlsx</p>
                                    <h5 class="mb-0">{{ $detail['title'] }}</h5>
                                </th>
                            </tr>
                            <tr>
                                <th rowspan="3" class="bg-grey">No</th>
                                <th rowspan="3" class="bg-grey" style="min-width: 200px;">{{ $detail['uraian'] }}</th>
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
                            @foreach ($detail['list'] as $item)
                                @php
                                    $arrBulanan = $detail['data'][$item];
                                    $totItem = array_sum($arrBulanan);
                                    $statItem = $getStatsRef($arrBulanan);
                                    $pctItem = $grandTotal > 0 ? ($totItem / $grandTotal) * 100 : 0;
                                @endphp
                                <tr>
                                    <td>{{ $no++ }}</td>
                                    <td class="text-start">{{ $item }}</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <td class="text-end">{{ $arrBulanan[$i] > 0 ? number_format($arrBulanan[$i], 0, ',', '.') : '-' }}</td>
                                    @endfor
                                    <td class="bg-grey text-end fw-bold">{{ number_format($totItem, 0, ',', '.') }}</td>
                                    <td class="bg-grey text-end">{{ $totItem > 0 ? number_format($pctItem, 2, ',', '.').'%' : '-' }}</td>
                                    <td class="bg-yellow text-end">{{ number_format($statItem['avg'], 0, ',', '.') }}</td>
                                    <td class="bg-yellow text-end">{{ number_format($statItem['max'], 0, ',', '.') }}</td>
                                    <td class="bg-yellow text-end">{{ number_format($statItem['min'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <tr>
                                <th colspan="2" class="bg-yellow text-center">JUMLAH TOTAL</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <td class="bg-yellow text-end">{{ $dataTotal[$i] > 0 ? number_format($dataTotal[$i], 0, ',', '.') : '-' }}</td>
                                @endfor
                                <td class="bg-yellow text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ $grandTotal > 0 ? '100%' : '0%' }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statTotal['avg'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statTotal['max'], 0, ',', '.') }}</td>
                                <td class="bg-yellow text-end">{{ number_format($statTotal['min'], 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="card-custom" style="background: {{ $detail['gradient'] }};">
                        <h5 class="text-center text-white fw-bold mt-2 mb-3">GRAFIK PERMOHONAN BERDASARKAN {{ $detail['uraian'] }} PER BULAN</h5>
                        <div class="chart-container-large">
                            <canvas id="{{ $detail['chartId'] }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const labelsBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

        const colors = {
            'KOSONG': { bg: '#9e9e9e', border: '#757575' },
            'MR': { bg: '#569bd5', border: '#30699c' },
            'MT': { bg: '#e5d836', border: '#c0a820' },
            'R': { bg: '#4caf50', border: '#388e3c' },
            'T': { bg: '#e5536b', border: '#d32f2f' }
        };
        const palette = ['#e5536b', '#569bd5', '#e5d836', '#4caf50', '#9c27b0', '#ff9800', '#00bcd4', '#795548', '#607d8b', '#e91e63'];

        // Plugin label dinamis
        const pluginDatalabelsMulti = {
            id: 'topLabelsMulti',
            afterDatasetsDraw(chart, args, pluginOptions) {
                const { ctx, data } = chart;
                chart.data.datasets.forEach((dataset, i) => {
                    chart.getDatasetMeta(i).data.forEach((datapoint, index) => {
                        const value = dataset.data[index];
                        if (value > 0) {
                            ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                            ctx.fillRect(datapoint.x - 12, datapoint.y - 20, 24, 15);
                            ctx.font = 'bold 9px Arial';
                            ctx.fillStyle = 'black';
                            ctx.textAlign = 'center';
                            ctx.fillText(value.toLocaleString('id-ID'), datapoint.x, datapoint.y - 9);
                        }
                    });
                });
            }
        };

        Chart.defaults.scale.grid.display = false;

        // 1. Chart Gabungan Risiko
        new Chart(document.getElementById('chartAllRisiko'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: @json(
                    collect($kategoriRisiko)->map(function ($kr) use ($colors, $dataResiko) {
                        return [
                            'label' => $kr,
                            'data' => array_values($dataResiko[$kr]),
                            'backgroundColor' => $colors[$kr]['bg'],
                            'borderColor' => $colors[$kr]['border'],
                            'borderWidth' => 1,
                        ];
                    })->values()->all()
                )
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { color: 'white', font: { weight: 'bold' } }
                    }
                },
                scales: {
                    y: { display: false },
                    x: { ticks: { color: 'white', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabelsMulti]
        });

        // 2. Chart Gabungan Jenis Perizinan
        new Chart(document.getElementById('chartJenisPerizinan'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: @json(
                    collect($jenisList)->map(function ($jp, $i) use ($palette, $dataJenis) {
                        return [
                            'label' => $jp,
                            'data' => array_values($dataJenis[$jp]),
                            'backgroundColor' => $palette[$i % count($palette)],
                            'borderColor' => 'white',
                            'borderWidth' => 1,
                        ];
                    })->values()->all()
                )
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { color: 'black', font: { weight: 'bold' } }
                    }
                },
                scales: {
                    y: { display: false },
                    x: { ticks: { color: 'white', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabelsMulti]
        });

        // 3. Chart Gabungan Nama Dokumen
        new Chart(document.getElementById('chartNamaDokumen'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: @json(
                    collect($dokumenList)->map(function ($nd, $i) use ($palette, $dataDokumen) {
                        return [
                            'label' => $nd,
                            'data' => array_values($dataDokumen[$nd]),
                            'backgroundColor' => $palette[$i % count($palette)],
                            'borderColor' => 'white',
                            'borderWidth' => 1,
                        ];
                    })->values()->all()
                )
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { color: 'black', font: { weight: 'bold' } }
                    }
                },
                scales: {
                    y: { display: false },
                    x: { ticks: { color: 'white', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabelsMulti]
        });

        // 4. Chart Gabungan Status Respon
        new Chart(document.getElementById('chartStatusRespon'), {
            type: 'bar',
            data: {
                labels: labelsBulan,
                datasets: @json(
                    collect($responList)->map(function ($sr, $i) use ($palette, $dataRespon) {
                        return [
                            'label' => $sr,
                            'data' => array_values($dataRespon[$sr]),
                            'backgroundColor' => $palette[$i % count($palette)],
                            'borderColor' => 'white',
                            'borderWidth' => 1,
                        ];
                    })->values()->all()
                )
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { color: 'black', font: { weight: 'bold' } }
                    }
                },
                scales: {
                    y: { display: false },
                    x: { ticks: { color: 'white', font: { weight: 'bold' } } }
                }
            },
            plugins: [pluginDatalabelsMulti]
        });
    </script>
</body>

</html>
