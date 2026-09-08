<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th colspan="8" style="background:#1565C0;color:#fff;font-size:13pt;">
                    Data Investasi Detail KBLI (2 Digit): {{ $kecamatan == '' ? 'Semua Kecamatan (Kota Semarang)' : 'Kecamatan ' . $kecamatan }}
                </th>
            </tr>
            <tr>
                <th colspan="8" style="background:#E3F2FD;">
                    Periode: {{ ($bulan == '' ? 'Tahun' : $months[$bulan]) . " $tahun" }} &nbsp;|&nbsp; Berdasarkan Tanggal Terbit OSS
                </th>
            </tr>
            <tr style="background:#BBDEFB;font-weight:bold;text-align:center;">
                <th>No</th>
                <th>Kode Kat.</th>
                <th>Nama Kategori KBLI</th>
                <th>Kode KBLI</th>
                <th>Judul KBLI</th>
                <th>Jumlah Proyek</th>
                <th>Jumlah Investasi (Rp)</th>
                <th>Persentase (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $row)
                @php
                    $persentase = ($grandInvestasi > 0) ? ($row->jumlah_investasi / $grandInvestasi) * 100 : 0;
                    $katKode = $row->kat_kode;
                    $katNama = $row->kat_nama;
                    $isFirst = ($firstIdxMap[$katKode] === $i);
                    $span = $rowspanMap[$katKode];
                    $katOrd = ord($katKode) - ord('A');
                    $rowBg = ($katOrd % 2 === 0) ? '#FFFFFF' : '#F5F5F5';
                @endphp
                <tr style="background:{{ $rowBg }};">
                    <td align="center">{{ $loop->iteration }}</td>

                    @if ($isFirst)
                        <td rowspan="{{ $span }}" align="center" valign="middle"
                            style="background:#1565C0;color:#fff;font-weight:bold;font-size:12pt;letter-spacing:1px;">
                            {{ $katKode }}
                        </td>
                        <td rowspan="{{ $span }}" valign="middle"
                            style="background:#E3F2FD;font-weight:600;font-size:9pt;">
                            {{ $katNama }}
                        </td>
                    @endif

                    <td align="center"><b>{{ $row->kode_kbli }}</b></td>
                    <td>{{ $row->judul_kbli }}</td>
                    <td align="right">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                    <td align="right">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                    <td align="right">{{ \App\Http\Controllers\DataKita\OverviewOssController::formatPersen($persentase) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" align="center">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background:#BBDEFB;font-weight:bold;">
                <td colspan="5" align="left">
                    <b>TOTAL INVESTASI ({{ $kecamatan == '' ? 'KOTA SEMARANG' : strtoupper($kecamatan) }})</b>
                </td>
                <td align="right"><b>{{ number_format($grandProyek, 0, ',', '.') }}</b></td>
                <td align="right"><b>{{ number_format($grandInvestasi, 0, ',', '.') }}</b></td>
                <td align="right"><b>100,00%</b></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
