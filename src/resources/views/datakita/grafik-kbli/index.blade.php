<!DOCTYPE html>
<html lang="id">

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

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="input-group">
                    <input id="cari" type="text" class="form-control" placeholder="Masukan judul / KBLI...">
                    <button id="btn_submit" class="btn btn-primary" type="button">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div id="grafik_tahunan"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        function muatGrafik() {
            let cari = $("#cari").val();
            $.get(@json(route('datakita.grafik-kbli.tahunan')) + `?cari=${encodeURIComponent(cari)}`, function(data) {
                $("#grafik_tahunan").html(data);
            });
        }

        $("#btn_submit").on('click', muatGrafik);

        $('#cari').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                muatGrafik();
            }
        });
    </script>

</body>

</html>
