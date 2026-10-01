@php
    $tglOpname = \App\Http\Controllers\Persediaan\CetakController::tglIndo($header->tanggal_opname->format('Y-m-d'));
    $totalNilaiSelisih = $details->sum(fn ($r) => $r->selisih * $r->harga_satuan);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>BASO - {{ $header->kode_opname }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            margin: 12mm 15mm;
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
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
        }

        .header-sub {
            font-size: 10pt;
            margin-bottom: 18px;
        }

        table.meta-info {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        table.meta-info td {
            padding: 2px 0;
            vertical-align: top;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }

        table.data-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
            font-size: 9pt;
        }

        .selisih-lebih {
            color: #198754;
        }

        .selisih-kurang {
            color: #dc3545;
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
            height: 50px;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
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

    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #198754; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Cetak BASO
        </button>
    </div>

    <div class="text-center">
        <div class="header-title">BERITA ACARA STOK OPNAME (BASO)</div>
        <div class="header-sub">Nomor: {{ $header->kode_opname }}</div>
    </div>

    <table class="meta-info">
        <tr>
            <td style="width: 22%;"><b>Tanggal Pelaksanaan</b></td>
            <td style="width: 2%;">:</td>
            <td style="width: 76%;">{{ $tglOpname }}</td>
        </tr>
        <tr>
            <td><b>Nama Kegiatan Audit</b></td>
            <td>:</td>
            <td>{{ $header->nama_kegiatan }}</td>
        </tr>
        <tr>
            <td><b>Status Opname</b></td>
            <td>:</td>
            <td><b>{{ strtoupper($header->status) }}</b></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th>Nama Uraian Barang</th>
                <th style="width: 8%;">Satuan</th>
                <th style="width: 13%;">Harga Batch</th>
                <th style="width: 9%;">Stok Sistem</th>
                <th style="width: 9%;">Stok Fisik</th>
                <th style="width: 9%;">Selisih</th>
                <th style="width: 15%;">Nilai Selisih (Rp)</th>
                <th style="width: 18%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $r)
                @php $nilaiSelisih = $r->selisih * $r->harga_satuan; @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $r->nama_barang }}</td>
                    <td class="text-center">{{ $r->nama_satuan }}</td>
                    <td class="text-end">{{ number_format($r->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $r->stok_sistem }}</td>
                    <td class="text-center fw-bold">{{ $r->stok_fisik }}</td>
                    <td class="text-center fw-bold {{ $r->selisih > 0 ? 'selisih-lebih' : ($r->selisih < 0 ? 'selisih-kurang' : '') }}">{{ ($r->selisih > 0 ? '+' : '').$r->selisih }}</td>
                    <td class="text-end">{{ number_format($nilaiSelisih, 0, ',', '.') }}</td>
                    <td>{{ $r->alasan_selisih ?: '-' }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #fafafa;">
                <td colspan="7" class="text-end fw-bold">Total Akumulasi Nilai Selisih:</td>
                <td class="text-end fw-bold">Rp {{ number_format($totalNilaiSelisih, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- FORMASI TANDA TANGAN 2x2 SEIMBANG -->
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
            <td style="padding-top: 20px;">
                Pengurus Barang,<br>
                <b>Petugas Persediaan</b>
                <div class="space-ttd"></div>
                <b><u>{{ $header->ttd_kiri ?: 'Naelu Shulhal Majid, A.Md.Ak' }}</u></b>
            </td>
            <td style="padding-top: 20px;">
                Semarang, {{ $tglOpname }}<br>
                <b>Tim Pemeriksa / Saksi</b>
                <div class="space-ttd"></div>
                <b><u>{{ $header->ttd_kanan }}</u></b>
            </td>
        </tr>
    </table>

</body>

</html>
