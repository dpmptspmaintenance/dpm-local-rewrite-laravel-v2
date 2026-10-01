@php
    $rupiah = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $hasItems = collect($blocks)->contains(fn ($b) => $b['type'] === 'item');
@endphp

@if (! $hasItems)
    <tr>
        <td colspan="16" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada data persediaan di bulan ini.</td>
    </tr>
@else
    @foreach ($blocks as $b)
        @if ($b['type'] === 'rek')
            <tr class="table-secondary bg-opacity-25 fw-bold text-dark tr-rekening">
                <td colspan="16" class="ps-3"><i class="bi bi-folder2-open me-2"></i> {{ $b['kode'] }} - {{ $b['nama'] }}</td>
            </tr>
        @elseif ($b['type'] === 'item')
            @php $it = $b['item']; $saldo = $b['saldo']; @endphp
            <tr class="align-middle bg-white hover-shadow tr-data">
                <td class="text-center text-muted small">{{ $b['no'] }}</td>
                <td class="fw-bold" style="min-width: 200px;">{{ $it->nama_barang }}</td>

                <td class="text-center bg-light">{{ $it->qty_awal ?: '-' }}</td>
                <td class="text-center bg-light text-muted small">{{ $it->nama_satuan }}</td>
                <td class="text-end bg-light small">{{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-end bg-light fw-bold text-secondary">{{ $rupiah($saldo['awal']) }}</td>

                <td class="text-center">{{ $it->qty_masuk ?: '-' }}</td>
                <td class="text-center text-muted small">{{ $it->nama_satuan }}</td>
                <td class="text-end small">{{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-end text-success fw-bold">{{ $rupiah($saldo['masuk']) }}</td>

                <td class="text-center bg-light">{{ $it->qty_keluar ?: '-' }}</td>
                <td class="text-center bg-light text-muted small">{{ $it->nama_satuan }}</td>
                <td class="text-end bg-light small">{{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-end bg-light text-danger fw-bold">{{ $rupiah($saldo['keluar']) }}</td>

                <td class="text-center fw-bold text-primary fs-6">{{ $it->qty_akhir ?: '-' }}</td>
                <td class="text-end fw-bold text-primary fs-6">{{ $rupiah($saldo['akhir']) }}</td>
            </tr>
        @elseif ($b['type'] === 'sub')
            <tr class="table-warning fw-bold align-middle tr-subtotal">
                <td colspan="5" class="text-end fst-italic">Jumlah {{ $b['kode'] }}</td>
                <td class="text-end">{{ $rupiah($b['sub']['awal']) }}</td>
                <td colspan="3" class="text-end"></td>
                <td class="text-end text-success">{{ $rupiah($b['sub']['masuk']) }}</td>
                <td colspan="3" class="text-end"></td>
                <td class="text-end text-danger">{{ $rupiah($b['sub']['keluar']) }}</td>
                <td colspan="1" class="text-end"></td>
                <td class="text-end text-primary">{{ $rupiah($b['sub']['akhir']) }}</td>
            </tr>
        @else
            <tr class="table-primary fw-bold align-middle tr-total">
                <td colspan="5" class="text-end text-uppercase fs-6">Total Keseluruhan</td>
                <td class="text-end fs-6">{{ $rupiah($b['grand']['awal']) }}</td>
                <td colspan="3" class="text-end text-uppercase"></td>
                <td class="text-end text-success fs-6">{{ $rupiah($b['grand']['masuk']) }}</td>
                <td colspan="3" class="text-end text-uppercase"></td>
                <td class="text-end text-danger fs-6">{{ $rupiah($b['grand']['keluar']) }}</td>
                <td colspan="1" class="text-end text-uppercase"></td>
                <td class="text-end text-primary fs-5">{{ $rupiah($b['grand']['akhir']) }}</td>
            </tr>
        @endif
    @endforeach
@endif
