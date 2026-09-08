<div class="card my-5" id="table-data">
    <div class="card-header bg-primary text-white">
        <h1 class="card-title">Tabel List Izin {{ $tahun }}</h1>
    </div>
    <div class="card-body">

        <div class="row">
            <div class="col-3 my-2">
                <button target="_blank" id="btn_export" class="btn btn-success">Export ke Excel</button>
            </div>
        </div>

        <div class="row">
            <div class="col-3">
                <label class="fw-semibold" for="uraian_jenis_perizinan">Uraian Jenis Perizinan</label>
                <select class="form-select" name="uraian_jenis_perizinan" id="uraian_jenis_perizinan">
                    <option value="">Pilih Semua</option>
                    @foreach ($jenisPerizinanOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-3">
                <label class="fw-semibold" for="uraian_status_respon">Uraian Status Respon</label>
                <select class="form-select" name="uraian_status_respon" id="uraian_status_respon">
                    <option value="">Pilih Semua</option>
                    @foreach ($statusResponOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-3">
                <label class="fw-semibold" for="uraian_status_penanaman_modal">Uraian Status Penanaman Modal</label>
                <select class="form-select" name="uraian_status_penanaman_modal" id="uraian_status_penanaman_modal">
                    <option value="">Pilih Semua</option>
                    @foreach ($statusPenanamanModalOptions as $opt)
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

        $("#uraian_jenis_perizinan,#uraian_status_respon,#uraian_status_penanaman_modal").on("change", function() {
            changeAjaxUrl()
        });

        function changeAjaxUrl() {
            let ajaxUrl = @json(route('datakita.grafik-rekap-list-izin.ajax')) + `?tahun=${currentTahun}`;

            let uraian_jenis_perizinan = $("#uraian_jenis_perizinan").val();
            if (uraian_jenis_perizinan != "") ajaxUrl += `&uraian_jenis_perizinan=${encodeURIComponent(uraian_jenis_perizinan)}`

            let uraian_status_respon = $("#uraian_status_respon").val();
            if (uraian_status_respon != "") ajaxUrl += `&uraian_status_respon=${encodeURIComponent(uraian_status_respon)}`

            let uraian_status_penanaman_modal = $("#uraian_status_penanaman_modal").val();
            if (uraian_status_penanaman_modal != "") ajaxUrl += `&uraian_status_penanaman_modal=${encodeURIComponent(uraian_status_penanaman_modal)}`

            hasil_table.ajax.url(ajaxUrl);
            hasil_table.ajax.reload();
        }

        $("#btn_export").on('click', function() {
            let ajaxUrl = @json(route('datakita.grafik-rekap-list-izin.export')) + `?`;

            let uraian_jenis_perizinan = $("#uraian_jenis_perizinan").val();
            if (uraian_jenis_perizinan != "") ajaxUrl += `&uraian_jenis_perizinan=${encodeURIComponent(uraian_jenis_perizinan)}`

            let uraian_status_respon = $("#uraian_status_respon").val();
            if (uraian_status_respon != "") ajaxUrl += `&uraian_status_respon=${encodeURIComponent(uraian_status_respon)}`

            let uraian_status_penanaman_modal = $("#uraian_status_penanaman_modal").val();
            if (uraian_status_penanaman_modal != "") ajaxUrl += `&uraian_status_penanaman_modal=${encodeURIComponent(uraian_status_penanaman_modal)}`

            let search = hasil_table.search();

            window.open(`${ajaxUrl}&searchValue=${encodeURIComponent(search)}&tahun=${currentTahun}`, "_blank");
        });

        let hasil_table = $('#datatable').DataTable({
            ajax: {
                url: @json(route('datakita.grafik-rekap-list-izin.ajax')) + `?tahun=${currentTahun}`,
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
            order: [7, 'asc'],
            columns: [{
                    orderable: false,
                    title: "no",
                    name: "no",
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { title: "Nama Perusahaan", name: "nama_perusahaan", data: "nama_perusahaan" },
                { title: "NIB", name: "nib", data: "nib" },
                { title: "Uraian Status Respon", name: "uraian_status_respon", data: "uraian_status_respon" },
                { title: "Uraian Jenis Perizinan", name: "uraian_jenis_perizinan", data: "uraian_jenis_perizinan" },
                { title: "Uraian Status Penanaman Modal", name: "uraian_status_penanaman_modal", data: "uraian_status_penanaman_modal" },
                { title: "Kbli", name: "kbli", data: "kbli" },
                { title: "Bulan", name: "bulan", data: "bulan" },
            ]
        });
    })()
</script>
