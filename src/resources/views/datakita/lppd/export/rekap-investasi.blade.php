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
        th { border: 1px solid #000; padding: 10px; text-align: center; vertical-align: middle; font-weight: bold; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; text-align: center; background-color: #f2f2f2; }
        .angka { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }
    </style>
</head>
<body>

    <table>
        <tr><td colspan="5" class="judul" style="border:none;">KOP SURAT</td></tr>
        <tr><td colspan="5" class="dinas" style="border:none;">DINAS PENANAMAN MODAL KAB/KOTA SEMARANG</td></tr>
        <tr><td colspan="5" style="border:none; border-bottom: 2px solid #000; height: 5px;"></td></tr>
        <tr><td colspan="5" style="border:none; height: 10px;"></td></tr>
        <tr><td colspan="5" class="sub-judul" style="border:none;">REKAPITULASI INVESTASI TAHUN {{ $tahunN }} (TAHUN PELAPORAN) DAN TAHUN {{ $tahunN1 }} (TAHUN SEBELUMNYA)</td></tr>
        <tr><td colspan="5" class="sub-judul" style="border:none;">KAB/KOTA SEMARANG</td></tr>
        <tr><td colspan="5" class="sub-judul" style="border:none;">TAHUN {{ $tahunN }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50">No</th>
                <th width="350">Jenis Penanaman Modal</th>
                <th width="200">Nilai Investasi<br>Tahun N (Rp)</th>
                <th width="200">Nilai Investasi<br>Tahun N-1 (Rp)</th>
                <th width="100">Ket</th>
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
            <tr>
                <td class="center">1.</td>
                <td>Penanaman Modal Asing (PMA)</td>
                <td class="angka">{{ $pmaN }}</td>
                <td class="angka">{{ $pmaN1 }}</td>
                <td></td>
            </tr>
            <tr>
                <td class="center">2.</td>
                <td>Penanaman Modal Dalam Negeri (PMDN)</td>
                <td class="angka">{{ $pmdnN }}</td>
                <td class="angka">{{ $pmdnN1 }}</td>
                <td></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="bold">
                <td colspan="2" class="center" style="letter-spacing: 2px;">J u m l a h</td>
                <td class="angka bold">{{ $totalN }}</td>
                <td class="angka bold">{{ $totalN1 }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
