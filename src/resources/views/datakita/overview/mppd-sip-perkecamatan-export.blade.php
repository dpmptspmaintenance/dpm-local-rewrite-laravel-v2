<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 10px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .text-left { text-align: left; padding-left: 10px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
    </style>
</head>

<body>

    <table>
        <tr><td colspan="4" class="judul" style="border:none;">PERMOHONAN SIP PER KECAMATAN</td></tr>
        <tr><td colspan="4" class="judul" style="border:none; font-size:12pt;">TAHUN: {{ $tahun }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50">No</th>
                <th width="300">Kecamatan Fasilitas Kesehatan</th>
                <th width="150">Jumlah Permohonan</th>
                <th width="100">Presentase</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @if (count($rows) > 0)
                @foreach ($rows as $row)
                    @php $persen = ($total > 0) ? ($row['jumlah'] / $total) * 100 : 0; @endphp
                    <tr>
                        <td class="center">{{ $no++ }}</td>
                        <td class="text-left bold">{{ $row['kecamatan'] }}</td>
                        <td class="center">{{ $row['jumlah'] }}</td>
                        <td class="center">{{ round($persen) }}%</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="4" class="center">Data Kosong</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bold" style="background-color: #f2f2f2;">
                <td colspan="2" class="center" style="font-size: 11pt;">TOTAL</td>
                <td class="center" style="font-size: 11pt;">{{ $total }}</td>
                <td class="center" style="background-color: #d9d9d9;"></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
