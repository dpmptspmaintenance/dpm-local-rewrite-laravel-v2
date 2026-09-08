<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Export Data</title>
    <style>
        body { font-family: sans-serif; }
        table { border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>

<body>
    <h2>Hasil Pencarian dari Rekap List Jenis Izin Dan Nama Dokumen</h2>

    @if ($hasRows)
        @foreach ($groups as $resiko => $perizinanGroups)
            @foreach ($perizinanGroups as $perizinan => $rowsForGroup)
                <div style="margin-top: 25px; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                    {{ $perizinan }} - {{ $resikoNama[$resiko] ?? $resiko }}
                </div>
                <table border="1" cellpadding="5" cellspacing="0">
                    <thead>
                        <tr style="background-color: #f2f2f2; font-weight: bold;">
                            <th>No</th>
                            <th>Tahun</th>
                            <th>Bulan</th>
                            <th>Status Respon (Nama Dokumen)</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rowsForGroup as $row)
                            <tr>
                                <td style="text-align: center;">{{ $loop->iteration }}</td>
                                <td style="text-align: center;">{{ $row->tahun }}</td>
                                <td>{{ $bulanNama[$row->bulan] ?? $row->bulan }}</td>
                                <td>{{ $row->uraian_status_respon }}</td>
                                <td style="text-align: right;"><strong>{{ number_format($row->jumlah_izin) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endforeach
    @else
        <p>Tidak ada data yang ditemukan dari Proyek.</p>
    @endif
</body>

</html>
