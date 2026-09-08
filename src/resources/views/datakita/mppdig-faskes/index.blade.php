<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Faskes MPP Digital — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; } .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: rgba(255,255,255,0.9); }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title h4">Cari Data Faskes MPP Digital</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.mppdig-faskes.index') }}" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-semibold form-label" for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="{{ $nama }}" placeholder="Masukkan Nama">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold form-label" for="kategori">Kategori</label>
                        <select class="form-select" name="kategori" id="kategori">
                            <option value="">-- Semua Kategori --</option>
                            @foreach ($kategoris as $kt)
                                <option value="{{ $kt }}" {{ $kategori == $kt ? 'selected' : '' }}>{{ $kt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="fw-semibold form-label" for="alamat">Alamat</label>
                        <input type="text" class="form-control" id="alamat" name="alamat" value="{{ $alamat }}" placeholder="Masukkan Alamat">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($nama !== '' || $alamat !== '' || $kategori !== '')
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.mppdig-faskes.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.mppdig-faskes.export', request()->query()) }}" class="btn btn-success">EXPORT KE EXCEL</a>
            </div>
        @endif

        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h2 class="card-title h5">Hasil Pencarian</h2>
            </div>
            <div class="card-body table-responsive">
                @if ($rows->isNotEmpty())
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Alamat</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->nama }}</td>
                                    <td>{{ $row->email ?? '' }}</td>
                                    <td>{{ $row->alamat }}</td>
                                    <td>{{ $row->kategori }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center">Tidak ada data yang ditemukan</p>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
