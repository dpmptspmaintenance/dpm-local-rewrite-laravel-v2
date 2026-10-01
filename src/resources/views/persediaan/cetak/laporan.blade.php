@php
    $hasItems = collect($blocks)->contains(fn ($b) => $b['type'] === 'item');
    $rp = fn ($v) => number_format($v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Persediaan - {{ $periode }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h3,
        .header h4 {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: right;
        }

        th {
            text-align: center;
            background-color: #f2f2f2;
        }

        td.text-left {
            text-align: left;
        }

        td.text-center {
            text-align: center;
        }

        .rek-header {
            background-color: #eaeaea;
            font-weight: bold;
            text-align: left;
        }

        .sub-total {
            font-weight: bold;
            font-style: italic;
            background-color: #fff7e0;
        }

        .grand-total {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .no-print {
            text-align: right;
            margin-bottom: 10px;
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }

            body {
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold;">Cetak Dokumen</button>
    </div>

    <div class="header">
        <h3>LAPORAN PERSEDIAAN</h3>
        <h4>Periode: {{ $periode }}</h4>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Barang</th>
                <th colspan="4">Saldo Awal</th>
                <th colspan="4">Mutasi Masuk</th>
                <th colspan="4">Mutasi Keluar</th>
                <th colspan="2">Saldo Akhir</th>
            </tr>
            <tr>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Total (Rp)</th>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Total (Rp)</th>
                <th>Stok</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Total (Rp)</th>
                <th>Stok</th>
                <th>Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @if (! $hasItems)
                <tr>
                    <td colspan="16" class="text-center">Tidak ada data pada periode ini.</td>
                </tr>
            @endif

            @foreach ($blocks as $b)
                @if ($b['type'] === 'rek')
                    <tr class="rek-header">
                        <td colspan="16">{{ $b['kode'] }} - {{ $b['nama'] }}</td>
                    </tr>
                @elseif ($b['type'] === 'item')
                    @php $it = $b['item']; $saldo = $b['saldo']; @endphp
                    <tr>
                        <td class="text-center">{{ $b['no'] }}</td>
                        <td class="text-left">{{ $it->nama_barang }}</td>
                        <td class="text-center">{{ $it->qty_awal ?: '-' }}</td>
                        <td class="text-center">{{ $it->nama_satuan }}</td>
                        <td>{{ $rp($it->harga_satuan) }}</td>
                        <td>{{ $rp($saldo['awal']) }}</td>
                        <td class="text-center">{{ $it->qty_masuk ?: '-' }}</td>
                        <td class="text-center">{{ $it->nama_satuan }}</td>
                        <td>{{ $rp($it->harga_satuan) }}</td>
                        <td>{{ $rp($saldo['masuk']) }}</td>
                        <td class="text-center">{{ $it->qty_keluar ?: '-' }}</td>
                        <td class="text-center">{{ $it->nama_satuan }}</td>
                        <td>{{ $rp($it->harga_satuan) }}</td>
                        <td>{{ $rp($saldo['keluar']) }}</td>
                        <td class="text-center"><b>{{ $it->qty_akhir ?: '-' }}</b></td>
                        <td><b>{{ $rp($saldo['akhir']) }}</b></td>
                    </tr>
                @elseif ($b['type'] === 'sub')
                    <tr class="sub-total">
                        <td colspan="5" class="text-left">JUMLAH {{ $b['kode'] }}</td>
                        <td>{{ $rp($b['sub']['awal']) }}</td>
                        <td colspan="3"></td>
                        <td>{{ $rp($b['sub']['masuk']) }}</td>
                        <td colspan="3"></td>
                        <td>{{ $rp($b['sub']['keluar']) }}</td>
                        <td></td>
                        <td>{{ $rp($b['sub']['akhir']) }}</td>
                    </tr>
                @else
                    <tr class="grand-total">
                        <td colspan="5" class="text-left">TOTAL KESELURUHAN</td>
                        <td>{{ $rp($b['grand']['awal']) }}</td>
                        <td colspan="3"></td>
                        <td>{{ $rp($b['grand']['masuk']) }}</td>
                        <td colspan="3"></td>
                        <td>{{ $rp($b['grand']['keluar']) }}</td>
                        <td></td>
                        <td>{{ $rp($b['grand']['akhir']) }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

</body>

</html>
