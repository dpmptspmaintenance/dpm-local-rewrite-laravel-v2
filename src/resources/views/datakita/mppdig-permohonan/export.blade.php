<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Export Data Permohonan MPP Digital</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Pencarian Dari Data Permohonan MPP Digital {{ $start }} - {{ $end }}</h2>
        @if ($rows->isNotEmpty())
            <table border="1" class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Registrasi</th>
                        <th>Nama</th>
                        <th>Profesi</th>
                        <th>Tempat Praktik</th>
                        <th>Status Permohonan</th>
                        <th>Tanggal Permohonan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->no_reg }}</td>
                            <td>{{ $row->nama }}</td>
                            <td>{{ $row->profesi }}</td>
                            <td>{{ $row->tempat_praktik }}</td>
                            <td>{{ $row->status_permohonan }}</td>
                            <td>{{ $row->tgl_permohonan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Tidak Ada Data Yang Ditemukan Dari Pencarian Data Permohonan MPP Digital</p>
        @endif
    </div>
</body>

</html>
