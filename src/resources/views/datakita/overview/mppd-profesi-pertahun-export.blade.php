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
        .text-left { text-align: left; padding-left: 10px; }
        .bold { font-weight: bold; }
        .gray { background-color: #f2f2f2; }
    </style>
</head>

<body>

    <table>
        <tr><td colspan="8" class="judul" style="border:none;">PROFIL KATEGORI TENAGA KESEHATAN (PROFESI)</td></tr>
        <tr><td colspan="8" class="judul" style="border:none; font-size:12pt;">{{ $txtPeriode }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="50">No</th>
                <th rowspan="2" width="300">Kategori Tenaga Kesehatan</th>
                <th colspan="4">Jumlah Status Permohonan</th>
                <th colspan="2">Total & Persentase</th>
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
            @php $no = 1; @endphp
            @if ($rows->isNotEmpty())
                @foreach ($rows as $row)
                    @php
                        $namaProfesi = !empty($row->profesi) ? $row->profesi : '(Tidak Disebutkan)';
                        $persen = ($grandTotal > 0) ? ($row->total_per_profesi / $grandTotal) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td class="text-left bold">{{ $namaProfesi }}</td>
                        <td>{{ $row->jml_batal == 0 ? '-' : $row->jml_batal }}</td>
                        <td>{{ $row->jml_tolak == 0 ? '-' : $row->jml_tolak }}</td>
                        <td>{{ $row->jml_verif == 0 ? '-' : $row->jml_verif }}</td>
                        <td>{{ $row->jml_terbit == 0 ? '-' : $row->jml_terbit }}</td>
                        <td class="bold">{{ $row->total_per_profesi }}</td>
                        <td>{{ round($persen) }}%</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="8">Data Kosong</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="gray bold">
                <td colspan="2" style="text-align: right;">Total & Persentase</td>
                <td>{{ $totalBatal }}</td>
                <td>{{ $totalTolak }}</td>
                <td>{{ $totalVerif }}</td>
                <td>{{ $totalTerbit }}</td>
                <td>{{ $grandTotal }}</td>
                <td style="background-color: #808080;"></td>
            </tr>
            <tr class="gray bold">
                <td colspan="2"></td>
                <td>{{ ($grandTotal > 0) ? round(($totalBatal / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totalTolak / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totalVerif / $grandTotal) * 100) : 0 }}%</td>
                <td>{{ ($grandTotal > 0) ? round(($totalTerbit / $grandTotal) * 100) : 0 }}%</td>
                <td colspan="2" style="background-color: #808080;"></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
