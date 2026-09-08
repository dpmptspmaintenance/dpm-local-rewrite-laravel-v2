<!DOCTYPE html>
<html>

<head>
    <title>Export Data Rekap List Izin</title>
    <style type="text/css">
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Export Data Rekap List Izin</h2>
        @if (count($rows) > 0)
            <table border="1" class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Perusahaan</th>
                        <th>NIB</th>
                        <th>Uraian Status Respon</th>
                        <th>Uraian Jenis Perizinan</th>
                        <th>Uraian Status Penanaman Modal</th>
                        <th>Kbli</th>
                        <th>Bulan</th>
                        <th>Tahun</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->nama_perusahaan }}</td>
                            <td>{{ $row->nib }}</td>
                            <td>{{ $row->uraian_status_respon }}</td>
                            <td>{{ $row->uraian_jenis_perizinan }}</td>
                            <td>{{ $row->uraian_status_penanaman_modal }}</td>
                            <td>{{ $row->kbli }}</td>
                            <td>{{ $row->bulan }}</td>
                            <td>{{ $row->tahun }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Tidak ada data yang ditemukan.</p>
        @endif
    </div>
</body>

</html>
