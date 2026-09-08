<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Investasi (LPPD) — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: "Moderustic", sans-serif; }
    </style>
</head>

<body>
    @include('datakita.partials.navbar')

    @php
        $baseQuery = ['tahun' => $tahunPilih, 'status' => $statusPilih];
    @endphp

    <div class="m-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan Rincian Investasi</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.lppd.status-investasi.index') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tahun Pelaporan</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahunPilih ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                                @if ($listTahun->isEmpty())
                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Status Penanaman Modal</label>
                            <select class="form-select" name="status">
                                <option value="PMDN" {{ $statusPilih == 'PMDN' ? 'selected' : '' }}>PMDN (Dalam Negeri)</option>
                                <option value="PMA" {{ $statusPilih == 'PMA' ? 'selected' : '' }}>PMA (Asing)</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.lppd.status-investasi.export', $baseQuery) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Semua (Excel)
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1">KOP SURAT</h5>
                    <h5 class="fw-bold mb-0" style="color: #b5651d;">DINAS PENANAMAN MODAL KAB/KOTA SEMARANG</h5>
                    <hr style="border: 2px solid #000; opacity: 1; margin-top: 5px; margin-bottom: 5px;">
                    <h6 class="fw-bold mb-0">RINCIAN INVESTASI {{ $headerStatusText }}</h6>
                    <h6 class="fw-bold mb-0">TAHUN {{ $tahunPilih }} (TAHUN PELAPORAN)</h6>
                    <h6 class="fw-bold mb-0">KAB/KOTA SEMARANG</h6>
                    <h6 class="fw-bold mb-0">TAHUN {{ $tahunPilih }}</h6>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Menampilkan baris {{ $totalRecords > 0 ? ($offset + 1) : 0 }} - {{ min($offset + $limit, $totalRecords) }} dari <strong>{{ number_format($totalRecords, 0, ',', '.') }}</strong> total data</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #000;">
                        <thead class="text-center fw-bold align-middle bg-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Nama Perusahaan</th>
                                <th width="20%">Alamat Perusahaan</th>
                                <th width="10%">Kecamatan</th>
                                <th width="15%">Jenis Investasi</th>
                                <th width="15%">Kegiatan Investasi</th>
                                <th width="15%">Nilai Investasi (Rp)</th>
                                <th width="5%">Ket</th>
                            </tr>
                            <tr class="fst-italic" style="background-color: #f8f9fa;">
                                <td>(1)</td>
                                <td>(2)</td>
                                <td>(3)</td>
                                <td>(4)</td>
                                <td>(5)</td>
                                <td>(6)</td>
                                <td>(7)</td>
                                <td>(8)</td>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dataInvestasi as $row)
                                @php
                                    $namaPerusahaan = !empty($row->nama_perusahaan) ? $row->nama_perusahaan : '-';
                                    $alamat = !empty($row->lokasi_usaha) ? $row->lokasi_usaha : '-';
                                    $jenis = !empty($row->nama_sektor) ? $row->nama_sektor : '-';
                                    $kegiatan = !empty($row->deskripsi_kbli) ? $row->deskripsi_kbli : '-';
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $offset + $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $namaPerusahaan }}</td>
                                    <td>{{ $alamat }}</td>
                                    <td class="text-center">-</td>
                                    <td>{{ $jenis }}</td>
                                    <td>{{ $kegiatan }}</td>
                                    <td class="text-end fw-bold text-nowrap">Rp. {{ number_format($row->nilai_investasi, 0, ',', '.') }},-</td>
                                    <td></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Data tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="fw-bold text-center bg-light">
                            <tr>
                                <td colspan="6" style="letter-spacing: 2px;">GRAND TOTAL KESELURUHAN</td>
                                <td class="text-end fs-6 text-primary text-nowrap">Rp. {{ number_format($grandTotalInvestasi, 0, ',', '.') }},-</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($totalPages > 1)
                    @php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                    @endphp
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ route('datakita.lppd.status-investasi.index', array_merge($baseQuery, ['page' => $page - 1])) }}" aria-label="Previous">
                                    <span aria-hidden="true">&laquo; Prev</span>
                                </a>
                            </li>

                            @if ($startPage > 1)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif

                            @for ($i = $startPage; $i <= $endPage; $i++)
                                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                                    <a class="page-link" href="{{ route('datakita.lppd.status-investasi.index', array_merge($baseQuery, ['page' => $i])) }}">{{ $i }}</a>
                                </li>
                            @endfor

                            @if ($endPage < $totalPages)
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            @endif

                            <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ route('datakita.lppd.status-investasi.index', array_merge($baseQuery, ['page' => $page + 1])) }}" aria-label="Next">
                                    <span aria-hidden="true">Next &raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                @endif

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
