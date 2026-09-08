<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 10px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000; padding: 5px; text-align: center; vertical-align: middle; }
        .text-left { text-align: left; padding-left: 10px; }
        .bold { font-weight: bold; }
        .gray { background-color: #f2f2f2; }
    </style>
</head>

<body>

    <table>
        <tr><td colspan="8" class="judul" style="border:none;">STATUS PERMOHONAN SIP PER BULAN</td></tr>
        <tr><td colspan="8" class="judul" style="border:none; font-size:12pt;">TAHUN: {{ $tahun }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="50">No</th>
                <th rowspan="2" width="150">Bulan</th>
                <th colspan="4">Jumlah Status Permohonan</th>
                <th colspan="2">Total & Persentase Permohonan</th>
            </tr>
            <tr>
                <th width="100">Dibatalkan</th>
                <th width="100">Ditolak</th>
                <th width="100">Verifikasi DPMPTSP</th>
                <th width="100">SK Diterbitkan</th>
                <th width="100">Total</th>
                <th width="80">%</th>
            </tr>
        </thead>
        <tbody>
            @php $totalBatal = 0; $totalTolak = 0; $totalVerif = 0; $totalTerbit = 0; @endphp
            @for ($m = 1; $m <= 12; $m++)
                @php
                    $d = $dataBulan[$m];
                    $rowTotal = $d['batal'] + $d['tolak'] + $d['verif'] + $d['terbit'];
                    $perc = ($grandTotal > 0) ? ($rowTotal / $grandTotal) * 100 : 0;
                    $totalBatal += $d['batal']; $totalTolak += $d['tolak'];
                    $totalVerif += $d['verif']; $totalTerbit += $d['terbit'];
                @endphp
                <tr>
                    <td>{{ $m }}</td>
                    <td class="text-left">{{ $namaBulan[$m] }}</td>
                    <td>{{ $d['batal'] == 0 ? '-' : $d['batal'] }}</td>
                    <td>{{ $d['tolak'] == 0 ? '-' : $d['tolak'] }}</td>
                    <td>{{ $d['verif'] == 0 ? '-' : $d['verif'] }}</td>
                    <td>{{ $d['terbit'] == 0 ? '-' : $d['terbit'] }}</td>
                    <td class="bold">{{ $rowTotal == 0 ? '-' : $rowTotal }}</td>
                    <td>{{ $rowTotal == 0 ? '-' : round($perc).'%' }}</td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr class="gray bold">
                <td colspan="2" rowspan="2" style="vertical-align: middle;">Total & Persentase</td>
                <td>{{ $totalBatal }}</td>
                <td>{{ $totalTolak }}</td>
                <td>{{ $totalVerif }}</td>
                <td>{{ $totalTerbit }}</td>
                <td rowspan="2" style="font-size: 14pt; vertical-align: middle;">{{ $grandTotal }}</td>
                <td rowspan="2" style="background-color: #808080;"></td>
            </tr>
            <tr class="gray bold">
                <td>{{ ($grandTotal > 0) ? round(($totalBatal / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totalTolak / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totalVerif / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totalTerbit / $grandTotal) * 100) : 0 }}%</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
