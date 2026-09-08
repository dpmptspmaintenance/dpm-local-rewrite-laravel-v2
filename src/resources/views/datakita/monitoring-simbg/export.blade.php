<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Export Data Monitoring SIMBG</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Pencarian dari Tabel Monitoring SIMBG</h2>
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
            <p>Tidak Ada Data Dari Hasil Pencarian dari Tabel Monitoring SIMBG</p>
        @endif
    </div>
</body>

</html>
