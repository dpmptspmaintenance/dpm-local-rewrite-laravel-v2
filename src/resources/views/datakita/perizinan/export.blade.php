<!DOCTYPE html>
<html>

<head>
    <title>Export Data Proyek & Perizinan</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Data Proyek, Kantor, dan Perizinan</h2>
        <table border="1" class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Proyek</th>
                    <th>NIB</th>
                    <th>Nama Perusahaan</th>
                    <th>Alamat Usaha</th>
                    <th>Kecamatan Usaha</th>
                    <th>Alamat Kantor (NIB)</th>
                    <th>Email Kantor</th>
                    <th>Nama Proyek</th>
                    <th>KBLI</th>
                    <th>Judul KBLI</th>
                    <th>Resiko Proyek</th>
                    <th>Skala Usaha</th>
                    <th>Sektor Pembina</th>
                    <th>Luas Tanah</th>
                    <th>Jumlah Investasi</th>
                    <th>ID Permohonan Izin</th>
                    <th>Jenis Perizinan</th>
                    <th>Nama Dokumen</th>
                    <th>Status Respon Izin</th>
                    <th>Tanggal Izin</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->id_proyek }}</td>
                        <td>{{ $row->nib }}</td>
                        <td>{{ $row->nama_perusahaan }}</td>
                        <td>{{ $row->alamat_usaha }}</td>
                        <td>{{ $row->kecamatan_usaha }}</td>
                        <td>{{ $row->alamat_kantor ?? '-' }}</td>
                        <td>{{ $row->email_kantor ?? '-' }}</td>
                        <td>{{ $row->nama_proyek }}</td>
                        <td>{{ $row->kbli }}</td>
                        <td>{{ $row->judul_kbli }}</td>
                        <td>{{ $row->uraian_risiko_proyek }}</td>
                        <td>{{ $row->uraian_skala_usaha }}</td>
                        <td>{{ $row->sektor_pembina }}</td>
                        <td>{{ $row->luas_tanah.' '.$row->satuan_tanah }}</td>
                        <td>{{ $row->jumlah_investasi3 }}</td>
                        <td>{{ $row->id_permohonan_izin ?? '-' }}</td>
                        <td>{{ $row->uraian_jenis_perizinan ?? '-' }}</td>
                        <td>{{ $row->nama_dokumen ?? '-' }}</td>
                        <td>{{ $row->uraian_status_respon ?? '-' }}</td>
                        <td>{{ $row->day_of_tanggal_izin ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
