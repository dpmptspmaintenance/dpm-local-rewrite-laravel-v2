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
        <h2>Hasil Pencarian Data Perizinan OSS-RBA</h2>
        <table border="1" class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Perusahaan</th>
                    <th>NIB</th>
                    <th>Tanggal Terbit OSS</th>
                    <th>Resiko</th>
                    <th>KBLI</th>
                    <th>Judul KBLI</th>
                    <th>Kelurahan</th>
                    <th>Kecamatan</th>
                    <th>Jenis Perizinan</th>
                    <th>Nama Dokumen</th>
                    <th>Status Respon</th>
                    <th>Sektor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_perusahaan }}</td>
                        <td>NIB: {{ $row->nib }}</td>
                        <td>{{ $row->day_of_tanggal_terbit_oss }}</td>
                        <td>{{ $row->resiko }}</td>
                        <td>{{ $row->kbli }}</td>
                        <td>{{ $row->judul_kbli }}</td>
                        <td>{{ $row->kelurahan }}</td>
                        <td>{{ $row->kecamatan }}</td>
                        <td>{{ $row->uraian_jenis_perizinan }}</td>
                        <td>{{ $row->nama_dokumen }}</td>
                        <td>{{ $row->uraian_status_respon }}</td>
                        <td>{{ $row->kl_sektor }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
