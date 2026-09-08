<div class="d-flex justify-content-center gap-3 mt-4">
    <button class="btn btn-warning" id="reset">Reset Filter</button>
    <button id="btn_export" class="btn btn-success">Export ke Excel</button>
</div>

<div class="card mt-4 mx-2">
    <div class="card-header bg-info text-white">
        <h2 class="card-title">Hasil Pencarian Data Proyek</h2>
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
            "uraian_jenis_perusahaan",
            "uraian_risiko_proyek",
            "uraian_skala_usaha",
            "kecamatan_usaha",
            "sektor_pembina",
            "sektor"
        ];

        $(fields.map(field => `#${field}`).join(",")).on('change', function(ev) {
            changeAjaxUrl();
        });

        let url = @json(route('datakita.proyek.ajax'));

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
            let exportUrl = changeAjaxUrl(true, @json(route('datakita.proyek.export')));

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

        $("#reset").on("click", function(e) {
            fields.forEach((id) => { $(`#${id}`).val(""); });
            hasil_table.search("");
            changeAjaxUrl();
        });

        let hasil_table = $('#datatable').DataTable({
            ajax: {
                url: changeAjaxUrl(true),
                type: 'POST',
                dataSrc: "data",
            },
            language: {
                processing: "Memproses...",
                search: "Nama Perusahaan / Nama Proyek / NIB / KBLI:",
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
                [1, "asc"]
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
                { data: 'nama_perusahaan', name: 'nama_perusahaan', title: 'nama_perusahaan' },
                { data: 'nama_proyek', name: 'nama_proyek', title: 'nama_proyek' },
                { data: 'tanggal_terbit_oss', name: 'tanggal_terbit_oss', title: 'tanggal_terbit_oss' },
                { data: 'nib', name: 'nib', title: 'nib' },
                { data: 'alamat_usaha', name: 'alamat_usaha', title: 'alamat_usaha' },
                { data: 'kecamatan_usaha', name: 'kecamatan_usaha', title: 'kecamatan_usaha' },
                { data: 'kelurahan_usaha', name: 'kelurahan_usaha', title: 'kelurahan_usaha' },
                { data: 'kbli', name: 'kbli', title: 'kbli' },
                { data: 'judul_kbli', name: 'judul_kbli', title: 'judul_kbli' },
                { data: 'uraian_risiko_proyek', name: 'uraian_risiko_proyek', title: 'uraian_risiko_proyek' },
                { data: 'uraian_jenis_perusahaan', name: 'uraian_jenis_perusahaan', title: 'uraian_jenis_perusahaan' },
                { data: 'uraian_skala_usaha', name: 'uraian_skala_usaha', title: 'uraian_skala_usaha' },
                { data: 'sektor_pembina', name: 'sektor_pembina', title: 'sektor_pembina' },
                {
                    data: null,
                    title: "Luas Tanah",
                    orderable: false,
                    render: function(data, type, row) {
                        return `${row.luas_tanah} ${row.satuan_tanah}`;
                    }
                },
                { data: 'jumlah_investasi3', name: 'jumlah_investasi3', title: 'Jumlah Investasi' },
            ]
        });
    })()
</script>
