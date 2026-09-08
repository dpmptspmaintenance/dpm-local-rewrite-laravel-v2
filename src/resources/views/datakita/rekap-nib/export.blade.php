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
        <h2>Hasil Pencarian dari Rekap Jumlah NIB Baru</h2>
        @if ($rows->count() > 0)
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        @if ($bulan === '')
                            <th>Jumlah NIB</th>
                            <th>Perubahan Jumlah NIB (%)</th>
                            <th>Jumlah Investasi</th>
                            <th>Perubahan Investasi (%)</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->tahun }}</td>
                            <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                            @if ($bulan === '')
                                <td>{{ $row->total_nib }}</td>
                                <td>{{ $row->nib_change }}%</td>
                                <td>Rp.{{ number_format($row->jumlah_investasi, 2, ',', '.') }}</td>
                                <td>{{ $row->investasi_change }}%</td>
                            @endif
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
