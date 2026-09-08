<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Times New Roman', Times, serif; }
        .judul { font-weight: bold; text-align: center; }
        .sub-judul { font-weight: bold; text-align: center; font-size: 12pt; }
        .dinas { font-weight: bold; text-align: center; color: #b5651d; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 10px; text-align: center; vertical-align: middle; font-weight: bold; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; text-align: center; background-color: #f2f2f2; }
        .angka { mso-number-format:"_-Rp\ * \#\,\#\#0_-\;\\-Rp\ * \#\,\#\#0_-\;_-Rp\ * \x22-\x22_-\;_-\@_-"; text-align: right; font-weight: bold; }
    </style>
</head>
<body>

    <table>
        <tr><td colspan="5" class="judul" style="border:none;">KOP SURAT</td></tr>
        <tr><td colspan="5" class="dinas" style="border:none;">DINAS PENANAMAN MODAL KAB/KOTA SEMARANG</td></tr>
        <tr><td colspan="5" style="border:none; border-bottom: 2px solid #000; height: 5px;"></td></tr>
        <tr><td colspan="5" style="border:none; height: 10px;"></td></tr>
        <tr><td colspan="5" class="sub-judul" style="border:none;">REALISASI INVESTASI PER KECAMATAN</td></tr>
        <tr><td colspan="5" class="sub-judul" style="border:none;">TAHUN {{ $tahunPilih }} (TAHUN PELAPORAN)</td></tr>
        <tr><td colspan="5" class="sub-judul" style="border:none;">KAB/KOTA SEMARANG</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50" rowspan="2">No</th>
                <th width="300" rowspan="2">Kecamatan</th>
                <th colspan="2">Jenis Penanaman Modal (Rp)</th>
                <th width="200" rowspan="2">Total Investasi (Rp)</th>
            </tr>
            <tr>
                <th width="200">PMA (Asing)</th>
                <th width="200">PMDN (Dalam Negeri)</th>
            </tr>
            <tr class="italic">
                <td>(1)</td>
                <td>(2)</td>
                <td>(3)</td>
                <td>(4)</td>
                <td>(5)</td>
            </tr>
        </thead>
        <tbody>
            @forelse ($dataLaporan as $row)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="bold">{{ ucwords(strtolower($row->nama_kecamatan)) }}</td>
                    <td class="angka">{{ $row->pma }}</td>
                    <td class="angka">{{ $row->pmdn }}</td>
                    <td class="angka">{{ $row->total_investasi }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Data Kosong</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bold" style="background-color: #f2f2f2;">
                <td colspan="2" class="center" style="letter-spacing: 2px;">TOTAL KESELURUHAN</td>
                <td class="angka">{{ $totPma }}</td>
                <td class="angka">{{ $totPmdn }}</td>
                <td class="angka">{{ $grandTotal }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
