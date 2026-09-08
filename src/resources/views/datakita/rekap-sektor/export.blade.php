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
        <h2>Hasil Pencarian Rekap Data Proyek Persektor</h2>
        @if ($rows->count() > 0)
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun</th>
                        <th>Bulan</th>
                        <th>Sektor Pembina &amp; KBLI</th>
                        @if ($filters['kecamatan'] !== '')
                            <th>Kecamatan</th>
                        @endif
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->tahun }}</td>
                            <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                            <td>
                                <strong>{{ $row->sektor_pembina }}</strong>
                                <div>{!! $row->kbli_detail !!}</div>
                            </td>
                            @if ($filters['kecamatan'] !== '')
                                <td>{{ $row->kecamatan }}</td>
                            @endif
                            <td>{{ $row->jumlah_sektor_pembina }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-center">Tidak Ada Data Yang Ditemukan</p>
        @endif
    </div>
</body>

</html>
