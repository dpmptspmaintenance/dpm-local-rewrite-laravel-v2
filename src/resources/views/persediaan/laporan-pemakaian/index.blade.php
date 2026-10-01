@extends('persediaan.partials.header')

@section('title', 'Persediaan Bidang')

@section('content')
    @php
        $fmtQty = fn ($v) => number_format((float) $v, 0, ',', '.');
    @endphp

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold m-0 text-success"><i class="bi bi-clipboard-data me-2"></i>Persediaan / Penerimaan Barang Bidang</h5>
                <small class="text-muted">
                    Khusus Bidang: <b>{{ $labelBidang }}</b>
                    — {{ $bulanNama[$bulan] ?? $bulan }} {{ $tahun }}
                </small>
            </div>
            <form method="GET" action="{{ route('persediaan.laporan-pemakaian.index') }}" class="d-flex align-items-center gap-2">

                @if ($isAdmin)
                    <select name="bidang" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="" {{ $filterBidang === '' ? 'selected' : '' }}>-- Semua Bidang --</option>
                        @foreach ($listBidang as $b)
                            <option value="{{ $b }}" {{ $filterBidang === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $labelBidang }}</span>
                @endif

                <select name="bulan" class="form-select form-select-sm w-auto">
                    @foreach ($bulanNama as $num => $nama)
                        <option value="{{ $num }}" {{ $num == $bulan ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <input type="number" name="tahun" class="form-control form-control-sm w-auto" value="{{ $tahun }}" min="2020">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Lihat</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center small text-muted">
                        <tr>
                            <th rowspan="2" class="align-middle">No</th>
                            <th rowspan="2" class="align-middle text-start">Nama Barang</th>
                            <th rowspan="2" class="align-middle">Satuan</th>
                            <th colspan="1">Saldo Awal</th>
                            <th colspan="1">Penerimaan</th>
                            <th colspan="1">Pengeluaran</th>
                            <th colspan="1" class="bg-primary bg-opacity-10 text-primary">Saldo Akhir</th>
                        </tr>
                        <tr>
                            <th>Qty</th>
                            <th>Qty</th>
                            <th>Qty</th>
                            <th class="bg-primary bg-opacity-10 text-primary">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @forelse ($rows as $i => $r)
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>
                                <td class="text-start fw-bold">{{ $r->nama_barang }}</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $r->nama_satuan }}</span></td>
                                <td class="fw-bold">{{ $fmtQty($r->saldo_awal) }}</td>
                                <td class="text-success fw-bold">{{ $fmtQty($r->masuk) }}</td>
                                <td class="text-danger">{{ $fmtQty($r->keluar) }}</td>
                                <td class="bg-primary bg-opacity-10 text-primary fw-bold fs-6">{{ $fmtQty($r->saldo_akhir) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ada data transaksi pengambilan barang pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .card,
            .card * {
                visibility: visible;
            }

            .card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none !important;
                border: none !important;
            }

            .btn,
            form {
                display: none !important;
            }
        }
    </style>
@endsection
