<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 10px; text-align: center; }
        td { border: 1px solid #000; padding: 5px; text-align: center; }
        .text-left { text-align: left; padding-left: 10px; }
        .bold { font-weight: bold; }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="5" class="judul" style="border:none;">REKAPITULASI SLF & PBG</td>
        </tr>
        <tr>
            <td colspan="5" class="judul" style="border:none; font-size:12pt;">TAHUN: {{ $tahun }} | KOTA SEMARANG</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50">No</th>
                <th width="150">Bulan</th>
                <th width="200">SLF (Status like %slf%)</th>
                <th width="200">PBG (Status like %pbg%)</th>
                <th width="100">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @for ($m = 1; $m <= 12; $m++)
                @php
                    $slf = $dataBulan[$m];
                    $pbg = $dataBulanPbg[$m];
                    $sum = $slf + $pbg;
                @endphp
                <tr>
                    <td>{{ $m }}</td>
                    <td class="text-left">{{ $namaBulan[$m] }}</td>
                    <td>{{ $slf > 0 ? $slf : '-' }}</td>
                    <td>{{ $pbg > 0 ? $pbg : '-' }}</td>
                    <td class="bold">{{ $sum > 0 ? $sum : '-' }}</td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr class="bold" style="background-color: #f2f2f2;">
                <td colspan="2" class="text-left">Total Tahunan</td>
                <td>{{ array_sum($stats['slf']) }}</td>
                <td>{{ array_sum($stats['pbg']) }}</td>
                <td>{{ array_sum($stats['total']) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-left">Minimal</td>
                <td>{{ min($stats['slf']) }}</td>
                <td>{{ min($stats['pbg']) }}</td>
                <td>{{ min($stats['total']) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-left">Maksimal</td>
                <td>{{ max($stats['slf']) }}</td>
                <td>{{ max($stats['pbg']) }}</td>
                <td>{{ max($stats['total']) }}</td>
            </tr>
            <tr>
                <td colspan="2" class="text-left">Rata-rata/Bulan</td>
                <td>{{ round(array_sum($stats['slf']) / 12) }}</td>
                <td>{{ round(array_sum($stats['pbg']) / 12) }}</td>
                <td>{{ round(array_sum($stats['total']) / 12) }}</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
