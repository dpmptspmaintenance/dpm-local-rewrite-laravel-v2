<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Investasi (LPPD) — Data Kita</title>
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

    <div class="m-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan Target Investasi</h5>
                <button type="button" class="btn btn-sm btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahTarget">
                    <i class="bi bi-plus-circle"></i> Tambah Target
                </button>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.lppd.target-investasi.index') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Tahun Pelaporan</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahunPilih ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
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
                            <a href="{{ route('datakita.lppd.target-investasi.export', ['tahun' => $tahunPilih, 'status' => $statusPilih]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
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
                    <h6 class="fw-bold mb-0">TARGET INVESTASI {{ $headerStatusText }}</h6>
                    <h6 class="fw-bold mb-0">TAHUN {{ $tahunPilih }} (TAHUN PELAPORAN)</h6>
                    <h6 class="fw-bold mb-0">KAB/KOTA SEMARANG</h6>
                    <h6 class="fw-bold mb-0">TAHUN {{ $tahunPilih }}</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0" style="border-color: #000;">
                        <thead class="text-center fw-bold align-middle bg-light">
                            <tr>
                                <th width="10%">No</th>
                                <th width="40%">Kecamatan</th>
                                <th width="35%">Target Nilai Investasi (Rp)</th>
                                <th width="15%">Ket</th>
                            </tr>
                            <tr class="fst-italic" style="background-color: #f8f9fa;">
                                <td>(1)</td>
                                <td>(2)</td>
                                <td>(3)</td>
                                <td>(4)</td>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dataTarget as $row)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="fw-bold ps-4">{{ !empty($row->kecamatan) ? $row->kecamatan : '-' }}</td>
                                    <td class="text-end pe-4 fw-bold text-nowrap">Rp. {{ number_format($row->target_nilai, 0, ',', '.') }},-</td>
                                    <td class="text-center">{{ $row->keterangan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Data target belum diinput untuk periode dan status ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="fw-bold text-center bg-light">
                            <tr>
                                <td colspan="2" style="letter-spacing: 2px;">J u m l a h</td>
                                <td class="text-end pe-4 fs-6 text-primary text-nowrap">Rp. {{ number_format($totalTargetInvestasi, 0, ',', '.') }},-</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahTarget" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('datakita.lppd.target-investasi.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTambahLabel"><i class="bi bi-plus-circle"></i> Tambah Target Investasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tahun</label>
                            <input type="number" class="form-control" name="in_tahun" value="{{ date('Y') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Penanaman Modal</label>
                            <select class="form-select" name="in_status" required>
                                <option value="PMDN">PMDN (Dalam Negeri)</option>
                                <option value="PMA">PMA (Asing)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kecamatan</label>
                            <input type="text" class="form-control" name="in_kecamatan" placeholder="Contoh: Banyumanik" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nilai Target Investasi (Rp)</label>
                            <input type="number" class="form-control" name="in_target" placeholder="Contoh: 1500000000" step="0.01" required>
                            <small class="text-muted">Masukkan angka tanpa titik/koma (cth: 1500000000)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan (Opsional)</label>
                            <textarea class="form-control" name="in_ket" rows="2" placeholder="Tambahkan keterangan jika ada..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan_target" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
