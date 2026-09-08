<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Sikenut</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.7/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        .navbar-custom {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .04);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .summary-card {
            border-left: 5px solid;
            border-radius: 8px;
        }

        .summary-primary { border-left-color: #0d6efd; }
        .summary-success { border-left-color: #198754; }
        .summary-warning { border-left-color: #ffc107; }

        .summary-icon {
            font-size: 2.5rem;
            opacity: 0.2;
            position: absolute;
            right: 15px;
            top: 15px;
        }

        @media (max-width: 768px) {
            .filter-controls {
                width: 100%;
                justify-content: flex-start !important;
            }

            #filterBidangTabel {
                width: 100% !important;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-custom mb-4 py-3">
        <div class="container-fluid px-4">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <span class="navbar-brand mb-0 h1 ms-3 fw-bold text-primary">Manajemen Sikenut</span>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm summary-card summary-primary h-100 position-relative">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold text-uppercase mb-1">Total Kegiatan SPPD</h6>
                        <h2 class="mb-0 fw-bold text-primary" id="sum_sppd">0</h2>
                        <i class="bi bi-car-front-fill summary-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm summary-card summary-success h-100 position-relative">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold text-uppercase mb-1">Total Kegiatan UT 50.000</h6>
                        <h2 class="mb-0 fw-bold text-success" id="sum_ut50">0</h2>
                        <i class="bi bi-cash-stack summary-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm summary-card summary-warning h-100 position-relative">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold text-uppercase mb-1">Total Kegiatan UT 75.000</h6>
                        <h2 class="mb-0 fw-bold text-warning" id="sum_ut75">0</h2>
                        <i class="bi bi-cash-coin summary-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h3 class="card-title mb-0">Data Kegiatan</h3>

                <div class="d-flex flex-wrap align-items-center gap-2 filter-controls">
                    <select class="form-select form-select-sm" id="filterBidangTabel" style="width: auto;">
                        <option value="">Semua Bidang</option>
                        <option value="bidang 1">Bidang 1</option>
                        <option value="bidang 2">Bidang 2</option>
                        <option value="bidang 3">Bidang 3</option>
                        <option value="bidang monev">Bidang Monev</option>
                        <option value="bidang pm">Bidang PM</option>
                        <option value="sekretariat">Sekretariat</option>
                    </select>

                    @if ($canAdd)
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-file-earmark-excel"></i> Import
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kegiatanModal" id="btnTambah">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="sikenutTable" class="table table-striped table-bordered " style="width:100%">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Kegiatan</th>
                                <th>Surat Tugas</th>
                                <th>Tgl. Surat</th>
                                <th>Disposisi & Bidang</th>
                                <th>Tgl. Acara</th>
                                <th>Anggaran Bulan</th>
                                <th>Tipe Anggaran</th>
                                <th>Anggaran Bidang</th>
                                <th>Tahun</th>
                                <th>Pembuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Import Data Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Pastikan format kolom sesuai: <br>
                            (A) Nama Kegiatan, (B) Surat Tugas, (C) Tgl Surat, (D) Tgl Acara, (E) Tipe, (F) Bulan, (G) Tahun, (H) Nama Disposisi, (I) Bidang, (J) Anggaran Bidang. Mulai dari baris ke-2.</p>
                        <input type="file" class="form-control" name="file_excel" accept=".xlsx, .xls, .csv" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success" id="btnProsesImport">Proses Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kegiatanModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="kegiatanForm">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalLabel">Form Kegiatan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <input type="hidden" id="action" name="action">
                        <input type="hidden" id="original_nama_kegiatan" name="original_nama_kegiatan">
                        <input type="hidden" id="original_surat_tugas" name="original_surat_tugas">

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="nama_kegiatan" class="form-label">Nama Kegiatan</label>
                                <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="surat_tugas" class="form-label">Surat Tugas</label>
                                <input type="text" class="form-control" id="surat_tugas" name="surat_tugas" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_surat" class="form-label">Tanggal Surat</label>
                                <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tanggal_acara" class="form-label">Tanggal Acara</label>
                                <input type="date" class="form-control" id="tanggal_acara" name="tanggal_acara" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tipe_anggaran" class="form-label">Tipe Anggaran</label>
                                <select class="form-select" id="tipe_anggaran" name="tipe_anggaran" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="UT 50000">UT 50000</option>
                                    <option value="UT 75000">UT 75000</option>
                                    <option value="SPPD">SPPD</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="anggaran_bulan" class="form-label">Anggaran Bulan</label>
                                <select class="form-select" id="anggaran_bulan" name="anggaran_bulan">
                                    <option value="">-- Pilih Bulan --</option>
                                    <option value="Januari">Januari</option>
                                    <option value="Februari">Februari</option>
                                    <option value="Maret">Maret</option>
                                    <option value="April">April</option>
                                    <option value="Mei">Mei</option>
                                    <option value="Juni">Juni</option>
                                    <option value="Juli">Juli</option>
                                    <option value="Agustus">Agustus</option>
                                    <option value="September">September</option>
                                    <option value="Oktober">Oktober</option>
                                    <option value="November">November</option>
                                    <option value="Desember">Desember</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tahun" class="form-label">Tahun</label>
                                <input type="number" class="form-control" id="tahun" name="tahun" value="{{ date('Y') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="anggaran_bidang" class="form-label">Anggaran Bidang (Yang Menganggarkan)</label>
                                <select class="form-select" id="anggaran_bidang" name="anggaran_bidang" required>
                                    <option value="">-- Pilih Bidang --</option>
                                    <option value="bidang 1">Bidang 1</option>
                                    <option value="bidang 2">Bidang 2</option>
                                    <option value="bidang 3">Bidang 3</option>
                                    <option value="bidang monev">Bidang Monev</option>
                                    <option value="bidang pm">Bidang PM</option>
                                    <option value="sekretariat">Sekretariat</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Tambah Disposisi</h5>
                        <div class="row align-items-end">
                            <div class="col-md-5 mb-2">
                                <label for="modal_disposisi" class="form-label">Disposisi</label>
                                <select class="form-select" id="modal_disposisi">
                                    <option value="">-- Memuat... --</option>
                                </select>
                            </div>
                            <div class="col-md-5 mb-2">
                                <label for="modal_bidang" class="form-label">Bidang</label>
                                <select class="form-select" id="modal_bidang">
                                    <option value="">-- Pilih Bidang --</option>
                                    <option value="bidang 1">Bidang 1</option>
                                    <option value="bidang 2">Bidang 2</option>
                                    <option value="bidang 3">Bidang 3</option>
                                    <option value="bidang monev">Bidang Monev</option>
                                    <option value="bidang pm">Bidang PM</option>
                                    <option value="sekretariat">Sekretariat</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="button" class="btn btn-success w-100" id="btnTambahBaris">Tambah</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mt-3" id="tabelDisposisi">
                                <thead>
                                    <tr>
                                        <th>Nama Disposisi</th>
                                        <th>Bidang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tabelDisposisiBody">
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.bootstrap5.js"></script>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
        });

        $(document).ready(function() {

            function loadSummary() {
                var filterBidang = $('#filterBidangTabel').val();

                $.ajax({
                    url: '{{ route('sikenut.summary') }}',
                    type: 'POST',
                    data: { filter_bidang: filterBidang },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status == 'success') {
                            $('#sum_sppd').text(res.data['SPPD'] ?? 0);
                            $('#sum_ut50').text(res.data['UT 50000'] ?? 0);
                            $('#sum_ut75').text(res.data['UT 75000'] ?? 0);
                        }
                    }
                });
            }
            loadSummary();

            var table = $('#sikenutTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('sikenut.data') }}",
                    "type": "POST",
                    "data": function(d) {
                        d.filter_bidang = $('#filterBidangTabel').val();
                    }
                },
                "pageLength": 50,
                "ordering": false,
                "columns": [
                    { "data": "no", "searchable": false },
                    { "data": "nama_kegiatan" },
                    { "data": "surat_tugas" },
                    { "data": "tanggal_surat" },
                    { "data": "disposisi" },
                    { "data": "tanggal_acara" },
                    { "data": "anggaran_bulan" },
                    { "data": "tipe_anggaran" },
                    { "data": "anggaran_bidang" },
                    { "data": "tahun" },
                    { "data": "pembuat" },
                    { "data": "aksi", "searchable": false }
                ]
            });

            $('#filterBidangTabel').on('change', function() {
                table.ajax.reload();
                loadSummary();
            });

            var daftarDisposisi = [];

            function muatDaftarDisposisi(callback = null) {
                if (daftarDisposisi.length > 0) {
                    isiDropdownDisposisi();
                    if (callback) callback();
                    return;
                }

                var disposisiSelect = $('#modal_disposisi');
                disposisiSelect.html('<option value="">-- Memuat daftar... --</option>');

                $.ajax({
                    url: '{{ route('sikenut.disposisi') }}',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 'success') {
                            daftarDisposisi = response.data;
                            isiDropdownDisposisi();
                            if (callback) callback();
                        } else {
                            disposisiSelect.html('<option value="">Gagal memuat</option>');
                        }
                    },
                    error: function() {
                        disposisiSelect.html('<option value="">Gagal memuat</option>');
                    }
                });
            }

            function isiDropdownDisposisi() {
                var disposisiSelect = $('#modal_disposisi');
                disposisiSelect.html('<option value="">-- Pilih Disposisi --</option>');
                $.each(daftarDisposisi, function(index, user) {
                    disposisiSelect.append($('<option>', {
                        value: user.id,
                        text: user.nama
                    }));
                });
            }

            $('#importForm').submit(function(e) {
                e.preventDefault();
                var btn = $('#btnProsesImport');
                btn.prop('disabled', true).text('Memproses...');

                var formData = new FormData(this);

                $.ajax({
                    url: '{{ route('sikenut.import') }}',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 'success') {
                            alert(response.message);
                            bootstrap.Modal.getInstance($('#importModal')[0]).hide();
                            $('#importForm')[0].reset();
                            table.ajax.reload();
                            loadSummary();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan koneksi saat import.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Proses Import');
                    }
                });
            });

            $('#btnTambah').on('click', function() {
                $('#kegiatanForm')[0].reset();
                $('#action').val('tambah_multi');
                $('#modalLabel').text('Tambah Data Kegiatan');
                $('#tahun').val(new Date().getFullYear());
                $('#tabelDisposisiBody').html('');

                $('#original_nama_kegiatan').val('');
                $('#original_surat_tugas').val('');

                muatDaftarDisposisi();
            });

            $('#sikenutTable tbody').on('click', '.btn-edit', function() {
                var id = $(this).data('id');

                $('#kegiatanForm')[0].reset();
                $('#action').val('update_multi');
                $('#modalLabel').text('Edit Data Kegiatan');
                $('#tabelDisposisiBody').html('<tr><td colspan="3">Memuat data...</td></tr>');

                muatDaftarDisposisi(function() {
                    $.ajax({
                        url: '{{ route('sikenut.get-single-group') }}',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 'success') {
                                var parent = response.parent_data;
                                var children = response.child_data;

                                $('#nama_kegiatan').val(parent.nama_kegiatan);
                                $('#surat_tugas').val(parent.surat_tugas);
                                $('#tanggal_surat').val(parent.tanggal_surat);
                                $('#tanggal_acara').val(parent.tanggal_acara);
                                $('#tipe_anggaran').val(parent.tipe_anggaran);
                                $('#anggaran_bulan').val(parent.anggaran_bulan);
                                $('#tahun').val(parent.tahun);
                                $('#anggaran_bidang').val(parent.anggaran_bidang);

                                $('#original_nama_kegiatan').val(parent.nama_kegiatan);
                                $('#original_surat_tugas').val(parent.surat_tugas);

                                $('#tabelDisposisiBody').html('');
                                $.each(children, function(index, item) {
                                    var namaDisposisi = "ID: " + item.disposisi;
                                    var user = daftarDisposisi.find(u => u.id == item.disposisi);
                                    if (user) namaDisposisi = user.nama;

                                    tambahBarisKeTabel(item.disposisi, namaDisposisi, item.bidang);
                                });

                                new bootstrap.Modal($('#kegiatanModal')[0]).show();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('Gagal mengambil data untuk diedit.');
                        }
                    });
                });
            });

            $('#btnTambahBaris').on('click', function() {
                var disposisiSelect = $('#modal_disposisi');
                var bidangSelect = $('#modal_bidang');

                var disposisiId = disposisiSelect.val();
                var disposisiNama = disposisiSelect.find('option:selected').text();
                var bidangNama = bidangSelect.val();

                if (!disposisiId || !bidangNama) {
                    alert('Silakan pilih Disposisi dan Bidang.');
                    return;
                }

                tambahBarisKeTabel(disposisiId, disposisiNama, bidangNama);

                disposisiSelect.val('');
                bidangSelect.val('');
            });

            function tambahBarisKeTabel(id, nama, bidang) {
                var barisBaru = `
                    <tr>
                        <td data-disposisi-id="${id}">${nama}</td>
                        <td>${bidang}</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm btn-hapus-baris">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#tabelDisposisiBody').append(barisBaru);
            }

            $('#tabelDisposisiBody').on('click', '.btn-hapus-baris', function() {
                $(this).closest('tr').remove();
            });

            $('#kegiatanForm').submit(function(e) {
                e.preventDefault();

                var action = $('#action').val();
                var dataUtama = {
                    nama_kegiatan: $('#nama_kegiatan').val(),
                    surat_tugas: $('#surat_tugas').val(),
                    tanggal_surat: $('#tanggal_surat').val(),
                    tanggal_acara: $('#tanggal_acara').val(),
                    tipe_anggaran: $('#tipe_anggaran').val(),
                    anggaran_bulan: $('#anggaran_bulan').val(),
                    tahun: $('#tahun').val(),
                    anggaran_bidang: $('#anggaran_bidang').val()
                };

                var dataDisposisi = [];
                $('#tabelDisposisiBody tr').each(function() {
                    dataDisposisi.push({
                        disposisi_id: $(this).find('td:eq(0)').data('disposisi-id'),
                        bidang: $(this).find('td:eq(1)').text()
                    });
                });

                if (dataDisposisi.length === 0) {
                    alert('Anda harus menambahkan minimal satu baris Disposisi dan Bidang.');
                    return;
                }

                var payload = {
                    data_utama: dataUtama,
                    data_disposisi: dataDisposisi
                };

                var url = '{{ route('sikenut.store') }}';
                if (action === 'update_multi') {
                    payload.original_nama_kegiatan = $('#original_nama_kegiatan').val();
                    payload.original_surat_tugas = $('#original_surat_tugas').val();
                    url = '{{ route('sikenut.update-multi') }}';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: payload,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 'success') {
                            alert('Data berhasil disimpan!');
                            bootstrap.Modal.getInstance($('#kegiatanModal')[0]).hide();
                            table.ajax.reload();
                            loadSummary();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Terjadi kesalahan. ' + textStatus);
                    }
                });
            });

            $('#kegiatanModal').on('hidden.bs.modal', function() {
                $('#kegiatanForm')[0].reset();
                $('#tabelDisposisiBody').html('');
            });

            $('#sikenutTable tbody').on('click', '.btn-delete', function() {
                var id = $(this).data('id');
                if (confirm('Anda yakin ingin menghapus kegiatan ini beserta seluruh disposisinya?')) {
                    $.ajax({
                        url: '{{ route('sikenut.destroy') }}',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 'success') {
                                alert('Kegiatan berhasil dihapus!');
                                table.ajax.reload();
                                loadSummary();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }
                    });
                }
            });

        });
    </script>

</body>

</html>
