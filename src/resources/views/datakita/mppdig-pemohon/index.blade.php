<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Data Pemohon MPP Digital — Data Kita</title>
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
                <h1 class="card-title h4">Cari Data Pemohon MPP Digital</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.mppdig-pemohon.index') }}" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-semibold" for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="{{ $nama }}" placeholder="Masukan nama">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold" for="alamat">Alamat</label>
                        <input type="text" class="form-control" id="alamat" name="alamat" value="{{ $alamat }}" placeholder="Masukan Alamat">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold" for="gender">Gender</label>
                        <select class="form-select" name="gender" id="gender">
                            <option value="">-- Pilih Status Pemohon --</option>
                            <option value="Laki-Laki" {{ $gender == 'Laki-Laki' ? 'selected' : '' }}>Laki-Laki</option>
                            <option value="Perempuan" {{ $gender == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($nama !== '' || $gender !== '' || $alamat !== '')
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.mppdig-pemohon.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.mppdig-pemohon.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>
        @endif

        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h2 class="card-title h5">Hasil Pencarian Data Pemohon MPP Digital</h2>
            </div>
            <div class="card-body table-responsive">
                @if ($rows->isNotEmpty())
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>No Telepon</th>
                                <th>Gender</th>
                                <th>Alamat</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->nama }}</td>
                                    <td>{{ $row->telp }}</td>
                                    <td>{{ $row->gender }}</td>
                                    <td>{{ $row->alamat }}</td>
                                    <td>{{ $row->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>Tidak Ada Data Yang Ditemukan Dari Pencarian Data Pemohon MPP Digital</p>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
