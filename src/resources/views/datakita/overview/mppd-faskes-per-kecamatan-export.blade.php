<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 8px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; text-align: center; }
        .text-left { text-align: left; padding-left: 5px; }
        .bold { font-weight: bold; }
    </style>
</head>

<body>

    <table>
        <tr><td colspan="9" class="judul" style="border:none;">SEBARAN PERMOHONAN SIP PER FASKES</td></tr>
        <tr><td colspan="9" class="judul" style="border:none; font-size:12pt;">{{ $txtLokasi }} | {{ $txtPeriode }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="50">No</th>
                <th rowspan="2" width="200">Kelurahan</th>
                <th rowspan="2" width="250">Kategori</th>
                <th colspan="4">Status Permohonan</th>
                <th rowspan="2" width="80">Total</th>
                <th rowspan="2" width="80">Persentase</th>
            </tr>
            <tr>
                <th width="80">Dibatalkan</th>
                <th width="80">Ditolak</th>
                <th width="80">Verifikasi</th>
                <th width="80">SK Terbit</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @if ($rows->isNotEmpty())
                @foreach ($rows as $row)
                    @php
                        $persen = ($grandTotal > 0) ? ($row->total_per_row / $grandTotal) * 100 : 0;
                        $kelurahanClean = ucwords(strtolower($row->kelurahan));
                        $kategoriClean = ucwords(strtolower($row->kategori));
                    @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td class="text-left">{{ $kelurahanClean }}</td>
                        <td class="text-left">{{ $kategoriClean }}</td>
                        <td>{{ $row->stat_batal == 0 ? '-' : $row->stat_batal }}</td>
                        <td>{{ $row->stat_tolak == 0 ? '-' : $row->stat_tolak }}</td>
                        <td>{{ $row->stat_verif == 0 ? '-' : $row->stat_verif }}</td>
                        <td>{{ $row->stat_terbit == 0 ? '-' : $row->stat_terbit }}</td>
                        <td class="bold">{{ $row->total_per_row }}</td>
                        <td>{{ round($persen) }}%</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="9">Data Kosong</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bold" style="background-color: #f2f2f2;">
                <td colspan="3" style="text-align: right;">Total Permohonan</td>
                <td>{{ $totBatal }}</td>
                <td>{{ $totTolak }}</td>
                <td>{{ $totVerif }}</td>
                <td>{{ $totTerbit }}</td>
                <td>{{ $grandTotal }}</td>
                <td style="background-color: #808080;"></td>
            </tr>
            <tr class="bold" style="background-color: #f2f2f2;">
                <td colspan="3" style="text-align: right;">Persentase</td>
                <td>{{ ($grandTotal > 0) ? round(($totBatal / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totTolak / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totVerif / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totTerbit / $grandTotal) * 100) : 0 }}%</td>
                <td colspan="2" style="background-color: #808080;"></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
