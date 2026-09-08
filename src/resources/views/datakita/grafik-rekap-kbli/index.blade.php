<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Per KBLI Pertahun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css">
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Moderustic", sans-serif; }
        .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .table-container { overflow-x: auto; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title">Grafik Per KBLI Pertahun</h1>
            </div>
            <div class="card-body">
                <div id="chartTahunan"></div>
            </div>
        </div>

        <div class="card my-5" id="chartBulanan" style="display: none; margin-top: 50px;">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title">Grafik Per KBLI Pertahun - <span class="tahunTerpilih"></span></h1>
            </div>
            <div class="card-body">
                <div>
                    <div id="chartBulananContainer"></div>
                </div>
            </div>
        </div>

        <div id="tempat_table"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
        });

        const tahun = @json($tahun);
        const dataInvestasi = @json($dataInvestasi);

        const optionsTahunan = {
            series: [{
                name: "Jumlah KBLI",
                data: dataInvestasi
            }],
            chart: {
                type: 'bar',
                height: 400,
                events: {
                    dataPointSelection: (event, chartContext, config) => {
                        document.getElementById('chartBulanan').scrollIntoView({
                            behavior: 'smooth'
                        });
                        const selectedYear = tahun[config.dataPointIndex];
                        loadDataBulanan(selectedYear);
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opt) {
                    return ` ${val.toLocaleString("id-ID")}`;
                }
            },
            xaxis: {
                categories: tahun,
                title: {
                    text: 'Tahun'
                }
            },
            yaxis: {
                title: {
                    text: 'Jumlah KBLI'
                },
                labels: {
                    formatter: function(value) {
                        return `${value.toLocaleString("id-ID")}`;
                    }
                }
            },
            title: {
                text: 'Grafik KBLI Pertahun',
                align: 'left'
            }
        };

        const chartTahunan = new ApexCharts(document.querySelector("#chartTahunan"), optionsTahunan);
        chartTahunan.render();

        async function loadDataBulanan(tahunSelected) {
            try {
                $.get(@json(route('datakita.grafik-rekap-kbli.table')) + `?tahun=${encodeURIComponent(tahunSelected)}`, function(data) {
                    $("#tempat_table").html(data);
                });

                const response = await fetch(@json(route('datakita.grafik-rekap-kbli.bulanan')) + `?tahun=${encodeURIComponent(tahunSelected)}`);
                const data = await response.json();

                document.getElementById('chartBulanan').style.display = 'block';
                let tahunElement = document.querySelectorAll('.tahunTerpilih');

                tahunElement.forEach((e) => {
                    e.innerHTML = tahunSelected;
                });

                const optionsBulanan = {
                    series: [{
                        name: "Jumlah KBLI",
                        data: data.data
                    }],
                    chart: {
                        type: 'line',
                        height: 350
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                        title: {
                            text: 'Bulan'
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(value) {
                            return `${value.toLocaleString("id-ID")}`;
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Jumlah KBLI'
                        },
                        labels: {
                            formatter: function(value) {
                                return `${value.toLocaleString("id-ID")}`;
                            }
                        }
                    },
                    title: {
                        text: `Grafik KBLI Perbulan Tahun ${tahunSelected}`,
                        align: 'left'
                    }
                };

                if (window.chartBulanan instanceof ApexCharts) {
                    window.chartBulanan.destroy();
                }

                window.chartBulanan = new ApexCharts(document.querySelector("#chartBulananContainer"), optionsBulanan);
                chartBulanan.render();

            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data bulanan');
            }
        }
    </script>
</body>

</html>
