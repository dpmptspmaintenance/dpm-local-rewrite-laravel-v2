<!DOCTYPE html>
<html>

<head>
    <title>Export Data Rekap KBLI</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Export Data Rekap KBLI</h2>
        @if (count($rows) > 0)
            <table border="1" class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>KBLI</th>
                        <th>Jumlah Tenaga Kerja</th>
                        <th>Jumlah Investasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->kbli }}</td>
                            <td>{{ $row->jml_tki }}</td>
                            <td>{{ $row->jml_investasi }}</td>
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
