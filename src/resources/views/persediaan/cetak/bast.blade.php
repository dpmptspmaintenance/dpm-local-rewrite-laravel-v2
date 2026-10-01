<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak BAST - {{ $header->kode_transaksi }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 20mm 20mm 25mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 4px double #000;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        .kop-logo {
            width: 75px;
            height: auto;
            margin-right: 15px;
        }

        .kop-teks {
            text-align: center;
            flex-grow: 1;
        }

        .kop-teks h3 {
            font-size: 14pt;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .kop-teks h2 {
            font-size: 16pt;
            text-transform: uppercase;
            margin: 2px 0;
            font-weight: bold;
        }

        .kop-teks p {
            font-size: 9pt;
            font-style: italic;
            margin: 0;
        }

        .judul-dokumen {
            text-align: center;
            margin-bottom: 25px;
        }

        .judul-dokumen h4 {
            font-size: 12pt;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 0;
            font-weight: bold;
        }

        .judul-dokumen p {
            margin: 2px 0 0 0;
        }

        .paragraf-isi {
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 12px;
        }

        .identitas-table {
            margin: 10px 0 15px 40px;
            border-collapse: collapse;
        }

        .identitas-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .tabel-barang {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11pt;
        }

        .tabel-barang th,
        .tabel-barang td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .tabel-barang th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
        }

        .titimangsa {
            text-align: right;
            margin-right: 20px;
            margin-bottom: 15px;
        }

        .container-ttd {
            width: 100%;
            margin-top: 10px;
        }

        .row-ttd {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
        }

        .col-ttd {
            width: 45%;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .space-ttd {
            height: 65px;
        }

        .nama-pejabat {
            font-weight: bold;
            text-decoration: underline;
        }

        .no-print-area {
            background: #e9ecef;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        .btn-cetak {
            padding: 8px 24px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 11pt;
        }

        .btn-cetak:hover {
            background: #0b5ed7;
        }

        @media print {
            .no-print-area {
                display: none !important;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="no-print-area">
        <button class="btn-cetak" onclick="window.print()">Cetak Dokumen / Simpan PDF</button>
        <p style="margin: 5px 0 0 0; font-size: 9pt; color: #666;">Pastikan opsi margin di printer setelan browser Anda dipilih <b>Default</b>.</p>
    </div>

    <div style="width: 100%;">

        <!-- KOP SURAT RESMI DPMPTSP KOTA SEMARANG -->
        <div class="kop-surat">
            <img src="{{ $logoSrc }}" class="kop-logo" alt="Logo Pemkot">
            <div class="kop-teks">
                <h3>Pemerintah Kota Semarang</h3>
                <h2>Dinas Penanaman Modal Dan<br>Pelayanan Terpadu Satu Pintu</h2>
                <p>Jl. Jend. Urip Sumoharjo KM 17 (Mal Pelayanan Publik Terminal Mangkang Lantai 2) Kel. Mangkang Kulon, Kec. Tugu, Kota Semarang - 50155<br>Telp. (024) 3585944, 3548691 Email: dpmptsp@semarangkota.go.id</p>
            </div>
        </div>

        <!-- NOMOR SURAT DINAS -->
        <div class="judul-dokumen">
            <h4>Berita Acara Serah Terima Barang/Jasa</h4>
            <p>Nomor : <b>{{ $header->kode_transaksi }}</b></p>
        </div>

        <p class="paragraf-isi">
            Pada hari ini, <b>{{ $d['hari'] }}</b> tanggal <b>{{ $d['tgl'] }}</b> bulan <b>{{ $d['bulan'] }}</b> tahun <b>{{ $d['tahun'] }}</b>, kami yang bertanda tangan di bawah ini :
        </p>

        <table class="identitas-table">
            <tr>
                <td style="width: 80px;">Nama</td>
                <td>:</td>
                <td style="font-weight: bold;">{{ $header->ttd_kanan }}</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>:</td>
                <td>19990406 202201 1 002</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>Pengurus Barang Pengguna / Pengelola Akuntansi</td>
            </tr>
            <tr>
                <td>Naskah</td>
                <td>:</td>
                <td>Selaku yang menerima penyerahan barang persediaan internal dinas.</td>
            </tr>
        </table>

        <p class="paragraf-isi">
            Berdasarkan Nota Pembelian / BAST dari pihak penyedia barang/jasa dengan nama <b>{{ $header->pihak_terkait }}</b> telah menyerahkan barang persediaan dengan rincian jenis dan volume sebagai berikut:
        </p>

        <!-- DATA TABEL BARANG -->
        <table class="tabel-barang">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 22%;">Kode Rekening</th>
                    <th style="width: 38%;">Nama Uraian Barang / Persediaan</th>
                    <th style="width: 10%;">Volume</th>
                    <th style="width: 10%;">Satuan</th>
                    <th style="width: 15%;">Harga Satuan (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($details as $item)
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td style="text-align: center; font-family: monospace; font-size: 10pt;">{{ $item->kode_rekening ?: '-' }}</td>
                        <td style="font-weight: bold;">{{ $item->nama_barang }}</td>
                        <td style="text-align: center;">{{ $item->qty }}</td>
                        <td style="text-align: center;">{{ $item->nama_satuan }}</td>
                        <td style="text-align: right;">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; font-style: italic;">Tidak ada rincian barang tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="paragraf-isi">
            Demikian Berita Acara Serah Terima ini dibuat dalam rangkap 2 (Dua) untuk dapat dipergunakan sebagaimana mestinya.
        </p>

        <div class="titimangsa">
            Semarang, {{ $d['tgl'] }} {{ $d['bulan'] }} {{ $d['tahun'] }}
        </div>

        <!-- PANEL FORMASI MANDAT TANDA TANGAN -->
        <div class="container-ttd">

            <div class="row-ttd">
                <div class="col-ttd">
                    <span>Yang Menyerahkan,<br>Penyedia Barang / Jasa</span>
                    <div class="space-ttd"></div>
                    <span class="nama-pejabat">{{ $header->ttd_kiri ?: '.......................................' }}</span>
                    <small style="color:#555;">Pihak Vendor / Rekanan</small>
                </div>
                <div class="col-ttd">
                    <span>Yang Menerima,<br>Pengurus Barang Pengguna</span>
                    <div class="space-ttd"></div>
                    <span class="nama-pejabat">{{ $header->ttd_kanan }}</span>
                    <small>NIP. 19990406 202201 1 002</small>
                </div>
            </div>

            <div class="row-ttd">
                <div class="col-ttd" style="width: 100%; margin: 0 auto;">
                    <span>Mengetahui,<br>Kasubbag Keuangan &amp; Aset</span>
                    <div class="space-ttd"></div>
                    <span class="nama-pejabat">{{ $header->ttd_tengah ?: 'Nany Marlina, SE' }}</span>
                    <small>NIP. .......................................</small>
                </div>
            </div>

        </div>

    </div>

</body>

</html>
