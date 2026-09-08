<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Data MPP Digital — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <h1 class="card-title h4">Tracking Data MPP Digital</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.mppdig-tracking.index') }}" method="GET" class="row g-3">
                    <div class="col-12">
                        <label class="fw-semibold" for="no_reg">No Registrasi</label>
                        <input type="text" class="form-control" id="no_reg" name="no_reg" value="{{ $noReg }}" placeholder="Masukan No Registrasi">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($noReg !== '')
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h2 class="h5">Tracking MPP Digital</h2>
                </div>
                <div class="card-body table-responsive">
                    @if ($rows->isNotEmpty())
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No Registrasi</th>
                                    <th>Nama</th>
                                    <th class="d-flex justify-content-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row->no_reg }}</td>
                                        <td>{{ $row->nama }}</td>
                                        <td class="d-flex justify-content-center">
                                            @if ($row->status_permohonan === 'SK Diterbitkan')
                                                <p class="text-white bg-success text-center rounded-pill p-2" style="max-width: 150px;">{{ $row->status_permohonan }}</p>
                                            @elseif ($row->status_permohonan === 'Dibatalkan')
                                                <p class="text-white bg-danger text-center rounded-pill p-2" style="max-width: 250px;">{{ $row->status_permohonan }}</p>
                                            @else
                                                <p class="text-white bg-warning text-center rounded-pill p-2" style="max-width: 250px;">{{ $row->status_permohonan }}</p>
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
