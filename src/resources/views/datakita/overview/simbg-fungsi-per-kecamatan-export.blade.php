<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        .sub-judul { font-size: 12pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 10px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="4" class="judul" style="border:none;">REKAPITULASI FUNGSI & SUB FUNGSI BANGUNAN</td>
        </tr>
        <tr>
            <td colspan="4" class="sub-judul" style="border:none;">{{ $judulAtas }}</td>
        </tr>
        <tr>
            <td colspan="4" class="sub-judul" style="border:none;">TAHUN REGISTRASI: {{ $tahun }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50">NO</th>
                <th width="200">FUNGSI BANGUNAN</th>
                <th width="350">SUB FUNGSI BANGUNAN</th>
                <th width="100">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @if ($rows->isNotEmpty())
                @foreach ($rows as $row)
                    <tr>
                        <td class="center">{{ $no++ }}</td>
                        <td class="bold">{{ $row->fungsi_bangunan }}</td>
                        <td>{{ $row->sub_fungsi_bangunan }}</td>
                        <td class="center">{{ $row->jumlah }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" class="center">Data tidak ditemukan.</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="text-align: right;">TOTAL IZIN TERBIT</th>
                <th style="text-align: center;">{{ $total }}</th>
            </tr>
        </tfoot>
    </table>

</body>

</html>
