@php
    $tglTransaksi = \App\Http\Controllers\Persediaan\CetakController::tglIndo($header->tanggal_transaksi->format('Y-m-d'));
    $totalKeseluruhan = $details->sum(fn ($row) => $row->qty * $row->harga_satuan);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Bon Permintaan Barang - {{ $header->kode_transaksi }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            margin: 15mm 15mm;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .header-title {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .header-sub {
            font-size: 11pt;
            margin-bottom: 20px;
        }

        table.meta-info {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        table.meta-info td {
            padding: 3px 0;
            vertical-align: top;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        table.data-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
            font-size: 10pt;
        }

        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .ttd-table td {
            text-align: center;
            vertical-align: top;
        }

        .space-ttd {
            height: 55px;
        }

        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }

            body {
                margin: 0;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Cetak Dokumen
        </button>
    </div>

    <div class="text-center">
        <div class="header-title">BON PERMINTAAN BARANG PERSEDIAAN</div>
        <div class="header-sub">Nomor: {{ $header->kode_transaksi }}</div>
    </div>

    <table class="meta-info">
        <tr>
            <td style="width: 22%;"><b>Tanggal Permintaan</b></td>
            <td style="width: 2%;">:</td>
            <td style="width: 76%;">{{ $tglTransaksi }}</td>
        </tr>
        <tr>
            <td><b>Bidang / Peminta</b></td>
            <td>:</td>
            <td>{{ $header->pihak_terkait }}</td>
        </tr>
        <tr>
            <td><b>Alasan / Keperluan</b></td>
            <td>:</td>
            <td>{{ $header->alasan_pengambilan ?: '-' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Nama Uraian Barang</th>
                <th style="width: 12%;">Satuan</th>
                <th style="width: 15%;">Jumlah Diminta</th>
                <th style="width: 20%;">Harga Satuan (Rp)</th>
                <th style="width: 20%;">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $row)
                @php $subtotal = $row->qty * $row->harga_satuan; @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $row->nama_barang }}</td>
                    <td class="text-center">{{ $row->nama_satuan }}</td>
                    <td class="text-center fw-bold">{{ $row->qty }}</td>
                    <td class="text-end">{{ number_format($row->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #fafafa;">
                <td colspan="5" class="text-end fw-bold">Total Akumulasi:</td>
                <td class="text-end fw-bold">Rp {{ number_format($totalKeseluruhan, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="ttd-table">
        <tr>
            <td style="width: 50%;">
                Menyetujui,<br>
                <b>Sekretaris Dinas</b>
                <div class="space-ttd"></div>
                <b><u>{{ $header->ttd_sekretaris ?: 'Anton Siswartono, S.Sos, M.M' }}</u></b><br>
                <span>NIP. 196810131992031006</span>
            </td>
            <td style="width: 50%;">
                Mengetahui,<br>
                <b>Ka. Subbag Keuangan</b>
                <div class="space-ttd"></div>
                <b><u>{{ $header->ttd_tengah ?: 'Nany Marlina, SE' }}</u></b><br>
                <span>NIP. 197405132002122004</span>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 25px;">
                Diserahkan Oleh,<br>
                <b>Pengurus Barang</b>
                <div class="space-ttd"></div>
                <b><u>{{ $header->ttd_kiri ?: 'Naelu Shulhal Majid, A.Md.Ak' }}</u></b>
            </td>
            <td style="padding-top: 25px;">
                Semarang, {{ $tglTransaksi }}<br>
                Yang Meminta / Penerima,
                <div class="space-ttd"></div>
                <b><u>{{ $header->ttd_kanan }}</u></b>
            </td>
        </tr>
    </table>

</body>

</html>
