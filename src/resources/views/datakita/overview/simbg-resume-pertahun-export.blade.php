<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        .judul {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
        }

        th {
            background-color: #4f81bd;
            color: #ffffff;
            border: 1px solid #000;
            padding: 10px;
        }

        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .bg-gray {
            background-color: #d9d9d9;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td colspan="3" class="judul" style="border:none;">DATA PERIZINAN BANGUNAN GEDUNG (SIMBG)</td>
        </tr>
        <tr>
            <td colspan="3" style="border:none;"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="100">TAHUN</th>
                <th width="350">STATUS PERIZINAN</th>
                <th width="120">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['tahun'] }}</td>
                    <td style="text-align: left;">{{ $row['status_izin'] }}</td>
                    <td>{{ $row['jumlah'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray">
                <td colspan="2">TOTAL KESELURUHAN</td>
                <td>{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
