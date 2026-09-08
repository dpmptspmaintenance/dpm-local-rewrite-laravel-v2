<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar File — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css">
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
                <h1 class="card-title h4">Daftar File — {{ strtoupper($klasifikasi) }}</h1>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Upload File
                </button>
                <table id="dataTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama File</th>
                            <th>Download File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($files as $row)
                            <tr>
                                <th>{{ $loop->iteration }}</th>
                                <td>{{ $row->nama_file }}</td>
                                <td><a href="{{ $row->lokasi_file }}" target="_blank">Download File</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Upload file ini berguna untuk menyimpan file yang sudah anda download lalu akan disimpan pada sistem kita. Pastikan beri nama file sesuai dengan isi yang ada di dalam file yang ingin di upload.</p>
                    <form action="{{ route('datakita.daftar-file.upload', ['klasifikasi' => $klasifikasi]) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label class="fw-semibold" for="inputGroupFile01">Pilih File</label>
                            <input type="file" name="berkas" class="form-control" id="inputGroupFile01" required>
                        </div>
                        <div class="mt-2">
                            <label class="fw-semibold" for="klasifikasi">Klasifikasi</label>
                            <input type="text" name="klasifikasi" class="form-control" id="klasifikasi" value="{{ $klasifikasi }}" disabled>
                        </div>
                        <input type="submit" class="btn btn-sm btn-success mt-4" name="upload" value="upload" />
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });
        });
    </script>
</body>

</html>
