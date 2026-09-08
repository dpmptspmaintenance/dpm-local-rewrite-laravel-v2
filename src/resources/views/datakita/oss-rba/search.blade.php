<div class="d-flex justify-content-center gap-3 mt-4">
    <button class="btn btn-warning" id="reset">Reset Filter</button>
    <button id="btn_export" class="btn btn-success">Export ke Excel</button>
</div>

<div class="card mt-4 mx-2">
    <div class="card-header bg-info text-white">
        <h2 class="card-title">Hasil Pencarian Perizinan OSS-RBA</h2>
    </div>
    <div class="card-body table-container">
        <div class="table-responsive">
            <table id="datatable" class="table table-striped table-hover w-100">
            </table>
        </div>
    </div>
</div>

<script>
    (function() {
        let fields = [
            "tahun",
            "bulan",
            "uraian_status_penanaman_modal",
            "resiko_proyek",
            "uraian_jenis_perizinan",
            "nama_dokumen",
            "kl_sektor",
            "uraian_status_respon",
            "sektor"
        ];

        $(fields.map(field => `#${field}`).join(",")).on('change', function(ev) {
            changeAjaxUrl();
        });

        let url = @json(route('datakita.ossrba.ajax'));

        function changeAjaxUrl(onlyUrl = false, curentUrl = url) {
            let ajaxUrl = `${curentUrl}?`;

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
            let exportUrl = changeAjaxUrl(true, @json(route('datakita.ossrba.export')));

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
            fields.forEach((id) => { $(`#${id}`).val(""); });
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
                search: "Nama Perusahaan / Judul / NIB:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 entri",
                infoFiltered: "(tersaring dari _MAX_ total entri)",
                loadingRecords: "Memuat...",
                zeroRecords: "Tidak ada data yang cocok",
            },
            processing: true,
            serverSide: true,
            order: [[3, 'desc']],
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
                { data: "nib", title: "nib", orderable: false },
                { data: "day_of_tanggal_terbit_oss", name: "day_of_tanggal_terbit_oss", title: "tanggal terbit oss", orderable: true },
                { data: "resiko", title: "Resiko", orderable: false },
                { data: "kbli", title: "kbli", orderable: false },
                { data: "judul_kbli", title: "judul kbli", orderable: false },
                { data: "kelurahan", title: "Kelurahan", orderable: false },
                { data: "kecamatan", title: "Kecamatan", orderable: false },
                { data: "uraian_jenis_perizinan", title: "jenis perizinan", orderable: false },
                { data: "nama_dokumen", title: "Nama Dokumen", orderable: false },
                { data: "uraian_status_respon", title: "Status Respon", orderable: false },
                { data: "kl_sektor", title: "Sektor", orderable: false }
            ]
        });
    })();
</script>
