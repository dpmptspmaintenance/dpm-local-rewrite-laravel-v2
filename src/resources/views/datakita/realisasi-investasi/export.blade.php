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
    <h2>Hasil Export Data Realisasi Investasi</h2>
    @if ($rows->count() > 0)
        <table border="1">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Perusahaan</th>
                    <th>No. Izin</th>
                    <th>No. Proyek</th>
                    <th>Status</th>
                    <th>Negara</th>
                    <th>Tahun</th>
                    <th>Triwulan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_perusahaan }}</td>
                        <td>{{ $row->no_izin }}</td>
                        <td>{{ $row->no_proyek }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->negara }}</td>
                        <td>{{ $row->tahun }}</td>
                        <td>{{ $row->triwulan }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Tidak ada data yang ditemukan.</p>
    @endif
</body>

</html>
