<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notulen Rapat — RapatKita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <style>
        html, body { font-family: "Moderustic", sans-serif; height: 100%; width: 100%; }
        .container { padding-top: 30px; }
        .card-header { background-color: #007bff; color: white; }
        .card-body { background-color: #f8f9fa; }
        .card { margin-bottom: 20px; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>

<body>
    @include('rapatkita.partials.navbar')

    <div class="container">
        <h3 class="text-center">Daftar Notulen Rapat</h3>

        <div class="card">
            <div class="card-header">
                <h5>List Notulen</h5>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notulenList as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row->nama_kegiatan }}</td>
                                <td>{{ $row->tanggal?->translatedFormat('d F Y') }}</td>
                                <td>
                                    <a href="{{ route('rapatkita.notulen.show', $row) }}" class="btn btn-success btn-sm">Lihat</a>
                                    <a href="{{ route('rapatkita.notulen.edit', $row) }}" class="btn btn-primary btn-sm mt-2">Ubah</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-center text-black fw-semibold" style="font-size: 18px;">
        © DPMPTSP RapatKita 2.1.1 2025
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

        @if (session('success'))
            swal("Berhasil!", @json(session('success')), "success");
        @endif
    </script>
</body>

</html>
