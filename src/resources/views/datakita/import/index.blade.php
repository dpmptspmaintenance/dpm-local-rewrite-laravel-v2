<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="container">
        <div class="row my-4">
            <div class="col-6">
                <h2>Upload File DP Proyek</h2>
                <p class="text-muted mb-2"><small>Terakhir diupload: <strong>{{ $lastDpProyek }}</strong></small></p>
                <form action="{{ route('datakita.import.validate-dp-proyek') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_proyek">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file" id="excel_file_proyek" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6">
                <h2>Upload File DP NIB Kantor</h2>
                <p class="text-muted mb-2"><small>Terakhir diupload: <strong>{{ $lastDpKantor }}</strong></small></p>
                <form action="{{ route('datakita.import.validate-dp-kantor') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_kantor">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file" id="excel_file_kantor" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6 mt-4">
                <h2>Upload File List Izin</h2>
                <p class="text-muted mb-2"><small>Terakhir diupload: <strong>{{ $lastListIzin }}</strong></small></p>
                <form action="{{ route('datakita.import.validate-list-izin') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_izin">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file" id="excel_file_izin" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6 mt-4">
                <h2>Upload File Simbg Monitoring</h2>
                <form action="{{ route('datakita.import.validate-simbg-monitoring') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_simbg">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file" id="excel_file_simbg" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6 mt-4">
                <h2>Upload File MPPD Semua</h2>
                <form action="{{ route('datakita.import.validate-mppd-semua') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_mppd_semua">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file_mppd_semua" id="excel_file_mppd_semua" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6 mt-4">
                <h2>Upload File MPPD Selesai</h2>
                <form action="{{ route('datakita.import.validate-mppd-selesai') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_mppd_selesai">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file_mppd_selesai" id="excel_file_mppd_selesai" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6 mt-4">
                <h2>Upload File MPPD Next Gen Selesai</h2>
                <form action="{{ route('datakita.import.validate-mppd-nextgen-selesai') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_mppd_nextgen_selesai">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file_mppd_nextgen_selesai" id="excel_file_mppd_nextgen_selesai" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>

            <div class="col-6 mt-4">
                <h2>Upload File MPPD Next Gen Ditolak</h2>
                <form action="{{ route('datakita.import.validate-mppd-nextgen-ditolak') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label for="excel_file_mppd_nextgen_ditolak">Pilih File Excel (.xlsx, .xls):</label>
                    <input type="file" name="excel_file_mppd_nextgen_ditolak" id="excel_file_mppd_nextgen_ditolak" required class="form form-control mb-2">
                    <button type="submit" class="btn btn-primary">Upload & Validasi</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
