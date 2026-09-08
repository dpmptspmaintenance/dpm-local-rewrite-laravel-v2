<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Data Perizinan OSS-RBA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Moderustic", sans-serif; }
        .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .table-container { overflow-x: auto; }
        table { margin-top: 20px; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title">Tracking Data Perizinan OSS-RBA</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.ossrba-tracking.index') }}" method="GET" class="row g-3">
                    <div class="col-12">
                        <label class="fw-semibold" for="id_permohonan_izin">ID Permohonan</label>
                        <input type="text" class="form-control" id="id_permohonan_izin" name="id_permohonan_izin"
                            placeholder="Masukan ID Permohonan" value="{{ $idPermohonanIzin }}">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($searched)
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h2>Tracking OSS-RBA</h2>
            </div>
            <div class="card-body table-container">
                @if ($results->count() > 0)
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Permohonan Izin</th>
                            <th>Nama Perusahaan</th>
                            <th>Tanggal Permohonan</th>
                            <th class="d-flex justify-content-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->id_permohonan_izin }}</td>
                            <td>{{ $row->nama_perusahaan }}</td>
                            <td>{{ $row->tanggal_permohonan }}</td>
                            <td class="d-flex justify-content-center">
                                @if ($row->uraian_status_respon === 'Terbit Otomatis')
                                <p class="text-white bg-success text-center rounded-pill p-2" style="max-width: 150px;">
                                    {{ $row->uraian_status_respon }}
                                </p>
                                @else
                                <p class="text-white bg-warning text-center rounded-pill p-2" style="max-width: 250px;">
                                    {{ $row->uraian_status_respon }}
                                </p>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-center">Tidak Ada Data Yang Ditemukan</p>
                @endif
            </div>
        </div>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
