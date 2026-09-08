<div class="d-flex justify-content-center gap-3 mt-4">
    <button class="btn btn-warning" id="reset">Reset Filter</button>
    <button id="btn_export" class="btn btn-success">Export ke Excel</button>
</div>

<div class="card mt-4 mx-2">
    <div class="card-header bg-info text-white">
        <h2 class="card-title">Hasil Pencarian Dari Data Perusahaan</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="datatable" class="table table-striped table-hover w-100">
            </table>
        </div>
    </div>
</div>

<script>
    (function() {
        let fields = ["tahun", "bulan", "status_penanaman_modal", "uraian_jenis_perusahaan"];

        $(fields.map(f => `#${f}`).join(",")).on('change', function(ev) {
            changeAjaxUrl();
        });

        let url = @json(route('datakita.perusahaan.ajax'));

        function changeAjaxUrl(onlyUrl = false, defaultUrl = url) {
            let ajaxUrl = `${defaultUrl}?`;

            fields.forEach(id => {
                let v = $(`#${id}`).val();
                if (v) ajaxUrl += `&${id}=${encodeURIComponent(v)}`;
            });

            if (onlyUrl) {
                return ajaxUrl;
            }

            hasil_table.ajax.url(ajaxUrl);
            hasil_table.ajax.reload();
        }

        $("#btn_export").on('click', function() {
            let exportUrl = changeAjaxUrl(true, @json(route('datakita.perusahaan.export')));

            let searchValue = hasil_table.search();
            let order = hasil_table.order();
            let columnSortOrder = "desc";
            let columnName = "";

            if (order.length > 0) {
                var columnIndex = order[0][0];
                columnSortOrder = order[0][1];
                columnName = hasil_table.settings()[0].aoColumns[columnIndex].data;
            }

            window.open(`${exportUrl}&searchValue=${encodeURIComponent(searchValue)}&columnSortOrder=${columnSortOrder}&columnName=${columnName}`, "_blank");
        });

        function reset() {
            fields.forEach((id) => { $(`#${id}`).val(''); });
            hasil_table.search("");
            changeAjaxUrl();
        }

        $("#reset").on("click", function(e) {
            reset();
        });

        let hasil_table = $('#datatable').DataTable({
            ajax: {
                url: changeAjaxUrl(true),
                type: 'POST',
                dataSrc: "data",
            },
            language: {
                processing: "Memproses...",
                search: "Nama Perusahaan / Alamat / NIB:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 entri",
                infoFiltered: "(tersaring dari _MAX_ total entri)",
                loadingRecords: "Memuat...",
                zeroRecords: "Tidak ada data yang cocok",
            },
            processing: true,
            serverSide: true,
            order: [
                [4, "desc"]
            ],
            columns: [
                {
                    orderable: false,
                    title: "no",
                    name: "no",
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: "nama_perusahaan", name: "nama_perusahaan", title: "Nama Perusahaan", orderable: true },
                { data: "nib", name: "nib", title: "nib", orderable: false },
                { data: "email", name: "email", title: "Email", orderable: false },
                { data: "kelurahan", name: "kelurahan", title: "kelurahan", orderable: false },
                { data: "kecamatan", name: "kecamatan", title: "kecamatan", orderable: false },
                { data: "alamat_perusahaan", name: "alamat_perusahaan", title: "alamat", orderable: false },
                { data: "day_of_tanggal_terbit_oss", name: "day_of_tanggal_terbit_oss", title: "tanggal terbit oss", orderable: true },
                { data: "status_penanaman_modal", name: "status_penanaman_modal", title: "Status Penanaman Modal", orderable: false },
                { data: "uraian_jenis_perusahaan", name: "uraian_jenis_perusahaan", title: "Uraian Jenis Perusahaan", orderable: false },
            ]
        });
    })()
</script>
