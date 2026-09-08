<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Proyek — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: "Moderustic", sans-serif; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2 mt-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title">Cari Data Proyek</h1>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-3">
                        <label class="fw-semibold" for="tahun">Pilih Tahun</label>
                        <select class="form-select" name="tahun" id="tahun">
                            <option value="">-- Pilih Tahun --</option>
                            @foreach (range(2020, (int) date('Y')) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="fw-semibold" for="bulan">Pilih Bulan</label>
                        <select class="form-select" name="bulan" id="bulan">
                            <option value="">-- Pilih Bulan --</option>
                            @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $i => $namaBulan)
                                <option value="{{ $i + 1 }}">{{ $namaBulan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="fw-semibold" for="uraian_status_penanaman_modal">Status Penanaman Modal</label>
                        <select class="form-select" name="uraian_status_penanaman_modal" id="uraian_status_penanaman_modal">
                            <option value="">-- Pilih Status Penanaman Modal --</option>
                            @foreach ($statusPenanamanModal as $row)
                                <option value="{{ $row->kode_sandal }}">{{ $row->nama_sandal }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="fw-semibold" for="uraian_jenis_perusahaan">Jenis Perusahaan</label>
                        <select class="form-select" name="uraian_jenis_perusahaan" id="uraian_jenis_perusahaan">
                            <option value="">-- Pilih Jenis Perusahaan --</option>
                            @foreach ($jenisPerusahaan as $row)
                                <option value="{{ $row->Uraian }}">{{ $row->Uraian }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3 mt-4">
                        <label class="fw-semibold" for="uraian_risiko_proyek">Resiko Proyek</label>
                        <select class="form-select" name="uraian_risiko_proyek" id="uraian_risiko_proyek">
                            <option value="">-- Pilih Resiko Proyek --</option>
                            <option value="UMK">UMK</option>
                            <option value="Non UMK">Non UMK</option>
                        </select>
                    </div>
                    <div class="col-3 mt-4">
                        <label class="fw-semibold" for="uraian_skala_usaha">Skala Usaha</label>
                        <select class="form-select" name="uraian_skala_usaha" id="uraian_skala_usaha">
                            <option value="">-- Pilih Skala Usaha --</option>
                            @foreach ($skalaUsaha as $row)
                                <option value="{{ $row->skala_usaha }}">{{ $row->skala_usaha }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3 mt-4">
                        <label class="fw-semibold" for="kecamatan_usaha">Kecamatan</label>
                        <select class="form-select" name="kecamatan_usaha" id="kecamatan_usaha">
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach ($kecamatan as $row)
                                <option value="{{ $row->Kecamatan }}">{{ $row->Kecamatan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3 mt-4">
                        <label class="fw-semibold" for="sektor_pembina">Sektor Pembina</label>
                        <select class="form-select" name="sektor_pembina" id="sektor_pembina">
                            <option value="">-- Pilih Sektor Pembina --</option>
                            @foreach ($pembina as $row)
                                <option value="{{ $row->pembina }}">{{ $row->pembina }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 mt-4">
                        <label class="fw-semibold" for="sektor">Sektor</label>
                        <select class="form-select" name="sektor" id="sektor">
                            <option value="">-- Pilih Sektor --</option>
                            @foreach ($sektor as $row)
                                <option value="{{ $row->Id }}">{{ $row->nama_sektor }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button id="cari" class="btn btn-primary w-100">Cari</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="tmpt_search"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
        });

        $("#cari").on("click", function() {
            $("#tmpt_search").load(@json(route('datakita.proyek.search')));
        });
    </script>
</body>

</html>
