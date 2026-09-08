<div class="card my-5" id="table-data">
    <div class="card-header bg-primary text-white">
        <h1 class="card-title">Tabel KBLI {{ $tahun }}</h1>
    </div>
    <div class="card-body">

        <div class="row">
            <div class="col-3 my-2">
                <button target="_blank" id="btn_export" class="btn btn-success">Export ke Excel</button>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <label class="fw-semibold" for="uraian_skala_usaha">Uraian Skala Usaha</label>
                <select class="form-select" name="uraian_skala_usaha" id="uraian_skala_usaha">
                    <option value="">Pilih Semua</option>
                    @foreach ($uraianSkalaUsahaOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-3">
                <label class="fw-semibold" for="uraian_risiko_proyek">Uraian Risiko proyek</label>
                <select class="form-select" name="uraian_risiko_proyek" id="uraian_risiko_proyek">
                    <option value="">Pilih Semua</option>
                    @foreach ($uraianRisikoProyekOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-3">
                <label class="fw-semibold" for="uraian_jenis_proyek">Uraian Jenis Proyek</label>
                <select class="form-select" name="uraian_jenis_proyek" id="uraian_jenis_proyek">
                    <option value="">Pilih Semua</option>
                    @foreach ($uraianJenisProyekOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-3">
                <label class="fw-semibold" for="uraian_status_penanaman_modal">Uraian Status Penanaman Modal</label>
                <select class="form-select" name="uraian_status_penanaman_modal" id="uraian_status_penanaman_modal">
                    <option value="">Pilih Semua</option>
                    @foreach ($uraianStatusPenanamanModalOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 mt-2">
                <table id="datatable" class="table table-striped table-hover w-100">
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
    });

    (function() {
        let currentTahun = @json($tahun);

        $("#uraian_skala_usaha,#uraian_risiko_proyek,#uraian_jenis_proyek,#uraian_status_penanaman_modal").on("change", function() {
            changeAjaxUrl()
        });

        function changeAjaxUrl() {
            let ajaxUrl = @json(route('datakita.grafik-rekap-kbli.ajax')) + `?tahun=${currentTahun}`;

            let uraian_skala_usaha = $("#uraian_skala_usaha").val();
            if (uraian_skala_usaha != "") ajaxUrl += `&uraian_skala_usaha=${encodeURIComponent(uraian_skala_usaha)}`

            let uraian_risiko_proyek = $("#uraian_risiko_proyek").val();
            if (uraian_risiko_proyek != "") ajaxUrl += `&uraian_risiko_proyek=${encodeURIComponent(uraian_risiko_proyek)}`

            let uraian_jenis_proyek = $("#uraian_jenis_proyek").val();
            if (uraian_jenis_proyek != "") ajaxUrl += `&uraian_jenis_proyek=${encodeURIComponent(uraian_jenis_proyek)}`

            let uraian_status_penanaman_modal = $("#uraian_status_penanaman_modal").val();
            if (uraian_status_penanaman_modal != "") ajaxUrl += `&uraian_status_penanaman_modal=${encodeURIComponent(uraian_status_penanaman_modal)}`

            hasil_table.ajax.url(ajaxUrl);
            hasil_table.ajax.reload();
        }

        $("#btn_export").on('click', function() {
            let ajaxUrl = @json(route('datakita.grafik-rekap-kbli.export')) + `?`;

            let uraian_skala_usaha = $("#uraian_skala_usaha").val();
            if (uraian_skala_usaha != "") ajaxUrl += `&uraian_skala_usaha=${encodeURIComponent(uraian_skala_usaha)}`

            let uraian_risiko_proyek = $("#uraian_risiko_proyek").val();
            if (uraian_risiko_proyek != "") ajaxUrl += `&uraian_risiko_proyek=${encodeURIComponent(uraian_risiko_proyek)}`

            let uraian_jenis_proyek = $("#uraian_jenis_proyek").val();
            if (uraian_jenis_proyek != "") ajaxUrl += `&uraian_jenis_proyek=${encodeURIComponent(uraian_jenis_proyek)}`

            let uraian_status_penanaman_modal = $("#uraian_status_penanaman_modal").val();
            if (uraian_status_penanaman_modal != "") ajaxUrl += `&uraian_status_penanaman_modal=${encodeURIComponent(uraian_status_penanaman_modal)}`

            let search = hasil_table.search();

            window.open(`${ajaxUrl}&searchValue=${encodeURIComponent(search)}&tahun=${currentTahun}`, "_blank");
        });

        let hasil_table = $('#datatable').DataTable({
            ajax: {
                url: @json(route('datakita.grafik-rekap-kbli.ajax')) + `?tahun=${currentTahun}`,
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
            layout: {
                topEnd: {
                    search: {
                        placeholder: 'KBLI'
                    }
                }
            },
            processing: true,
            serverSide: true,
            order: [1, 'asc'],
            columns: [{
                    orderable: false,
                    title: "no",
                    name: "no",
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { title: "KBLI", name: "kbli", data: "kbli" },
                {
                    title: "Jumlah Tenaga Kerja",
                    name: "jml_tki",
                    data: "jml_tki",
                    render: function(data, type, row, meta) {
                        return `${Number(data).toLocaleString("id-ID")}`
                    }
                },
                {
                    title: "Jumlah Investasi",
                    name: "jml_investasi",
                    data: "jml_investasi",
                    render: function(data, type, row, meta) {
                        return `Rp. ${Number(data).toLocaleString("id-ID")}`
                    }
                },
            ]
        });
    })()
</script>
