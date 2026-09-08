<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard MPP Digital — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        html, body { max-width: 100vw; overflow-x: hidden; font-family: "Moderustic", sans-serif; }
        .card { background: rgba(255, 255, 255, 0.9); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .chart-container { display: flex; flex-wrap: wrap; gap: 20px; }
        .chart-item { flex: 1 1 calc(50% - 20px); min-width: 300px; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2 my-2">
        <div class="chart-container">
            <div class="card chart-item">
                <div id="chart-faskes"></div>
            </div>
            <div class="card chart-item">
                <div id="chart-status"></div>
            </div>
        </div>
        <div class="card">
            <div id="chart-profesi"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dataFaskes = @json($faskesData);
            var seriesFaskes = dataFaskes.map(item => item.total);
            var labelsFaskes = dataFaskes.map(item => item.kategori);

            new ApexCharts(document.querySelector("#chart-faskes"), {
                series: [{ name: 'Jumlah FASKES', data: seriesFaskes }],
                chart: { height: 350, type: 'bar' },
                plotOptions: { bar: { dataLabels: { position: 'top' }, horizontal: true } },
                xaxis: { categories: labelsFaskes },
                yaxis: { title: { text: 'Jumlah' } },
                title: { text: 'DATA FASKES MPPDIG', align: 'center' }
            }).render();

            var dataStatus = @json($statusData);
            new ApexCharts(document.querySelector("#chart-status"), {
                series: [{ name: 'Jumlah Status', data: dataStatus.map(i => i.total) }],
                chart: { height: 350, type: 'bar' },
                plotOptions: { bar: { dataLabels: { position: 'top' } } },
                xaxis: { categories: dataStatus.map(i => i.status_permohonan) },
                yaxis: { title: { text: 'Jumlah' } },
                title: { text: 'Status Permohonan MPP Digital Tahun 2024', align: 'center' }
            }).render();

            var dataProfesi = @json($profesiData);
            new ApexCharts(document.querySelector("#chart-profesi"), {
                series: [{ name: 'Jumlah Profesi', data: dataProfesi.map(i => i.total) }],
                chart: { height: 1000, type: 'bar' },
                plotOptions: { bar: { dataLabels: { position: 'top' }, horizontal: true } },
                xaxis: { categories: dataProfesi.map(i => i.profesi) },
                yaxis: { title: { text: 'Jumlah' } },
                title: { text: 'Data Profesi Pemohon MPP Digital', align: 'center' }
            }).render();
        });
    </script>
</body>

</html>
