<div class="d-flex justify-content-center gap-3 mt-4">
    <button class="btn btn-warning" id="reset">Reset Filter</button>
    <button id="btn_export" class="btn btn-success">Export ke Excel</button>
</div>

<div class="card mt-4 mx-2">
    <div class="card-header bg-info text-white">
        <h2 class="card-title">Hasil Pencarian Data Proyek, Kantor & Perizinan</h2>
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
            "uraian_skala_usaha",
            "kecamatan_usaha",
            "sektor_pembina",
            "uraian_status_respon"
        ];

        $(fields.map(field => `#${field}`).join(",")).on('change', function(ev) {
            changeAjaxUrl();
        });

        let url = @json(route('datakita.perizinan.ajax'));

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
            let exportUrl = changeAjaxUrl(true, @json(route('datakita.perizinan.export')));

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
            fields.forEach((id) => {
                $(`#${id}`).val("");
            });

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
                search: "Nama Perusahaan / Nama Proyek / NIB / KBLI / Izin:",
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
                    title: "No",
                    name: "no",
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'id_proyek', name: 'id_proyek', title: 'ID Proyek' },
                { data: 'nib', name: 'nib', title: 'NIB' },
                { data: 'nama_perusahaan', name: 'nama_perusahaan', title: 'Nama Perusahaan' },
                { data: 'nama_proyek', name: 'nama_proyek', title: 'Nama Proyek' },
                {
                    data: 'alamat_kantor',
                    name: 'alamat_kantor',
                    title: 'Alamat Kantor (NIB)',
                    render: function(d) { return d ? d : '-'; }
                },
                { data: 'kecamatan_usaha', name: 'kecamatan_usaha', title: 'Kecamatan Usaha' },
                { data: 'kbli', name: 'kbli', title: 'KBLI' },
                { data: 'judul_kbli', name: 'judul_kbli', title: 'Judul KBLI' },
                { data: 'uraian_skala_usaha', name: 'uraian_skala_usaha', title: 'Skala Usaha' },
                {
                    data: null,
                    title: "Luas Tanah",
                    orderable: false,
                    render: function(data, type, row) {
                        return `${row.luas_tanah || 0} ${row.satuan_tanah || ''}`;
                    }
                },
                { data: 'jumlah_investasi3', name: 'jumlah_investasi3', title: 'Jumlah Investasi' },
                {
                    data: 'id_permohonan_izin',
                    name: 'id_permohonan_izin',
                    title: 'ID Permohonan Izin',
                    render: function(d) { return d ? d : '-'; }
                },
                {
                    data: 'uraian_jenis_perizinan',
                    name: 'uraian_jenis_perizinan',
                    title: 'Jenis Perizinan',
                    render: function(d) { return d ? d : '-'; }
                },
                {
                    data: 'uraian_status_respon',
                    name: 'uraian_status_respon',
                    title: 'Status Izin',
                    render: function(d) { return d ? d : '-'; }
                }
            ]
        });
    })();
</script>
