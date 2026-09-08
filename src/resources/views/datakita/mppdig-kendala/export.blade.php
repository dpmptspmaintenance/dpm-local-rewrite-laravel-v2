<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Export Data Kendala MPP Digital</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Pencarian Dari Data Kendala MPP Digital</h2>
        @if ($rows->isNotEmpty())
            <table class="table" border="1">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Kendala</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->nama }}</td>
                            <td>{{ $row->email }}</td>
                            <td>{{ $row->kendala }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->keterangan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Tidak Ada Data Yang Ditemukan Dari Pencarian Data Kendala MPP Digital</p>
        @endif
    </div>
</body>

</html>
