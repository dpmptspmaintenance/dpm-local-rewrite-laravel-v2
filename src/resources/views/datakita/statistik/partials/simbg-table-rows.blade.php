@php
    $statMaster = $getStatsRef($totalArr, $totalArr);
    $no = 1;
@endphp
@foreach ($dataArray as $key => $arrBulanan)
    @php
        $totRow = array_sum($arrBulanan);
        $pctRow = $gTotal > 0 ? ($totRow / $gTotal) * 100 : 0;
        $statRow = $getStatsRef($arrBulanan, $totalArr);
    @endphp
    <tr>
        <td>{{ $no }}</td>
        <td class="text-start">{{ strtoupper($key) }}</td>
        @for ($i = 1; $i <= 12; $i++)
            <td class="text-end">{{ $arrBulanan[$i] > 0 ? number_format($arrBulanan[$i], 0, ',', '.') : '-' }}</td>
        @endfor
        <td class="bg-grey text-end fw-bold">{{ number_format($totRow, 0, ',', '.') }}</td>
        <td class="bg-grey text-end">{{ $totRow > 0 ? number_format($pctRow, 2, ',', '.').'%' : '-' }}</td>
        <td class="bg-yellow text-end">{{ number_format($statRow['avg'], 0, ',', '.') }}</td>
        <td class="bg-yellow text-end">{{ number_format($statRow['max'], 0, ',', '.') }}</td>
        <td class="bg-yellow text-end">{{ number_format($statRow['min'], 0, ',', '.') }}</td>
    </tr>
    @php $no++; @endphp
@endforeach
<tr>
    <th colspan="2" class="bg-yellow text-center">JUMLAH TOTAL</th>
    @for ($i = 1; $i <= 12; $i++)
        <td class="bg-yellow text-end">{{ $totalArr[$i] > 0 ? number_format($totalArr[$i], 0, ',', '.') : '-' }}</td>
    @endfor
    <td class="bg-yellow text-end">{{ number_format($gTotal, 0, ',', '.') }}</td>
    <td class="bg-yellow text-end">{{ $gTotal > 0 ? '100%' : '0%' }}</td>
    <td class="bg-yellow text-end">{{ number_format($statMaster['avg'], 0, ',', '.') }}</td>
    <td class="bg-yellow text-end">{{ number_format($statMaster['max'], 0, ',', '.') }}</td>
    <td class="bg-yellow text-end">{{ number_format($statMaster['min'], 0, ',', '.') }}</td>
</tr>
