<!DOCTYPE html>
<html>

<head>
    <title>Export Data Rekap PBG</title>
    <style type="text/css">
        table { border-collapse: collapse; width: 100%; }
        table th, table td { border: 1px solid #3c3c3c; padding: 5px; }
        .text-center { text-align: center; }
    </style>
</head>

<body>

    <div style="text-align: center;">
        <h2>HASIL EKSPOR REKAP PBG</h2>
        <p>Filter Berdasarkan: <strong>{{ str_replace('_', ' ', $filterBerdasarkan) }}</strong> |
            Tahun: {{ $tahun ?: 'Semua' }} |
            Bulan: {{ $bulan ?: 'Semua' }}</p>
    </div>

    <table>
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>No</th>
                <th>Nama Pemilik</th>
                <th>No Registrasi</th>
                <th>No Dokumen PBG</th>
                <th>Jenis Permohonan</th>
                <th>Tanggal Registrasi</th>
                <th>Tanggal Dokumen PBG</th>
                <th>Tanggal Pengambilan SK</th>
                <th>Nama Pengambil SK</th>
            </tr>
        </thead>
        <tbody>
            @if ($rows->isNotEmpty())
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_pemilik }}</td>
                        <td>{{ $row->no_registrasi }}</td>
                        <td>{{ $row->no_dokumen_pbg }}</td>
                        <td>{{ $row->jenis_permohonan }}</td>
                        <td>{{ $row->tgl_registrasi }}</td>
                        <td>{{ $row->tgl_dokumen_pbg }}</td>
                        <td>{{ $row->tgl_pengambilan_sk }}</td>
                        <td>{{ $row->nama_pengambil_sk }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="9" style="text-align: center;">Tidak ada data ditemukan</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
