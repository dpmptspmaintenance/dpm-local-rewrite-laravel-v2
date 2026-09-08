<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Perizinan Bangunan Gedung (SIMBG) — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: "Moderustic", sans-serif; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-uppercase">Data Perizinan Bangunan Gedung</h4>
            <a href="{{ route('datakita.overview.simbg-resume-pertahun.export') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered text-center mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Tahun</th>
                                <th>Status</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($rows->isNotEmpty())
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="fw-bold">{{ $row['tahun'] }}</td>
                                        <td class="text-start ps-4">{{ $row['status_izin'] }}</td>
                                        <td class="fw-bold">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="py-5">Data tidak ditemukan atau query bermasalah.</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr class="fw-bold">
                                <td colspan="2">TOTAL KESELURUHAN</td>
                                <td>{{ number_format($total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
