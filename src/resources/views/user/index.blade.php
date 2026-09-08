<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 30px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; }
        #batchActionContainer { display: none; }
    </style>
</head>

<body>
    <div class="container">
        <div class="mb-3">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Manajemen User</h3>
                <div>
                    <span id="batchActionContainer" class="me-2">
                        <button class="btn btn-warning" id="btnOpenBatchModal">
                            <i class="fas fa-layer-group"></i> Batch Update Akses (<span id="batchCount">0</span>)
                        </button>
                    </span>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>
            </div>
            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label for="filterAktif" class="form-label fw-bold">Status Akun</label>
                        <select id="filterAktif" class="form-select">
                            <option value="">Semua</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="filterGmail" class="form-label fw-bold">Status Email</label>
                        <select id="filterGmail" class="form-select">
                            <option value="">Semua</option>
                            <option value="1">Terhubung</option>
                            <option value="0">Belum Terhubung</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="filterRole" class="form-label fw-bold">Role User</label>
                        <select id="filterRole" class="form-select">
                            <option value="">Semua Role</option>
                            @foreach ($roles as $id => $namaRole)
                                <option value="{{ $id }}">{{ $namaRole }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="filterBidang" class="form-label fw-bold">Cari Bidang</label>
                        <input type="text" id="filterBidang" class="form-control" placeholder="Ketik nama bidang...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Id</th>
                                <th>Nama Lengkap</th>
                                <th>Aktif</th>
                                <th>Role</th>
                                <th>Bidang</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ADD USER -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_baru" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_baru" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                @foreach ($roles as $id => $namaRole)
                                    <option value="{{ $id }}">{{ $namaRole }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bidang" class="form-label">Bidang</label>
                            <input type="text" class="form-control" id="bidang" name="bidang" required>
                        </div>
                        <div class="mb-3">
                            <label for="isaktif" class="form-label">Status Awal</label>
                            <select class="form-select" id="isaktif" name="isaktif" required>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GMAIL -->
    <div class="modal fade" id="addGmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Perbarui / Tambah Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addGmailForm">
                        <input type="hidden" id="gmail_user_id" name="username_id">
                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="contoh@gmail.com">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="saveGmailBtn">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Manajemen Akses (Single) -->
    <div class="modal fade" id="accessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hak Akses Aplikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="accessForm">
                        <input type="hidden" id="access_user_id" name="user_id">
                        <p class="text-muted small mb-3">Pilih sub-aplikasi yang dapat diakses oleh user ini.</p>
                        <div class="row">
                            @foreach ($daftarMenu as $key => $title)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input page-checkbox" type="checkbox" name="pages[]" value="{{ $key }}" id="check_{{ $key }}">
                                        <label class="form-check-label" for="check_{{ $key }}">{{ $title }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="saveAccessBtn">Simpan Akses</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Manajemen Akses (BATCH) -->
    <div class="modal fade" id="batchAccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Batch Update Akses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="batchAccessForm">
                        <div id="batchUserIdsContainer"></div>
                        <div class="alert alert-warning small">
                            <strong>Perhatian:</strong> Aksi ini akan menimpa pengaturan akses sebelumnya untuk <b id="textBatchCount">0</b> user.
                        </div>
                        <p class="fw-bold mb-3">Pilih akses baru untuk user terpilih:</p>
                        <div class="row">
                            @foreach ($daftarMenu as $key => $title)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="pages[]" value="{{ $key }}" id="batch_check_{{ $key }}">
                                        <label class="form-check-label" for="batch_check_{{ $key }}">{{ $title }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" id="saveBatchAccessBtn">Terapkan Massal</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } });

        $(document).ready(function() {

            function loadUsers() {
                const filterAktif = $('#filterAktif').val();
                const filterGmail = $('#filterGmail').val();
                const filterRole = $('#filterRole').val();
                const filterBidang = $('#filterBidang').val();

                $.ajax({
                    url: @json(route('user.get-users')),
                    type: 'POST',
                    data: {
                        filter_aktif: filterAktif,
                        filter_gmail: filterGmail,
                        filter_role: filterRole,
                        filter_bidang: filterBidang
                    },
                    beforeSend: function() {
                        $('#userTableBody').html('<tr><td colspan="9" class="text-center">Memuat data...</td></tr>');
                    },
                    success: function(response) {
                        $('#userTableBody').html(response);
                        $('#selectAll').prop('checked', false);
                        toggleBatchButton();
                    }
                });
            }

            loadUsers();

            $('#filterAktif, #filterGmail, #filterRole').change(function() {
                loadUsers();
            });

            let typingTimer;
            $('#filterBidang').on('keyup', function() {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(loadUsers, 500);
            });
            $('#filterBidang').on('keydown', function() {
                clearTimeout(typingTimer);
            });

            $('#saveUserBtn').click(function() {
                $.ajax({
                    url: @json(route('user.add-user')),
                    type: 'POST',
                    data: $('#addUserForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#addUserModal').modal('hide');
                            $('#addUserForm')[0].reset();
                            alert(response.message);
                            loadUsers();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

            $(document).on('click', '.toggle-status-btn', function() {
                const userId = $(this).data('id');
                if (confirm('Anda yakin ingin mengubah status user ini?')) {
                    $.ajax({
                        url: @json(route('user.toggle-status')),
                        type: 'POST',
                        data: { id: userId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert(response.message);
                                loadUsers();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        }
                    });
                }
            });

            $('#addGmailModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                $('#gmail_user_id').val(button.data('id'));
            });

            $('#saveGmailBtn').click(function() {
                $.ajax({
                    url: @json(route('user.add-gmail')),
                    type: 'POST',
                    data: $('#addGmailForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#addGmailModal').modal('hide');
                            $('#addGmailForm')[0].reset();
                            alert(response.message);
                            loadUsers();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

            $(document).on('click', '.manage-access-btn', function() {
                const userId = $(this).data('id');
                let pages = $(this).data('pages');
                if (typeof pages === 'string') {
                    try {
                        pages = JSON.parse(pages);
                    } catch (e) {
                        pages = [];
                    }
                }
                if (!Array.isArray(pages)) pages = [];
                $('#access_user_id').val(userId);
                $('.page-checkbox').prop('checked', false);
                pages.forEach(function(page) {
                    $('#check_' + page).prop('checked', true);
                });
            });

            $('#saveAccessBtn').click(function() {
                $.ajax({
                    url: @json(route('user.update-access')),
                    type: 'POST',
                    data: $('#accessForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#accessModal').modal('hide');
                            alert(response.message);
                            loadUsers();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

            function toggleBatchButton() {
                let checkedCount = $('.user-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#batchActionContainer').fadeIn();
                    $('#batchCount').text(checkedCount);
                } else {
                    $('#batchActionContainer').fadeOut();
                }
            }

            $('#selectAll').change(function() {
                $('.user-checkbox').prop('checked', $(this).prop('checked'));
                toggleBatchButton();
            });

            $(document).on('change', '.user-checkbox', function() {
                if ($('.user-checkbox:checked').length == $('.user-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                } else {
                    $('#selectAll').prop('checked', false);
                }
                toggleBatchButton();
            });

            $('#btnOpenBatchModal').click(function() {
                let checkedUsers = [];
                $('#batchUserIdsContainer').empty();

                $('.user-checkbox:checked').each(function() {
                    let uid = $(this).val();
                    checkedUsers.push(uid);
                    $('#batchUserIdsContainer').append('<input type="hidden" name="user_ids[]" value="' + uid + '">');
                });

                $('#textBatchCount').text(checkedUsers.length);
                $('#batchAccessForm input[type="checkbox"]').prop('checked', false);
                $('#batchAccessModal').modal('show');
            });

            $('#saveBatchAccessBtn').click(function() {
                $.ajax({
                    url: @json(route('user.batch-update-access')),
                    type: 'POST',
                    data: $('#batchAccessForm').serialize(),
                    dataType: 'json',
                    beforeSend: function() {
                        $('#saveBatchAccessBtn').text('Menyimpan...').prop('disabled', true);
                    },
                    success: function(response) {
                        $('#saveBatchAccessBtn').text('Terapkan Massal').prop('disabled', false);
                        if (response.status === 'success') {
                            $('#batchAccessModal').modal('hide');
                            alert(response.message);
                            loadUsers();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

        });
    </script>
</body>

</html>
