<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Realisasi Investasi — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: "Moderustic", sans-serif; }
        .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="container-fluid mt-4">
        <div class="card" id="table-data">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title mb-0">Rekap Realisasi Investasi</h1>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="tahun_selector" class="form-label fw-semibold">Pilih Tahun:</label>
                        <select id="tahun_selector" class="form-select">
                            @foreach ($allTahun as $tahun)
                                <option value="{{ $tahun }}" @selected($tahun == $currentTahun)>
                                    {{ $tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="fw-semibold" for="status">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">Pilih Semua</option>
                            @foreach ($statusList as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold" for="negara">Negara</label>
                        <select class="form-select" name="negara" id="negara">
                            <option value="">Pilih Semua</option>
                            @foreach ($negaraList as $negara)
                                <option value="{{ $negara }}">{{ $negara }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button id="btn_export" class="btn btn-success">Export ke Excel</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable" class="table table-striped table-hover" style="width:100%"></table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
        });

        $(document).ready(function() {
            let currentTahun = @json((string) $currentTahun);

            function getAjaxUrl() {
                let ajaxUrl = @json(route('datakita.realisasi-investasi.data')) + `?tahun=${currentTahun}`;
                let status = $("#status").val();
                if (status) ajaxUrl += `&status=${encodeURIComponent(status)}`;

                let negara = $("#negara").val();
                if (negara) ajaxUrl += `&negara=${encodeURIComponent(negara)}`;

                return ajaxUrl;
            }

            let hasil_table = $('#datatable').DataTable({
                ajax: {
                    url: getAjaxUrl(),
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
                    zeroRecords: "Tidak ada data yang cocok",
                },
                processing: true,
                serverSide: true,
                order: [[1, 'asc']],
                columns: [
                    {
                        orderable: false, title: "No", name: "no",
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    { title: "Nama Perusahaan", name: "nama_perusahaan", data: "nama_perusahaan" },
                    { title: "No. Izin", name: "no_izin", data: "no_izin" },
                    { title: "No. Proyek", name: "no_proyek", data: "no_proyek" },
                    { title: "Status", name: "status", data: "status" },
                    { title: "Negara", name: "negara", data: "negara" },
                    { title: "Tahun", name: "tahun", data: "tahun" },
                    { title: "Triwulan", name: "triwulan", data: "triwulan" },
                ]
            });

            $("#status, #negara").on("change", function() {
                hasil_table.ajax.url(getAjaxUrl()).load();
            });

            $("#tahun_selector").on("change", function() {
                let selectedTahun = $(this).val();
                window.location.href = `?tahun=${selectedTahun}`;
            });

            $("#btn_export").on('click', function() {
                let exportUrl = @json(route('datakita.realisasi-investasi.export')) + `?tahun=${currentTahun}`;
                let status = $("#status").val();
                if (status) exportUrl += `&status=${encodeURIComponent(status)}`;

                let negara = $("#negara").val();
                if (negara) exportUrl += `&negara=${encodeURIComponent(negara)}`;

                let search = hasil_table.search();
                if (search) exportUrl += `&search=${encodeURIComponent(search)}`;

                window.open(exportUrl, "_blank");
            });
        });
    </script>
</body>

</html>
