<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik SIMBG — Data Kita</title>
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
            height: 350px;
            width: 100%;
        }

        .section-title {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 2px solid #e5536b;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">

        <div class="mb-4 bg-light p-3 rounded shadow-sm">
            <form method="GET" action="{{ route('datakita.statistik.simbg') }}" class="d-flex align-items-center m-0">
                <label for="tahun" class="me-2 fw-bold" style="font-size: 16px;">Tahun Data:</label>
                <select name="tahun" id="tahun" class="form-select w-auto border-secondary" onchange="this.form.submit()">
                    @foreach ($yearsList as $thn)
                        <option value="{{ $thn }}" {{ $thn == $selectedYear ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @foreach ($blocks as $index => $block)
            <h4 class="section-title">{{ ($index + 1) . '. STATISTIK BERDASARKAN ' . $block['title'] }}</h4>

            @if ($block['chartId'] === 'chartKelurahan')
                <div class="mb-3 p-3 rounded" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
                    <form class="d-flex align-items-center m-0" onsubmit="return false;">
                        <label for="filter_kecamatan" class="me-2 fw-bold text-danger" style="font-size: 15px;">
                            <i class="bi bi-filter-circle"></i> Filter Kecamatan (Khusus Tabel Ini):
                        </label>
                        <select id="filter_kecamatan" class="form-select w-auto border-danger" onchange="loadKelurahanAjax()">
                            @foreach ($kecamatanList as $kc)
                                <option value="{{ $kc }}" {{ $kc == $selectedKecamatan ? 'selected' : '' }}>{{ $kc }}</option>
                            @endforeach
                        </select>
                        <div id="loading_kelurahan" class="spinner-border text-danger spinner-border-sm ms-3 d-none" role="status"></div>
                    </form>
                </div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="card-custom table-responsive">
                        <table class="tbl-report" style="min-width: 1100px;">
                            <tr>
                                <th colspan="20" class="bg-red text-start p-3">
                                    <h4 class="mb-1">DATA STATISTIK SIMBG</h4>
                                    <p style="font-size: 11px; font-weight: normal; margin-bottom: 5px;">Sumber Data : SIMBG Monitoring</p>
                                    <h5 class="mb-0" id="title-table-{{ $block['chartId'] }}">
                                        JUMLAH PERMOHONAN BERDASARKAN {{ $block['title'] }}
                                        @if ($block['chartId'] === 'chartKelurahan') ({{ $selectedKecamatan }}) @endif
                                    </h5>
                                </th>
                            </tr>
                            <tr>
                                <th rowspan="3" class="bg-grey">No</th>
                                <th rowspan="3" class="bg-grey" style="min-width: 200px;">Uraian {{ $block['title'] }}</th>
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

                            <tbody id="tbody-{{ $block['chartId'] }}">
                                @include('datakita.statistik.partials.simbg-table-rows', [
                                    'dataArray' => $block['data'],
                                    'totalArr' => $block['tot_arr'],
                                    'gTotal' => $block['g_tot'],
                                    'getStatsRef' => $getStatsRef,
                                ])
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-12">
                    <div class="card-custom" style="background: linear-gradient(to bottom, #d6495c, #e0d080);">
                        <h5 class="text-center text-white fw-bold mt-2 mb-3" id="title-chart-{{ $block['chartId'] }}">
                            GRAFIK BERDASARKAN {{ $block['title'] }} PER BULAN
                            @if ($block['chartId'] === 'chartKelurahan') ({{ $selectedKecamatan }}) @endif
                        </h5>
                        <div class="chart-container-large">
                            <canvas id="{{ $block['chartId'] }}"></canvas>
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
        const palette = ['#e5536b', '#569bd5', '#e5d836', '#4caf50', '#9c27b0', '#ff9800', '#00bcd4', '#795548', '#607d8b', '#e91e63'];

        // Global Object penyimpan instance Chart
        window.chartInstances = {};

        const pluginDatalabelsMulti = {
            id: 'topLabelsMulti',
            afterDatasetsDraw(chart, args, pluginOptions) {
                const { ctx, data } = chart;
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach((datapoint, index) => {
                            const value = dataset.data[index];
                            if (value > 0 && datapoint.y !== undefined && !isNaN(datapoint.y)) {
                                ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
                                ctx.fillRect(datapoint.x - 12, datapoint.y - 20, 24, 15);
                                ctx.font = 'bold 9px Arial';
                                ctx.fillStyle = 'black';
                                ctx.textAlign = 'center';
                                ctx.fillText(value.toLocaleString('id-ID'), datapoint.x, datapoint.y - 9);
                            }
                        });
                    }
                });
            }
        };

        Chart.defaults.scale.grid.display = false;

        // Fungsi Create/Re-Create Chart
        function createGroupedChart(canvasId, datasetArray) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            // Hancurkan (Destroy) jika Chart dengan ID ini sudah ada sebelumnya (penting untuk reload AJAX)
            if (window.chartInstances[canvasId]) {
                window.chartInstances[canvasId].destroy();
            }

            window.chartInstances[canvasId] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labelsBulan,
                    datasets: datasetArray
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
        }

        // FUNGSI JAVASCRIPT AJAX UNTUK KELURAHAN
        function loadKelurahanAjax() {
            const tahun = document.getElementById('tahun').value;
            const kecamatan = document.getElementById('filter_kecamatan').value;
            const loadingSpinner = document.getElementById('loading_kelurahan');

            // Tampilkan loading
            loadingSpinner.classList.remove('d-none');

            const url = @json(route('datakita.statistik.simbg-kelurahan')) + `?tahun=${tahun}&kecamatan=${encodeURIComponent(kecamatan)}`;

            // Fetch data via GET request
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // 1. Ganti HTML dalam tabel tbody
                    document.getElementById('tbody-chartKelurahan').innerHTML = data.html;

                    // 2. Ganti Judul
                    document.getElementById('title-table-chartKelurahan').innerText = 'JUMLAH PERMOHONAN BERDASARKAN KELURAHAN' + data.title_suffix;
                    document.getElementById('title-chart-chartKelurahan').innerText = 'GRAFIK BERDASARKAN KELURAHAN PER BULAN' + data.title_suffix;

                    // 3. Re-Create Chart menggunakan JSON Data dari AJAX
                    createGroupedChart('chartKelurahan', data.chart);
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    alert("Gagal memuat data kelurahan. Silakan coba lagi.");
                })
                .finally(() => {
                    // Sembunyikan loading
                    loadingSpinner.classList.add('d-none');
                });
        }

        // Render Semua Grafik Bawaan (Normal Load)
        @foreach ($blocks as $block)
            createGroupedChart('{{ $block['chartId'] }}', @json(
                collect($block['data'])->map(function ($arrVal, $key) use ($palette) {
                    static $cIdx = 0;
                    return [
                        'label' => (string) $key,
                        'data' => array_values($arrVal),
                        'backgroundColor' => $palette[$cIdx % count($palette)],
                        'borderColor' => 'white',
                        'borderWidth' => 1,
                    ];
                })->values()->all()
            ));
        @endforeach
    </script>
</body>

</html>
