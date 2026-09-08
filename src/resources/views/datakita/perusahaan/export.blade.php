<!DOCTYPE html>
<html>

<head>
    <title>Export Data</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Pencarian dari Perusahaan</h2>
        <table border="1" class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Perusahaan</th>
                    <th>NIB</th>
                    <th>Alamat</th>
                    <th>Kelurahan</th>
                    <th>Kecamatan</th>
                    <th>Email</th>
                    <th>Tanggal Terbit OSS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_perusahaan }}</td>
                        <td>NIB: {{ $row->nib }}</td>
                        <td>{{ $row->alamat_perusahaan }}</td>
                        <td>{{ $row->kelurahan }}</td>
                        <td>{{ $row->kecamatan }}</td>
                        <td>{{ $row->email }}</td>
                        <td>{{ $row->day_of_tanggal_terbit_oss }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
