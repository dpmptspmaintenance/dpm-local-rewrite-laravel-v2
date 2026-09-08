<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Data Permohonan MPP Digital — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; } .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title h4">Cari Data Permohonan MPP Digital</h1>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-semibold form-label" for="profesi">Profesi</label>
                        <select class="form-control js-example-basic-multiple" name="profesi[]" multiple="multiple" style="width:100%">
                            @foreach ($profesi as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold form-label" for="status_permohonan">Status Permohonan</label>
                        <select class="form-select" name="status_permohonan" id="status_permohonan">
                            <option value="">-- Semua Status --</option>
                            @foreach ($statuses as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold form-label" for="daterange">Date Range</label>
                        <input type="text" class="form-control" name="daterange" id="daterange" />
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button target="_blank" id="export" class="btn btn-success">Export ke Excel</button>
                    </div>
                    <div class="col-md-12">
                        <table id="datatable" class="table table-striped table-hover w-100"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } });

        var start = moment().subtract(1, 'months');
        var end = moment();

        $(document).ready(function() {
            $(".js-example-basic-multiple").select2({ placeholder: "-- Semua Profesi --" });
        });

        $(".js-example-basic-multiple").on("select2:select select2:unselect", function(e) { changeAjaxUrl(); });

        $('input[name="daterange"]').daterangepicker({
            startDate: start,
            endDate: end,
            locale: { format: 'DD-MM-YYYY' },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(startDate, endDate, label) {
            start = startDate; end = endDate; changeAjaxUrl();
        });

        $("#status_permohonan").on("change", function(ev) { changeAjaxUrl(); });

        function changeAjaxUrl() {
            let ajaxUrl = @json(route('datakita.mppdig-permohonan.ajax')) + `?start_date=${start.format("YYYY-MM-DD")}&end_date=${end.format("YYYY-MM-DD")}`;

            let status_permohonan = $("#status_permohonan").val();
            if (status_permohonan != "") ajaxUrl += `&status_permohonan=${encodeURIComponent(status_permohonan)}`;

            let profesi = $(".js-example-basic-multiple").val();
            if (profesi.length > 0) {
                for (const element of profesi) ajaxUrl += `&profesi[]=${encodeURIComponent(element)}`;
            }

            hasil_table.ajax.url(ajaxUrl); hasil_table.ajax.reload();
        }

        $("#export").on("click", function() {
            let ajaxUrl = @json(route('datakita.mppdig-permohonan.export')) + `?start_date=${start.format("YYYY-MM-DD")}&end_date=${end.format("YYYY-MM-DD")}`;

            let status_permohonan = $("#status_permohonan").val();
            if (status_permohonan != "") ajaxUrl += `&status_permohonan=${encodeURIComponent(status_permohonan)}`;

            let profesi = $(".js-example-basic-multiple").val();
            if (profesi.length > 0) {
                for (const element of profesi) ajaxUrl += `&profesi[]=${encodeURIComponent(element)}`;
            }

            let search = hasil_table.search();
            window.open(`${ajaxUrl}&searchValue=${encodeURIComponent(search)}`, "_blank");
        });

        let hasil_table = $('#datatable').DataTable({
            ajax: {
                url: @json(route('datakita.mppdig-permohonan.ajax')) + `?start_date=${start.format("YYYY-MM-DD")}&end_date=${end.format("YYYY-MM-DD")}`,
                type: 'POST',
                dataSrc: "data",
            },
            language: {
                processing: "Memproses...",
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 entri",
                infoFiltered: "(tersaring dari _MAX_ total entri)",
                loadingRecords: "Memuat...",
                zeroRecords: "Tidak ada data yang cocok",
            },
            layout: { topEnd: { search: { placeholder: 'Cari' } } },
            processing: true,
            serverSide: true,
            order: [1, 'desc'],
            columns: [
                { orderable: false, title: "no", name: "no", render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { title: "No Registrasi", name: "no_reg", data: "no_reg" },
                { title: "Nama", name: "nama", data: "nama" },
                { title: "profesi", name: "profesi", data: "profesi" },
                { title: "Tempat Praktik", name: "tempat_praktik", data: "tempat_praktik" },
                { title: "Status Permohonan", name: "status_permohonan", data: "status_permohonan" },
                { title: "Tanggal Permohonan", name: "tgl_permohonan", data: "tgl_permohonan" },
            ]
        });
    </script>
</body>

</html>
