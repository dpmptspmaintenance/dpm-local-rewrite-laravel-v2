<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .judul { font-size: 14pt; font-weight: bold; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #d9d9d9; border: 1px solid #000; padding: 8px; text-align: center; vertical-align: middle; }
        td { border: 1px solid #000; padding: 5px; vertical-align: middle; text-align: center; }
        .text-left { text-align: left; padding-left: 5px; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

    <table>
        <tr><td colspan="8" class="judul" style="border:none;">TABEL STATUS PERMOHONAN IZIN TENAGA KESEHATAN PER KECAMATAN PRAKTIK</td></tr>
        <tr><td colspan="8" class="judul" style="border:none; font-size:12pt;">{{ $txtPeriode }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="50">No</th>
                <th width="200">Kecamatan Praktik</th>
                <th width="350">Profesi</th>
                <th width="100">Dibatalkan</th>
                <th width="100">Ditolak</th>
                <th width="100">Verifikasi DPMPTSP</th>
                <th width="100">SK Diterbitkan</th>
                <th width="100">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @if ($rows->isNotEmpty())
                @foreach ($rows as $row)
                    @php
                        $kecClean = ucwords(strtolower($row->nama_kecamatan));
                        $jabatanClean = !empty($row->jabatan) ? ucwords(strtolower($row->jabatan)) : '-';
                    @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td class="text-left bold">{{ $kecClean }}</td>
                        <td class="text-left">{{ $jabatanClean }}</td>
                        <td>{{ $row->stat_batal == 0 ? '-' : $row->stat_batal }}</td>
                        <td>{{ $row->stat_tolak == 0 ? '-' : $row->stat_tolak }}</td>
                        <td>{{ $row->stat_verif == 0 ? '-' : $row->stat_verif }}</td>
                        <td>{{ $row->stat_terbit == 0 ? '-' : $row->stat_terbit }}</td>
                        <td class="bold">{{ $row->total_per_row }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="8">Data Kosong</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bold" style="background-color: #f2f2f2;">
                <td colspan="3" style="text-align: right;">TOTAL KESELURUHAN</td>
                <td>{{ $totBatal }}</td>
                <td>{{ $totTolak }}</td>
                <td>{{ $totVerif }}</td>
                <td>{{ $totTerbit }}</td>
                <td>{{ $grandTotal }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
