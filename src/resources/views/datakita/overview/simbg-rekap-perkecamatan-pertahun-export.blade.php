<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        .th-header { background-color: #d9d9d9; border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold; }
        .kecamatan-title { background-color: #ffff00; font-weight: bold; text-align: left; padding: 5px; border: 1px solid #000; font-size: 11pt; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="4" class="judul" style="border:none;">REKAPITULASI PER KECAMATAN</td>
        </tr>
        <tr>
            <td colspan="4" class="judul" style="border:none; font-size:12pt;">TAHUN: {{ $tahun }} | KOTA SEMARANG</td>
        </tr>
    </table>

    <table>
        @if ($rows->isEmpty())
            <tr><td colspan='4'>Data tidak ditemukan.</td></tr>
        @else
            @php $currentKecamatan = ''; $no = 1; @endphp
            @foreach ($rows as $row)
                @if ($row->kecamatan_bangunan != $currentKecamatan)
                    @php $currentKecamatan = $row->kecamatan_bangunan; @endphp
                    @if ($no > 1)
                        <tr><td colspan='4' style='border:none; height:15px;'></td></tr>
                    @endif
                    <tr>
                        <td colspan='4' class='kecamatan-title'>KECAMATAN: {{ strtoupper($row->kecamatan_bangunan) }}</td>
                    </tr>
                    <tr>
                        <th class='th-header' width='50'>No</th>
                        <th class='th-header' width='250'>Status</th>
                        <th class='th-header' width='300'>Fungsi Bangunan</th>
                        <th class='th-header' width='100'>Jumlah</th>
                    </tr>
                @endif
                @php $fungsi = !empty($row->fungsi_bangunan) ? $row->fungsi_bangunan : '-'; @endphp
                <tr>
                    <td class="center">{{ $no++ }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $fungsi }}</td>
                    <td class="center bold">{{ $row->jumlah }}</td>
                </tr>
            @endforeach
        @endif

        <tr>
            <td colspan='4' style='border:none; height:10px;'></td>
        </tr>
        <tr style="background-color: #4f81bd; color: white;">
            <td colspan="3" style="text-align: right; font-weight: bold; border: 1px solid #000;">TOTAL KESELURUHAN</td>
            <td class="center bold" style="border: 1px solid #000;">{{ $total }}</td>
        </tr>
    </table>

</body>

</html>
