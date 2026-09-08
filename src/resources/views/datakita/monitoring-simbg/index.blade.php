<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring SIMBG — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
                <h1 class="card-title h4">Cari Data Monitoring SIMBG</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.monitoring-simbg.index') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="fw-semibold form-label" for="tahun">Pilih Tahun</label>
                        <select class="form-select" name="tahun" id="tahun">
                            <option value="">-- Pilih Tahun --</option>
                            @for ($y = 2015; $y <= (int) date('Y'); $y++)
                                <option value="{{ $y }}" {{ $filters['tahun'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold form-label" for="bulan">Pilih Bulan</label>
                        <select class="form-select" name="bulan" id="bulan">
                            <option value="">-- Pilih Bulan --</option>
                            @foreach ([1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $num => $nama)
                                <option value="{{ $num }}" {{ $filters['bulan'] == $num ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold form-label" for="jenis_registrasi">Jenis Registrasi</label>
                        <select class="form-select" name="jenis_registrasi" id="jenis_registrasi">
                            <option value="">-- Pilih Jenis Registrasi --</option>
                            @foreach ($jenisRegist as $row)
                                <option value="{{ $row->jenis_registrasi }}" {{ $filters['jenis_registrasi'] == $row->jenis_registrasi ? 'selected' : '' }}>{{ $row->uraian_jenis_registrasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold form-label" for="jenis_konsultasi">Jenis Konsultasi</label>
                        <select class="form-select" name="jenis_konsultasi" id="jenis_konsultasi">
                            <option value="">-- Pilih Jenis Konsultasi --</option>
                            @foreach ($jenisKonsultasi as $row)
                                <option value="{{ $row->jenis_konsultasi }}" {{ $filters['jenis_konsultasi'] == $row->jenis_konsultasi ? 'selected' : '' }}>{{ $row->jenis_konsultasi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold form-label" for="fungsi_bg">Fungsi Bangunan Gedung</label>
                        <select class="form-select" name="fungsi_bg" id="fungsi_bg">
                            <option value="">-- Pilih Fungsi Bangunan Gedung --</option>
                            @foreach ($fungsiBg as $row)
                                <option value="{{ $row->fungsi_bg }}" {{ $filters['fungsi_bg'] == $row->fungsi_bg ? 'selected' : '' }}>{{ $row->fungsi_bg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold form-label" for="status">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">-- Pilih Status --</option>
                            @foreach ($statuses as $st)
                                <option value="{{ $st }}" {{ $filters['status'] == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="fw-semibold form-label" for="input_antara">Nama Pemilik, Lokasi Bangunan Gedung</label>
                        <input type="text" class="form-control" id="input_antara" name="input_antara" value="{{ $filters['input_antara'] }}" placeholder="Masukan Nama Pemilik, Lokasi Bangunan Gedung">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($rows->isNotEmpty() || request()->hasAny(['tahun','bulan','input_antara','jenis_registrasi','jenis_konsultasi','fungsi_bg','status']))
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('datakita.monitoring-simbg.index') }}" class="btn btn-warning">Reset Filter</a>
                <a target="_blank" href="{{ route('datakita.monitoring-simbg.export', request()->query()) }}" class="btn btn-success">Export ke Excel</a>
            </div>
        @endif

        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h2 class="card-title h5">Hasil Pencarian Monitoring SIMBG</h2>
            </div>
            <div class="card-body table-responsive">
                @if ($rows->isNotEmpty())
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Pengambilan SK</th>
                                <th>Nama Pengambil</th>
                                <th>No Registrasi</th>
                                <th>Nama Pemilik</th>
                                <th>Lokasi Bangunan</th>
                                <th>Fungsi Bangunan</th>
                                <th>Luas Bangunan</th>
                                <th>No SK PBG</th>
                                <th>Tanggal SK PBG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->tgl_pengambilan_sk }}</td>
                                    <td>{{ $row->nama_pengambil_sk }}</td>
                                    <td>{{ $row->no_registrasi }}</td>
                                    <td>{{ $row->nama_pemilik }}</td>
                                    <td>{{ $row->alamat }}</td>
                                    <td>{{ $row->fungsi_bangunan }}</td>
                                    <td>{{ $row->luas_bangunan }}</td>
                                    <td>{{ $row->no_dokumen_pbg }}</td>
                                    <td>{{ $row->tgl_dokumen_pbg }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-center">Tidak Ada Data Yang Ditemukan</p>
                @endif
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
