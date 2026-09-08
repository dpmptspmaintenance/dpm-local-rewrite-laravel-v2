<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>

<head>
    <meta charset="utf-8">
    <style>
        /* Pengaturan Kertas Full Landscape Dengan Margin Optimal */
        @page Section1 {
            size: 841.9pt 595.3pt;
            mso-page-orientation: landscape;
            margin: 0.4in 0.4in 0.4in 0.4in;
        }

        div.Section1 {
            page: Section1;
        }

        .kop-instansi {
            font-family: 'Arial', sans-serif;
            text-align: left;
            color: #000000;
            margin-bottom: 5px;
        }

        .kop-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-subtitle {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .kop-report {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .line-hr {
            border-bottom: 2px solid #000000;
            border-top: 1px solid #000000;
            height: 3px;
            margin-bottom: 15px;
        }

        /* Ukuran Font Diperkecil Agar Muat TTD & Tidak Terpotong */
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            margin-top: 5px;
            table-layout: auto;
        }

        th {
            background-color: #334155;
            color: #ffffff;
            font-weight: bold;
            border: 1px solid #000000;
            padding: 5px 3px;
            text-align: center;
            font-size: 9px;
        }

        td {
            border: 1px solid #000000;
            padding: 4px 3px;
            text-align: center;
            color: #000000;
            font-size: 9px;
        }

        /* Khusus Tabel Risiko dengan 19 Kolom */
        .tbl-risk {
            font-size: 8px !important;
        }

        .tbl-risk th {
            padding: 4px 2px !important;
            font-size: 8px !important;
        }

        .tbl-risk td {
            padding: 3px 2px !important;
            font-size: 8px !important;
            white-space: nowrap;
        }

        .tbl-risk td.text-start {
            white-space: normal !important;
            min-width: 120px;
            max-width: 160px;
        }

        .text-start {
            text-align: left !important;
        }

        .text-end {
            text-align: right !important;
        }

        .fw-bold {
            font-weight: bold;
        }

        .summary-word {
            background-color: #f1f5f9;
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
            clear: all;
            mso-break-type: section-break;
        }

        /* Style Khusus Batas Tanda Tangan Tanpa Border Tabel */
        .ttd-table {
            border: none !important;
            margin-top: 20px;
            width: 100%;
            table-layout: fixed;
        }

        .ttd-table td {
            border: none !important;
            padding: 0px !important;
            text-align: left !important;
            font-size: 10px !important;
            font-family: 'Arial', sans-serif;
        }
    </style>
</head>

<body>
    @php
        $fmt = fn ($v) => ($v > 0) ? number_format($v, 0, ',', '.') : '-';
        $fmtPct = fn ($v) => ($v > 0) ? number_format($v, 2, ',', '.').'%' : '-';
    @endphp

    <div class="Section1">
        <!-- ==========================================
        PAGE 1: LAPORAN STATISTIK JUMLAH PROYEK
        =========================================== -->
        <div class="kop-instansi">
            <div class="kop-title">Pemerintah Kota Semarang</div>
            <div class="kop-subtitle">Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu (DPMPTSP)</div>
            <div class="kop-report">Laporan Statistik Jumlah Proyek - Tahun {{ $selectedYear }}</div>
        </div>
        <div class="line-hr"></div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 20%;">TAHUN</th>
                    <th style="width: 20%;">BULAN</th>
                    <th>JUMLAH PER BULAN</th>
                    <th>% PER BULAN</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 12; $i++)
                    @php
                        $jml = $dataProyek[$i] ?? 0;
                        $pct = $totalProyek > 0 ? ($jml / $totalProyek) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $i }}</td>
                        <td>{{ $selectedYear }}</td>
                        <td>{{ $i }}</td>
                        <td class="text-end">{{ $fmt($jml) }}</td>
                        <td class="text-end">{{ $fmtPct($pct) }}</td>
                    </tr>
                @endfor
                <tr class="summary-word">
                    <td colspan="3" class="text-start">JUMLAH</td>
                    <td class="text-end">{{ number_format($totalProyek, 0, ',', '.') }}</td>
                    <td class="text-end">100.00%</td>
                </tr>
                <tr class="summary-word">
                    <td colspan="3" class="text-start">RATA - RATA</td>
                    <td class="text-end">{{ number_format(round($statProyek['avg']), 0, ',', '.') }}</td>
                    <td class="text-end"></td>
                </tr>
                <tr class="summary-word">
                    <td colspan="3" class="text-start">TERTINGGI</td>
                    <td class="text-end">{{ number_format($statProyek['max'], 0, ',', '.') }}</td>
                    <td class="text-end"></td>
                </tr>
                <tr class="summary-word">
                    <td colspan="3" class="text-start">TERENDAH</td>
                    <td class="text-end">{{ number_format($statProyek['min'], 0, ',', '.') }}</td>
                    <td class="text-end"></td>
                </tr>
            </tbody>
        </table>

        <!-- Area Tanda Tangan Halaman 1 -->
        <table class="ttd-table">
            <tr>
                <td style="width: 65%;"></td>
                <td style="width: 35%;">
                    Semarang, ....................................<br>
                    Kepala Dinas DPMPTSP<br>
                    Kota Semarang<br>
                    <br><br><br><br>
                    <b><u>......................................................</u></b><br>
                    NIP. .................................................
                </td>
            </tr>
        </table>

        <!-- ==========================================
        PAGE 2: LAPORAN URAIAN RISIKO PROYEK (JUMLAH)
        =========================================== -->
        <br clear="all" class="page-break" style="page-break-before: always; mso-break-type: section-break;" />
        <div class="kop-instansi">
            <div class="kop-title">Pemerintah Kota Semarang</div>
            <div class="kop-subtitle">Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu (DPMPTSP)</div>
            <div class="kop-report">Laporan Uraian Risiko Proyek (Jumlah) - Tahun {{ $selectedYear }}</div>
        </div>
        <div class="line-hr"></div>
        <table class="tbl-risk">
            <thead>
                <tr>
                    <th rowspan="3">No</th>
                    <th rowspan="3" style="width:130px;">Uraian Risiko Proyek</th>
                    <th colspan="12">TAHUN : {{ $selectedYear }}</th>
                    <th rowspan="3">Jumlah</th>
                    <th rowspan="3">% JUMLAH</th>
                    <th rowspan="3">RATA - RATA</th>
                    <th rowspan="3">TERTINGGI</th>
                    <th rowspan="3">TERENDAH</th>
                </tr>
                <tr>
                    <th colspan="12">BULAN</th>
                </tr>
                <tr>
                    @for ($m = 1; $m <= 12; $m++)
                        <th>{{ $m }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @php $idx = 1; @endphp
                @foreach ($risikoList as $rs)
                    @php
                        $arrBulanan = $dataRisikoProyek[$rs] ?? [];
                        $totRs = array_sum($arrBulanan);
                        $activeVals = array_filter($arrBulanan, fn ($v) => $v > 0);
                        $pctRs = $totalProyek > 0 ? ($totRs / $totalProyek) * 100 : 0;
                        $avgRs = count($activeVals) > 0 ? array_sum($activeVals) / count($activeVals) : 0;
                        $maxRs = count($activeVals) > 0 ? max($activeVals) : 0;
                        $minRs = count($activeVals) > 0 ? min($activeVals) : 0;
                    @endphp
                    <tr>
                        <td>{{ $idx++ }}</td>
                        <td class="text-start">{{ $rs }}</td>
                        @for ($m = 1; $m <= 12; $m++)
                            <td class="text-end">{{ $fmt($arrBulanan[$m] ?? 0) }}</td>
                        @endfor
                        <td class="fw-bold text-end">{{ number_format($totRs, 0, ',', '.') }}</td>
                        <td class="text-end">{{ $fmtPct($pctRs) }}</td>
                        <td class="text-end">{{ number_format(round($avgRs), 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($maxRs, 0, ',', '.') }}</td>
                        <td class="text-end">{{ $minRs > 0 ? number_format($minRs, 0, ',', '.') : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="summary-word">
                    <td colspan="2">JUMLAH TOTAL</td>
                    @for ($m = 1; $m <= 12; $m++)
                        <td class="text-end">{{ $fmt($dataProyek[$m] ?? 0) }}</td>
                    @endfor
                    <td class="text-end">{{ number_format($totalProyek, 0, ',', '.') }}</td>
                    <td class="text-end">100.00%</td>
                    <td class="text-end">{{ number_format(round($statProyek['avg']), 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($statProyek['max'], 0, ',', '.') }}</td>
                    <td class="text-end">{{ $statProyek['min'] > 0 ? number_format($statProyek['min'], 0, ',', '.') : '-' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Area Tanda Tangan Halaman 2 -->
        <table class="ttd-table">
            <tr>
                <td style="width: 65%;"></td>
                <td style="width: 35%;">
                    Semarang, ....................................<br>
                    Kepala Dinas DPMPTSP<br>
                    Kota Semarang<br>
                    <br><br><br><br>
                    <b><u>......................................................</u></b><br>
                    NIP. .................................................
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
