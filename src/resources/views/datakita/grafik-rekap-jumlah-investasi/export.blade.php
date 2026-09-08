<!DOCTYPE html>
<html>

<head>
    <title>Export Data Rekap Jumlah Investasi</title>
    <style type="text/css">
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Hasil Export Data Perizinan OSS-RBA</h2>
        @if (count($rows) > 0)
            <table border="1" class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Uraian Skala Usaha</th>
                        <th>Uraian Risiko Proyek</th>
                        <th>Uraian Jenis Proyek</th>
                        <th>Uraian Status Penanaman Modal</th>
                        <th>Kelurahan</th>
                        <th>Kecamatan</th>
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>KBLI</th>
                        <th>Jumlah Investasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->uraian_skala_usaha }}</td>
                            <td>{{ $row->uraian_risiko_proyek }}</td>
                            <td>{{ $row->uraian_jenis_proyek }}</td>
                            <td>{{ $row->uraian_status_penanaman_modal }}</td>
                            <td>{{ $row->kelurahan_usaha }}</td>
                            <td>{{ $row->kecamatan_usaha }}</td>
                            <td>{{ $row->bulan_pengambilan_data }}</td>
                            <td>{{ $row->tahun_pengambilan_data }}</td>
                            <td>{{ $row->kbli }}</td>
                            <td>{{ $row->jml_investasi }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Tidak ada data yang ditemukan dari Data Perizinan OSS-RBA.</p>
        @endif
    </div>
</body>

</html>
