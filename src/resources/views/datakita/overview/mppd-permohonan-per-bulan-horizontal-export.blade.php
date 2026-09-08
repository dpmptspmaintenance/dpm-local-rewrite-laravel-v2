<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th { background-color: #4f81bd; color: #ffffff; border: 1px solid #000000; padding: 8px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000000; padding: 5px; text-align: center; vertical-align: middle; }
        .text-left { text-align: left; padding-left: 8px; }
        .bold { font-weight: bold; }
        .bg-gray { background-color: #d9d9d9; font-weight: bold; }
    </style>
</head>

<body>

    @php $totalKolom = 2 + (count($bulanAktif) * 2) + 3; @endphp

    <table>
        <tr><td colspan="{{ $totalKolom }}" class="judul" style="border:none;">PERMOHONAN IZIN TENAGA KESEHATAN PER BULAN</td></tr>
        <tr><td colspan="{{ $totalKolom }}" class="judul" style="border:none; font-size:12pt;">TAHUN: {{ $tahun }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="40">No</th>
                <th rowspan="2" width="220">Jabatan / Profesi</th>
                @foreach ($bulanAktif as $b)
                    <th colspan="2">{{ $listBulanNama[$b] }}</th>
                @endforeach
                <th colspan="3">Total Keseluruhan</th>
            </tr>
            <tr>
                @foreach ($bulanAktif as $b)
                    <th width="80">Ditolak</th>
                    <th width="80">Diterbitkan</th>
                @endforeach
                <th width="90">Ditolak</th>
                <th width="90">Diterbitkan</th>
                <th width="100">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @if (!empty($dataMatrix))
                @foreach ($dataMatrix as $jabatan => $row)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td class="text-left bold">{{ $jabatan }}</td>
                        @foreach ($bulanAktif as $b)
                            <td>{{ $row['bulan'][$b]['tolak'] == 0 ? '-' : $row['bulan'][$b]['tolak'] }}</td>
                            <td>{{ $row['bulan'][$b]['terbit'] == 0 ? '-' : $row['bulan'][$b]['terbit'] }}</td>
                        @endforeach
                        <td>{{ $row['total_tolak'] == 0 ? '-' : $row['total_tolak'] }}</td>
                        <td>{{ $row['total_terbit'] == 0 ? '-' : $row['total_terbit'] }}</td>
                        <td class="bold bg-gray">{{ $row['grand_total'] }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="{{ $totalKolom }}">Data tidak ditemukan pada periode ini.</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bg-gray">
                <td colspan="2" style="text-align: right;">TOTAL KESELURUHAN</td>
                @foreach ($bulanAktif as $b)
                    <td>{{ $totalPerBulan[$b]['tolak'] }}</td>
                    <td>{{ $totalPerBulan[$b]['terbit'] }}</td>
                @endforeach
                <td>{{ $totTolakGlobal }}</td>
                <td>{{ $totTerbitGlobal }}</td>
                <td>{{ $grandTotalGlobal }}</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
