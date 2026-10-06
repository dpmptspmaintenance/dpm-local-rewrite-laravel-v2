<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Permohonan Perbulan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{font-family:sans-serif;font-size:12px}</style>
</head>
<body onload="window.print()">
    <h3 class="text-center">Rekap Permohonan Perbaikan Barang</h3>
    <p class="text-center">Bulan ke-{{ $bulan }}</p>
    <table class="table table-bordered table-sm">
        <thead>
            <tr><th>No</th><th>Nama Barang</th><th>Merk/Tipe</th><th>Kerusakan</th><th>Biaya</th></tr>
        </thead>
        <tbody>
            @foreach ($list as $i => $d)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $d->barang?->nama_barang }}</td>
                    <td>{{ $d->barang?->merk_type }}</td>
                    <td>{{ $d->uraian_kerusakan }}</td>
                    <td>Rp {{ number_format((float) $d->biaya, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
