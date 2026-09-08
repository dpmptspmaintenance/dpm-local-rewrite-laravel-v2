<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th { background-color: #4f81bd; color: #ffffff; border: 1px solid #000000; padding: 10px; font-weight: bold; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000000; padding: 5px; vertical-align: middle; font-size: 11pt; }
        .center { text-align: center; }
        .left { text-align: left; }
        .right { text-align: right; }
        .bg-gray { background-color: #d9d9d9; font-weight: bold; }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="4" class="judul" style="border:none;">
                REKAPITULASI INVESTASI PER KECAMATAN<br>
                PERIODE: {{ $bulan == '' ? "TAHUN $tahun (SEMUA BULAN)" : strtoupper($months[$bulan]) . " $tahun" }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50">NO</th>
                <th width="250">KECAMATAN</th>
                <th width="150">JUMLAH PROYEK</th>
                <th width="200">NILAI INVESTASI (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="left">{{ $row->kecamatan_usaha }}</td>
                    <td class="right">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">Data tidak ditemukan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-gray">
                <td colspan="2" class="center">TOTAL KESELURUHAN</td>
                <td class="right">{{ number_format($grandProyek, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($grandInvestasi, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
