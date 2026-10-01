@extends('persediaan.partials.header')

@section('title', 'Input BAST / Penerimaan')

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
@endpush

@section('content')
    <style>
        .card-simple {
            border: none !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03) !important;
            background-color: #ffffff;
        }

        .spacing-box {
            margin-bottom: 28px !important;
        }

        .form-label-custom {
            font-size: 0.875rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
        }

        .select2-container--default .select2-selection--single {
            height: 38px !important;
            padding: 5px 8px;
            font-size: 0.95rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            color: #212529;
        }

        .input-custom-style {
            border-radius: 8px !important;
            border: 1px solid #dee2e6;
            padding: 7px 12px;
            font-size: 0.95rem;
        }

        .input-custom-style:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.08);
        }
    </style>

    <form method="POST" action="{{ route('persediaan.transaksi-bast.store') }}" id="formBAST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="action_type" id="action_type" value="submit">

        <!-- CARD 1: INFORMASI DOKUMEN UTAMA (HEADER) -->
        <div class="card card-simple p-4 spacing-box">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Dokumen BAST</h5>

            <div class="row g-4 mb-3">
                <div class="col-md-4">
                    <label class="form-label-custom">Tanggal Dokumen</label>
                    <input type="date" name="tanggal_transaksi" class="form-control input-custom-style" value="{{ old('tanggal_transaksi', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Jenis Penerimaan</label>
                    <select name="jenis_mutasi" class="form-select input-custom-style">
                        <option value="masuk" @selected(old('jenis_mutasi') === 'masuk')>Masuk (Pembelian/Vendor)</option>
                        <option value="saldo_awal" @selected(old('jenis_mutasi') === 'saldo_awal')>Saldo Awal Tahun</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Nama Rekanan / Vendor</label>
                    <input type="text" name="pihak_terkait" class="form-control input-custom-style" placeholder="Contoh: CV. Jaya Mandiri ATK" value="{{ old('pihak_terkait') }}" required>
                </div>
            </div>

            <div class="row g-4 mb-3">
                <div class="col-md-4">
                    <label class="form-label-custom">Pihak Penyerah (Vendor)</label>
                    <input type="text" name="ttd_kiri" class="form-control input-custom-style" placeholder="Nama Sales / Staff Toko" value="{{ old('ttd_kiri') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Pihak Penerima (Pengurus Barang)</label>
                    <input type="text" name="ttd_kanan" class="form-control input-custom-style" value="{{ old('ttd_kanan', 'Naelu Shulhal Majid, A.Md.Ak') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">Mengetahui (Kasubbag Keuangan)</label>
                    <input type="text" name="ttd_tengah" class="form-control input-custom-style" value="{{ old('ttd_tengah', 'Nany Marlina, SE') }}" required>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label-custom">Upload Lampiran Dokumen Nota dan Foto Barang yang dibeli <span class="text-muted fw-normal">(Format Gambar/PDF, Maksimal 5MB per file)</span></label>
                    <input type="file" name="lampiran_berkas[]" class="form-control input-custom-style border-dashed" accept="image/*,application/pdf" multiple required>
                </div>
            </div>
        </div>

        <!-- CARD 2: PENCARIAN & TAMBAH BARANG -->
        <div class="card card-simple p-4 spacing-box border-start border-primary border-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-search text-primary me-2"></i>Pilih & Tambah Uraian Barang</h5>

            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label-custom">Nama Komoditas / Uraian ATK</label>
                    <select id="select-barang-lookup" class="form-select">
                        <option value="">-- Ketik Kata Kunci Nama Barang --</option>
                        @foreach ($barang as $brg)
                            <option value="{{ $brg->id }}" data-harga="{{ (int) $brg->harga_satuan }}">{{ $brg->nama_barang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-custom">Harga Satuan (Rp)</label>
                    <input type="number" id="input-harga-acuan" class="form-control input-custom-style text-end fw-bold" placeholder="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">Jumlah (Qty)</label>
                    <input type="number" id="input-qty-masuk" class="form-control input-custom-style text-center fw-bold" min="1" value="1">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-primary w-100 shadow-sm" style="height: 38px;" onclick="tambahKeResume()" title="Tambahkan ke tabel resume"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </div>

        <!-- CARD 3: KERANJANG RESUME BELANJA -->
        <div class="card card-simple spacing-box overflow-hidden">
            <div class="p-4 bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-cart3 text-success me-2"></i>Resume Input Daftar Barang</h5>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-bold" id="badge-total-item">0 Uraian</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase tracking-wider">
                        <tr>
                            <th class="ps-4 py-3" style="width: 5%;">No</th>
                            <th class="py-3">Nama Uraian Barang</th>
                            <th class="text-end py-3" style="width: 20%;">Harga Satuan</th>
                            <th class="text-center py-3" style="width: 15%;">Qty</th>
                            <th class="text-end py-3" style="width: 20%;">Subtotal</th>
                            <th class="text-center pe-4 py-3" style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="body-keranjang-bast">
                        <tr id="row-kosong-informasi">
                            <td colspan="6" class="text-center py-5 text-muted bg-light bg-opacity-20">
                                <i class="bi bi-inbox display-6 d-block mb-2 text-opacity-25 text-secondary"></i>
                                <span class="fs-7">Keranjang masih kosong. Cari barang di atas untuk ditambahkan ke resume.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-light d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 border-top rounded-bottom-4">
                <div>
                    <span class="text-muted small d-block text-uppercase tracking-wider fw-bold">Total Nilai Akumulasi BAST</span>
                    <h3 class="m-0 fw-bold text-dark" id="text-grand-total">Rp 0</h3>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold rounded-3" onclick="eksekusiSimpanDraft()"><i class="bi bi-journal-bookmark me-2"></i>Simpan Draft</button>
                    <button type="button" class="btn btn-success px-4 py-2 fw-bold rounded-3 shadow-sm" onclick="bukaModalKonfirmasi()"><i class="bi bi-cloud-check me-2"></i>Ajukan Dokumen</button>
                </div>
            </div>
        </div>

        <!-- DIALOG MODAL KONFIRMASI -->
        <div class="modal fade" id="modalKonfirmasiBAST" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header border-bottom-0 bg-light p-3 px-4 rounded-top-4">
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-patch-question text-warning me-2"></i>Validasi Akhir</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <p class="text-secondary mb-2">Apakah Anda yakin data rincian berkas masuk ini sudah sesuai nota fisik?</p>
                        <h2 class="fw-bold text-primary mb-3" id="modal-text-total-info">Rp 0</h2>
                        <div class="alert alert-warning py-2 border-0 small rounded-3 text-start"><i class="bi bi-info-circle me-1"></i> Transaksi akan diteruskan ke antrean verifikasi Admin Utama dan stok akan terhitung masuk setelah disetujui.</div>
                    </div>
                    <div class="modal-footer border-top-0 bg-light p-3 rounded-bottom-4">
                        <button type="button" class="btn btn-secondary fw-semibold px-3" data-bs-dismiss="modal">Periksa Ulang</button>
                        <button type="button" class="btn btn-success fw-bold px-4 shadow-sm" onclick="submitFormFinal()">Ya, Kirim Sekarang</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#select-barang-lookup').select2({
            placeholder: "-- Cari Nama Barang / ATK Gudang --",
            allowClear: true,
            width: '100%'
        });

        $('#select-barang-lookup').on('change', function() {
            let select = document.getElementById('select-barang-lookup');
            let opt = select.options[select.selectedIndex];
            if (opt && select.value !== "") {
                document.getElementById('input-harga-acuan').value = opt.getAttribute('data-harga') || 0;
            } else {
                document.getElementById('input-harga-acuan').value = '';
            }
        });
    });

    let keranjangBarang = [];

    function tambahKeResume() {
        let select = document.getElementById('select-barang-lookup');
        let idBarang = select.value;
        if (!idBarang) {
            alert('Silakan pilih komoditas barang terlebih dahulu!');
            return;
        }

        let opt = select.options[select.selectedIndex];
        let namaBarang = opt.text;
        let harga = parseFloat(document.getElementById('input-harga-acuan').value) || 0;
        let qty = parseInt(document.getElementById('input-qty-masuk').value) || 0;

        if (qty <= 0) {
            alert('Kuantitas barang masuk minimal 1 item!');
            return;
        }

        let indeksExist = keranjangBarang.findIndex(item => item.id === idBarang);
        if (indeksExist > -1) {
            keranjangBarang[indeksExist].qty += qty;
            keranjangBarang[indeksExist].harga = harga;
        } else {
            keranjangBarang.push({
                id: idBarang,
                name: namaBarang,
                harga: harga,
                qty: qty
            });
        }

        $('#select-barang-lookup').val('').trigger('change');
        document.getElementById('input-qty-masuk').value = "1";

        renderTabelResume();
    }

    function hapusItemResume(id) {
        keranjangBarang = keranjangBarang.filter(item => item.id !== id);
        renderTabelResume();
    }

    function updateQtyIndeks(id, value) {
        let qty = parseInt(value) || 0;
        let item = keranjangBarang.find(item => item.id === id);
        if (item && qty > 0) {
            item.qty = qty;
            renderTabelResume(false);
        }
    }

    function updateHargaIndeks(id, value) {
        let harga = parseFloat(value) || 0;
        let item = keranjangBarang.find(item => item.id === id);
        if (item) {
            item.harga = harga;
            renderTabelResume(false);
        }
    }

    function renderTabelResume(fullRender = true) {
        let tbody = document.getElementById('body-keranjang-bast');
        let grandTotal = 0;

        document.getElementById('badge-total-item').innerText = keranjangBarang.length + " Uraian";

        if (keranjangBarang.length === 0) {
            tbody.innerHTML = `<tr id="row-kosong-informasi"><td colspan="6" class="text-center py-5 text-muted bg-light bg-opacity-20"><i class="bi bi-inbox display-6 d-block mb-2 text-opacity-25 text-secondary"></i><span class="fs-7">Keranjang masih kosong. Cari barang di atas untuk ditambahkan ke resume.</span></td></tr>`;
            document.getElementById('text-grand-total').innerText = "Rp 0";
            return;
        }

        if (fullRender) {
            tbody.innerHTML = "";
            keranjangBarang.forEach((item, index) => {
                let subtotal = item.qty * item.harga;
                grandTotal += subtotal;

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="ps-4 text-muted small">${index + 1}
                        <input type="hidden" name="id_barang_array[]" value="${item.id}">
                    </td>
                    <td class="fw-bold text-dark">${item.name}</td>
                    <td><input type="number" name="harga_array[]" class="form-control input-custom-style text-end fw-bold py-1" value="${item.harga}" onkeyup="updateHargaIndeks('${item.id}', this.value)" onchange="updateHargaIndeks('${item.id}', this.value)"></td>
                    <td><input type="number" name="qty_array[]" class="form-control input-custom-style text-center fw-bold text-primary py-1" value="${item.qty}" min="1" onkeyup="updateQtyIndeks('${item.id}', this.value)" onchange="updateQtyIndeks('${item.id}', this.value)"></td>
                    <td class="text-end fw-bold text-success id-subtotal-${item.id} pe-3">Rp ${subtotal.toLocaleString('id-ID')}</td>
                    <td class="text-center pe-4">
                        <button type="button" class="btn btn-link link-danger p-0 border-0" onclick="hapusItemResume('${item.id}')"><i class="bi bi-trash3 fs-5"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            keranjangBarang.forEach(item => {
                let subtotal = item.qty * item.harga;
                grandTotal += subtotal;
                document.querySelector(`.id-subtotal-${item.id}`).innerText = "Rp " + subtotal.toLocaleString('id-ID');
            });
        }

        document.getElementById('text-grand-total').innerText = "Rp " + grandTotal.toLocaleString('id-ID');
    }

    function eksekusiSimpanDraft() {
        if (keranjangBarang.length === 0) {
            alert('Keranjang resume Anda masih kosong!');
            return;
        }
        document.getElementById('action_type').value = 'draft';
        document.getElementById('formBAST').submit();
    }

    function bukaModalKonfirmasi() {
        if (keranjangBarang.length === 0) {
            alert('Keranjang resume Anda masih kosong!');
            return;
        }
        document.getElementById('action_type').value = 'submit';

        let totalTxt = document.getElementById('text-grand-total').innerText;
        document.getElementById('modal-text-total-info').innerText = totalTxt;

        let myModal = new bootstrap.Modal(document.getElementById('modalKonfirmasiBAST'));
        myModal.show();
    }

    function submitFormFinal() {
        document.getElementById('formBAST').submit();
    }
</script>
@endpush
