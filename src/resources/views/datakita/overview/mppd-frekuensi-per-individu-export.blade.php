<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 10px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; text-align: center; }
        .bold { font-weight: bold; }
        .gray { background-color: #f2f2f2; }
    </style>
</head>

<body>

    <table>
        <tr><td colspan="9" class="judul" style="border:none;">FREKUENSI PENGAJUAN PERMOHONAN OLEH INDIVIDU</td></tr>
        <tr><td colspan="9" class="judul" style="border:none; font-size:12pt;">TAHUN: {{ $tahun }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="100">No Jumlah (n kali)</th>
                <th colspan="2">Pengajuan</th>
                <th colspan="4">Status Permohonan</th>
                <th colspan="2">Total & Persentase</th>
            </tr>
            <tr>
                <th width="100">Jumlah Orang</th>
                <th width="80">% Orang</th>
                <th width="100">Dibatalkan</th>
                <th width="100">Ditolak</th>
                <th width="100">Verifikasi</th>
                <th width="100">SK Diterbitkan</th>
                <th width="100">Total</th>
                <th width="80">%</th>
            </tr>
        </thead>
        <tbody>
            @if ($rows->isNotEmpty())
                @foreach ($rows as $row)
                    @php
                        $jmlOrang = $row->jumlah_orang;
                        $percOrang = ($totalOrangGlobal > 0) ? ($jmlOrang / $totalOrangGlobal) * 100 : 0;
                        $rowTotalApp = $row->tot_batal + $row->tot_tolak + $row->tot_verif + $row->tot_terbit;
                        $percApp = ($totalAplikasiGlobal > 0) ? ($rowTotalApp / $totalAplikasiGlobal) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="bold">{{ $row->jumlah_kali_mengajukan }}</td>
                        <td>{{ $jmlOrang }}</td>
                        <td>{{ round($percOrang, 2) }}%</td>
                        <td>{{ $row->tot_batal }}</td>
                        <td>{{ $row->tot_tolak }}</td>
                        <td>{{ $row->tot_verif }}</td>
                        <td>{{ $row->tot_terbit }}</td>
                        <td class="bold">{{ $rowTotalApp }}</td>
                        <td>{{ round($percApp, 2) }}%</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="9">Data Kosong</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="gray bold">
                <td>TOTAL</td>
                <td>{{ $totalOrangGlobal }}</td>
                <td>100%</td>
                <td>{{ $footerBatal }}</td>
                <td>{{ $footerTolak }}</td>
                <td>{{ $footerVerif }}</td>
                <td>{{ $footerTerbit }}</td>
                <td>{{ $totalAplikasiGlobal }}</td>
                <td style="background-color: #808080;"></td>
            </tr>
            <tr class="gray bold">
                <td colspan="3" style="text-align: right;">Persentase Status:</td>
                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerBatal / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerTolak / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerVerif / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerTerbit / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                <td colspan="2" style="background-color: #808080;"></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
