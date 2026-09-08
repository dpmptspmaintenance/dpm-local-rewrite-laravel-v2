<!DOCTYPE html>
<html>

<head>
    <title>Export Data</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Pencarian dari KBLI Perkecamatan</h2>
        @if ($rows->count() > 0)
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kecamatan</th>
                        <th>Kelurahan</th>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>KBLI</th>
                        <th>Judul KBLI</th>
                        <th>Jumlah KBLI</th>
                        <th>Total Investasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->kecamatan }}</td>
                            <td>{{ $row->kelurahan }}</td>
                            <td>{{ $row->tahun }}</td>
                            <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                            <td>{{ $row->kbli }}</td>
                            <td>{{ $row->judul_kbli }}</td>
                            <td>{{ $row->jumlah_kbli }}</td>
                            <td>Rp.{{ number_format($row->total_investasi, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Tidak ada data yang ditemukan dari Proyek.</p>
        @endif
    </div>
</body>

</html>
