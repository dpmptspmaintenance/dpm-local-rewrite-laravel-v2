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
        <h2>Hasil Pencarian dari Data Proyek</h2>
        <table border="1" class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Perusahaan</th>
                    <th>Nama Proyek</th>
                    <th>Tanggal Terbit</th>
                    <th>NIB</th>
                    <th>Alamat</th>
                    <th>Kecamatan</th>
                    <th>Kelurahan</th>
                    <th>KBLI</th>
                    <th>Judul KBLI</th>
                    <th>Resiko Proyek</th>
                    <th>Jenis Perusahaan</th>
                    <th>Skala Usaha</th>
                    <th>Sektor Pembina</th>
                    <th>Luas Tanah</th>
                    <th>Jumlah Investasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_perusahaan }}</td>
                        <td>{{ $row->nama_proyek }}</td>
                        <td>{{ $row->tanggal_terbit_oss }}</td>
                        <td>{{ $row->nib }}</td>
                        <td>{{ $row->alamat_usaha }}</td>
                        <td>{{ $row->kecamatan_usaha }}</td>
                        <td>{{ $row->kelurahan_usaha }}</td>
                        <td>{{ $row->kbli }}</td>
                        <td>{{ $row->judul_kbli }}</td>
                        <td>{{ $row->uraian_risiko_proyek }}</td>
                        <td>{{ $row->uraian_jenis_perusahaan }}</td>
                        <td>{{ $row->uraian_skala_usaha }}</td>
                        <td>{{ $row->sektor_pembina }}</td>
                        <td>{{ $row->luas_tanah.' '.$row->satuan_tanah }}</td>
                        <td>{{ $row->jumlah_investasi3 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
