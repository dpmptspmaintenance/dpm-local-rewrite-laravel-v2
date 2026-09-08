<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th colspan="6">Data Investasi: {{ $kecamatan == '' ? 'Semua Kecamatan (Kota Semarang)' : 'Kecamatan ' . $kecamatan }} (Periode: {{ ($bulan == '' ? 'Tahun' : $months[$bulan]) . " $tahun" }})</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Kode Klas</th>
            <th>Nama Klasifikasi</th>
            <th>Jumlah Proyek</th>
            <th>Jumlah Investasi (Rp)</th>
            <th>Persentase (%)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            @php
                $parts = explode('. ', $row->kategori_kbli, 2);
                $kode = $parts[0] ?? '?';
                $nama = $parts[1] ?? $row->kategori_kbli;
                $persentase = ($grandInvestasi > 0) ? ($row->jumlah_investasi / $grandInvestasi) * 100 : 0;
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $kode }}</td>
                <td>{{ $nama }}</td>
                <td>{{ $row->jumlah_proyek }}</td>
                <td>{{ $row->jumlah_investasi }}</td>
                <td>{{ number_format($persentase, 2, ',', '.') }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" align="center">Tidak ada data</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2"><strong>Total Investasi</strong></td>
            <td><strong>{{ $grandProyek }}</strong></td>
            <td><strong>{{ $grandInvestasi }}</strong></td>
            <td><strong>100,00%</strong></td>
        </tr>
    </tfoot>
</table>
