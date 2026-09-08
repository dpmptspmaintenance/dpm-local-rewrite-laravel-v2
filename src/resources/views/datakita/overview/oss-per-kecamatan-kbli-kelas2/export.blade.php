<table border="1">
    <tr>
        <th colspan="8" style="background:#1565C0;color:#fff;">DATA INVESTASI KBLI 3 DIGIT {{ $tahun }}</th>
    </tr>
    <tr style="background:#CCC;font-weight:bold;">
        <th>No</th>
        <th>Kat</th>
        <th>Kategori</th>
        <th>Kode KBLI</th>
        <th>Judul KBLI</th>
        <th>Proyek</th>
        <th>Investasi</th>
        <th>%</th>
    </tr>
    @forelse ($rows as $i => $row)
        @php $isFirst = ($firstIdxMap[$row->kat_kode] === $i); @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            @if ($isFirst)
                <td rowspan="{{ $rowspanMap[$row->kat_kode] }}" align="center" valign="middle">{{ $row->kat_kode }}</td>
                <td rowspan="{{ $rowspanMap[$row->kat_kode] }}" valign="middle">{{ $row->kat_nama }}</td>
            @endif
            <td align="center">{{ $row->kode_kbli }}</td>
            <td>{{ $row->nama_kbli }}</td>
            <td align="right">{{ $row->jumlah_proyek }}</td>
            <td align="right">{{ $row->jumlah_investasi }}</td>
            <td align="right">{{ number_format(($row->jumlah_investasi / ($grandInvestasi ?: 1)) * 100, 2) }}%</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" align="center">Tidak ada data</td>
        </tr>
    @endforelse
</table>
