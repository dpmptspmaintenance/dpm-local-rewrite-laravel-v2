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
        .bg-blue { background-color: #0d6efd; font-weight: bold; color: #ffffff; }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="5" class="judul" style="border:none;">
                REKAPITULASI INVESTASI PER KLASIFIKASI KBLI<br>
                PERIODE: {{ $bulan == '' ? "TAHUN $tahun (SEMUA BULAN)" : strtoupper($months[$bulan]) . " $tahun" }}<br>
                {{ $klasifikasi == '' ? 'SEMUA KLASIFIKASI' : strtoupper($klasifikasi) }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="80">KODE KLAS</th>
                <th width="300">NAMA KLASIFIKASI</th>
                <th width="150">JUMLAH PROYEK</th>
                <th width="200">JUMLAH INVESTASI (Rp)</th>
                <th width="120">PERSENTASE (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $parts = explode('. ', $row->kategori_kbli, 2);
                    $kode = $parts[0] ?? '?';
                    $nama = $parts[1] ?? $row->kategori_kbli;
                    $persentase = ($totalSemuaInvestasi > 0) ? ($row->jumlah_investasi / $totalSemuaInvestasi) * 100 : 0;
                @endphp
                <tr>
                    <td class="center"><b>{{ $kode }}</b></td>
                    <td class="left">{{ $nama }}</td>
                    <td class="right">{{ number_format($row->jumlah_proyek, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($row->jumlah_investasi, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($persentase, 2, ',', '.') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center">Data tidak ditemukan pada periode dan filter ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-blue">
                <td colspan="2" class="center" style="color:#ffffff;">TOTAL DATA YANG DITAMPILKAN</td>
                <td class="right" style="color:#ffffff;">{{ number_format($grandProyek, 0, ',', '.') }}</td>
                <td class="right" style="color:#ffffff;">{{ number_format($grandInvestasi, 0, ',', '.') }}</td>
                @php $persentaseFooter = ($totalSemuaInvestasi > 0) ? ($grandInvestasi / $totalSemuaInvestasi) * 100 : 0; @endphp
                <td class="right" style="color:#ffffff;">{{ number_format($persentaseFooter, 2, ',', '.') }}%</td>
            </tr>
        </tfoot>
    </table>

</body>

</html>
