@extends('persediaan.partials.header')

@section('title', 'Laporan Persediaan')

@section('content')
    <style>
        .table-laporan th {
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        .table-laporan td {
            font-size: 0.85rem;
        }

        .th-group {
            border-bottom: 2px solid #dee2e6;
        }

        .hover-shadow:hover {
            box-shadow: inset 0 0 0 9999px rgba(0, 0, 0, 0.02);
        }
    </style>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 d-flex justify-content-between align-items-center bg-primary bg-opacity-10 rounded-4 flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white p-3 rounded-circle me-3 shadow-sm">
                            <i class="bi bi-file-earmark-spreadsheet fs-3"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-primary mb-1">Laporan Persediaan</h4>
                            <span class="text-muted small">Rekapitulasi otomatis berdasarkan transaksi historis.</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <select id="filterBulan" class="form-select border-primary text-primary fw-bold shadow-sm">
                            @foreach ($bulanOptions as $val => $nama)
                                <option value="{{ $val }}" @selected($val == $defaultBulan)>{{ $nama }}</option>
                            @endforeach
                        </select>

                        <select id="filterTahun" class="form-select border-primary text-primary fw-bold shadow-sm">
                            @foreach ($tahunOptions as $th)
                                <option value="{{ $th }}" @selected($th == $defaultTahun)>{{ $th }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="btn btn-primary shadow-sm px-4 fw-bold" onclick="loadLaporan()">
                            <i class="bi bi-search me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger shadow-sm px-3" onclick="cetakPDF()" title="Cetak PDF / Print">
                            <i class="bi bi-printer-fill"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success shadow-sm px-3" onclick="exportExcel()" title="Export .xlsx Resmi">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-bordered table-laporan mb-0" id="tabelPersediaan">
                    <thead class="table-dark sticky-top text-center text-uppercase">
                        <tr>
                            <th rowspan="2" class="align-middle">No</th>
                            <th rowspan="2" class="align-middle" style="min-width: 200px;">Nama Barang</th>
                            <th colspan="4" class="th-group bg-secondary">Saldo Awal</th>
                            <th colspan="4" class="th-group bg-success text-white">Mutasi Masuk</th>
                            <th colspan="4" class="th-group bg-danger text-white">Mutasi Keluar</th>
                            <th colspan="2" class="th-group bg-primary text-white">Saldo Akhir</th>
                        </tr>
                        <tr>
                            <th class="bg-secondary text-white border-top-0">Stok</th>
                            <th class="bg-secondary text-white border-top-0">Sat</th>
                            <th class="bg-secondary text-white border-top-0">Harga</th>
                            <th class="bg-secondary text-white border-top-0">Total (Rp)</th>
                            <th class="bg-success text-white border-top-0">Stok</th>
                            <th class="bg-success text-white border-top-0">Sat</th>
                            <th class="bg-success text-white border-top-0">Harga</th>
                            <th class="bg-success text-white border-top-0">Total (Rp)</th>
                            <th class="bg-danger text-white border-top-0">Stok</th>
                            <th class="bg-danger text-white border-top-0">Sat</th>
                            <th class="bg-danger text-white border-top-0">Harga</th>
                            <th class="bg-danger text-white border-top-0">Total (Rp)</th>
                            <th class="bg-primary text-white border-top-0">Stok</th>
                            <th class="bg-primary text-white border-top-0">Total (Rp)</th>
                        </tr>
                    </thead>
                    <tbody id="dataLaporan">
                        <tr>
                            <td colspan="16" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 text-muted">Memuat data persediaan...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const laporanDataUrl = @json(route('persediaan.laporan.data'));
        const laporanExportUrl = @json(route('persediaan.laporan.export'));
        const laporanCetakUrl = @json(route('persediaan.cetak.laporan'));
        const laporanCsrfToken = @json(csrf_token());

        $(document).ready(function() {
            loadLaporan();
        });

        function loadLaporan() {
            let bulan = $('#filterBulan').val();
            let tahun = $('#filterTahun').val();
            $('#dataLaporan').html('<tr><td colspan="16" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Mengkalkulasi saldo...</div></td></tr>');

            $.ajax({
                url: laporanDataUrl,
                type: 'POST',
                data: {
                    bulan: bulan,
                    tahun: tahun,
                    _token: laporanCsrfToken
                },
                success: function(response) {
                    $('#dataLaporan').hide().html(response).fadeIn('fast');
                },
                error: function() {
                    $('#dataLaporan').html('<tr><td colspan="16" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>');
                }
            });
        }

        function cetakPDF() {
            let bulan = $('#filterBulan').val();
            let tahun = $('#filterTahun').val();
            window.open(laporanCetakUrl + '?bulan=' + bulan + '&tahun=' + tahun, '_blank');
        }

        function exportExcel() {
            let bulan = $('#filterBulan').val();
            let tahun = $('#filterTahun').val();
            window.open(laporanExportUrl + '?bulan=' + bulan + '&tahun=' + tahun, '_blank');
        }
    </script>
@endpush
