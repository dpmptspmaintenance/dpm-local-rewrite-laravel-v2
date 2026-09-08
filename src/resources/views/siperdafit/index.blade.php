<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Permintaan Data dan Fitur</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background-color: #f4f5f7;
            overflow-x: auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .kanban-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 20px;
            align-items: flex-start;
            min-height: 70vh;
        }

        .kanban-col-wrapper {
            flex: 1;
            min-width: 320px;
            transition: all 0.3s ease;
        }

        .kanban-col {
            background-color: #ebecf0;
            border-radius: 8px;
            padding: 10px;
            height: 100%;
            min-height: 75vh;
            display: flex;
            flex-direction: column;
        }

        .kanban-header {
            font-weight: 700;
            padding: 10px 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .kanban-list {
            flex-grow: 1;
            min-height: 100px;
            padding-bottom: 50px;
        }

        .kanban-card {
            background: white;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            border-left: 4px solid transparent;
            cursor: default;
            user-select: none;
            position: relative;
        }

        .kanban-card.is-draggable {
            cursor: grab;
        }

        .kanban-card.is-draggable:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: 0.4;
            background: #c8ebfb;
            border: 2px dashed #0d6efd;
        }

        .sortable-drag {
            opacity: 1;
            transform: rotate(2deg);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19);
        }

        .header-belum_dikerjakan,
        .btn-toggle-belum_dikerjakan.active {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .header-approved,
        .btn-toggle-approved.active {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
            color: white;
        }

        .header-pending,
        .btn-toggle-pending.active {
            background-color: #e0a800;
            border-color: #e0a800;
            color: white;
        }

        .header-pengerjaan,
        .btn-toggle-pengerjaan.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .header-selesai,
        .btn-toggle-selesai.active {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }

        .header-rejected,
        .btn-toggle-rejected.active {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .btn-toggle-col {
            border: 1px solid #ccc;
            background-color: white;
            color: #666;
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 20px;
            transition: all 0.2s;
            opacity: 0.7;
            white-space: nowrap;
        }

        .btn-toggle-col.active {
            opacity: 1;
            font-weight: bold;
            border: 1px solid transparent;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .status-info {
            font-size: 0.7rem;
            color: #555;
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px dashed #eee;
        }

        .pending-reason-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 8px;
        }

        .rejection-reason-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 8px;
        }

        .star-rating i {
            font-size: 0.8rem;
            margin-right: 2px;
            text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.1);
        }

        /* File Attachment Style */
        .file-attachment {
            font-size: 0.75rem;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 5px;
            margin-top: 5px;
            border: 1px solid #dee2e6;
            text-decoration: none;
            color: #333;
        }

        .file-attachment:hover {
            background: #e9ecef;
        }

        ::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-3">

        <div class="row g-3 align-items-center mb-3">
            <div class="col-md-4">
                <div class="d-flex align-items-center">
                    <a href="javascript:history.back()" class="btn btn-light btn-sm me-3 border shadow-sm rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <h5 class="mb-0 fw-bold text-dark text-truncate">Sistem Informasi Permintaan Data dan Fitur</h5>
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                    <div class="input-group input-group-sm shadow-sm" style="width: 200px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input type="text" id="filterKeyword" class="form-control border-start-0" placeholder="Cari...">
                    </div>
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <input type="radio" class="btn-check btn-filter-type" name="filterType" id="typeAll" value="" checked>
                        <label class="btn btn-outline-secondary" for="typeAll">Semua</label>
                        <input type="radio" class="btn-check btn-filter-type" name="filterType" id="typeFitur" value="penambahan_fitur">
                        <label class="btn btn-outline-primary" for="typeFitur">Fitur</label>
                        <input type="radio" class="btn-check btn-filter-type" name="filterType" id="typeData" value="permintaan_data">
                        <label class="btn btn-outline-success" for="typeData">Data</label>
                    </div>
                    <button class="btn btn-primary btn-sm shadow-sm" onclick="openModalCreate()">
                        <i class="fas fa-plus me-1"></i> Buat
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4 overflow-auto pb-2 border-bottom">
            <span class="small fw-bold align-self-center me-1 text-muted"><i class="fas fa-eye me-1"></i>Tampilan:</span>
            <button class="btn-toggle-col btn-toggle-belum_dikerjakan active" data-target="belum_dikerjakan"><i class="fas fa-check-circle me-1"></i>Request <span class="ms-1 fw-bold small" id="btn-count-belum_dikerjakan">(0)</span></button>
            <button class="btn-toggle-col btn-toggle-approved active" data-target="approved"><i class="fas fa-check-circle me-1"></i>Approved <span class="ms-1 fw-bold small" id="btn-count-approved">(0)</span></button>
            <button class="btn-toggle-col btn-toggle-pending" data-target="pending"><i class="fas fa-check-circle me-1"></i>Pending <span class="ms-1 fw-bold small" id="btn-count-pending">(0)</span></button>
            <button class="btn-toggle-col btn-toggle-pengerjaan active" data-target="pengerjaan"><i class="fas fa-check-circle me-1"></i>On Progress <span class="ms-1 fw-bold small" id="btn-count-pengerjaan">(0)</span></button>
            <button class="btn-toggle-col btn-toggle-selesai" data-target="selesai"><i class="fas fa-check-circle me-1"></i>Done <span class="ms-1 fw-bold small" id="btn-count-selesai">(0)</span></button>
            <button class="btn-toggle-col btn-toggle-rejected" data-target="rejected"><i class="fas fa-check-circle me-1"></i>Rejected <span class="ms-1 fw-bold small" id="btn-count-rejected">(0)</span></button>
        </div>

        <div class="kanban-container">
            <div class="kanban-col-wrapper" id="col-wrapper-belum_dikerjakan">
                <div class="kanban-col">
                    <div class="kanban-header header-belum_dikerjakan"><span>Request</span><span class="badge bg-white text-dark rounded-pill shadow-sm" id="count-belum_dikerjakan">0</span></div>
                    <div id="list-belum_dikerjakan" class="kanban-list" data-status="belum_dikerjakan"></div>
                </div>
            </div>
            <div class="kanban-col-wrapper" id="col-wrapper-approved">
                <div class="kanban-col">
                    <div class="kanban-header header-approved"><span>Approved</span><span class="badge bg-white text-dark rounded-pill shadow-sm" id="count-approved">0</span></div>
                    <div id="list-approved" class="kanban-list" data-status="approved"></div>
                </div>
            </div>
            <div class="kanban-col-wrapper" id="col-wrapper-pending" style="display:none;">
                <div class="kanban-col">
                    <div class="kanban-header header-pending"><span>Pending</span><span class="badge bg-white text-dark rounded-pill shadow-sm" id="count-pending">0</span></div>
                    <div id="list-pending" class="kanban-list" data-status="pending"></div>
                </div>
            </div>
            <div class="kanban-col-wrapper" id="col-wrapper-pengerjaan">
                <div class="kanban-col">
                    <div class="kanban-header header-pengerjaan"><span>On Progress</span><span class="badge bg-white text-dark rounded-pill shadow-sm" id="count-pengerjaan">0</span></div>
                    <div id="list-pengerjaan" class="kanban-list" data-status="pengerjaan"></div>
                </div>
            </div>
            <div class="kanban-col-wrapper" id="col-wrapper-selesai" style="display:none;">
                <div class="kanban-col">
                    <div class="kanban-header header-selesai"><span>Done</span><span class="badge bg-white text-dark rounded-pill shadow-sm" id="count-selesai">0</span></div>
                    <div id="list-selesai" class="kanban-list" data-status="selesai"></div>
                </div>
            </div>
            <div class="kanban-col-wrapper" id="col-wrapper-rejected" style="display:none;">
                <div class="kanban-col">
                    <div class="kanban-header header-rejected"><span>Rejected</span><span class="badge bg-white text-dark rounded-pill shadow-sm" id="count-rejected">0</span></div>
                    <div id="list-rejected" class="kanban-list" data-status="rejected"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Form Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="requestForm" enctype="multipart/form-data"> <input type="hidden" id="taskId" name="id">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">JUDUL REQUEST</label>
                            <input type="text" class="form-control" name="title" id="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">DESKRIPSI</label>
                            <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">TIPE</label>
                            <select class="form-select" name="request_type" id="request_type">
                                <option value="penambahan_fitur">Penambahan Fitur</option>
                                <option value="permintaan_data">Permintaan Data</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">UPLOAD FILE (Bisa Banyak)</label>
                            <input type="file" class="form-control" name="files[]" multiple>
                            <div id="fileListEdit" class="mt-2"></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pendingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" style="font-size:1rem;">Alasan Pending</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pendingTaskId">
                    <div class="mb-3">
                        <label class="form-label small">Kenapa dipending?</label>
                        <textarea class="form-control" id="pendingReasonInput" rows="3" required></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="btnCancelPending">Batal</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnSavePending">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" style="font-size:1rem;">Alasan Penolakan</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rejectTaskId">
                    <div class="mb-3">
                        <label class="form-label small">Kenapa ditolak?</label>
                        <textarea class="form-control" id="rejectReasonInput" rows="3" required></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="btnCancelReject">Batal</button>
                        <button type="button" class="btn btn-danger btn-sm" id="btnSaveReject">Tolak</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="priorityModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" style="font-size:1rem;">Set Prioritas</h5>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" id="priorityTaskId">
                    <p class="small mb-3">Tentukan prioritas pengerjaan:</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-warning btn-sm" onclick="submitPriority(1)"><i class="fas fa-star"></i> 1 (Normal)</button>
                        <button class="btn btn-outline-warning btn-sm" onclick="submitPriority(2)"><i class="fas fa-star"></i><i class="fas fa-star"></i> 2 (Medium)</button>
                        <button class="btn btn-outline-warning btn-sm" onclick="submitPriority(3)"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i> 3 (High)</button>
                    </div>
                    <button class="btn btn-link btn-sm text-muted mt-3" id="btnCancelPriority">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

    <script>
        const BASE_URL = "{{ url('/siperdafit') }}";
        const LIST_URL = "{{ route('siperdafit.list') }}";
        const STORE_URL = "{{ route('siperdafit.store') }}";
        const UPDATE_STATUS_URL = "{{ route('siperdafit.update-status') }}";
        const UPDATE_PRIORITY_URL = "{{ route('siperdafit.update-priority') }}";

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) }
        });

        let myModal, pendingModal, priorityModal, rejectModal;
        let userRole = 0;
        let isPriorityDrag = false;

        $(document).ready(function() {
            myModal = new bootstrap.Modal(document.getElementById('requestModal'));
            pendingModal = new bootstrap.Modal(document.getElementById('pendingModal'));
            rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            priorityModal = new bootstrap.Modal(document.getElementById('priorityModal'));

            loadRequests();
            $('#filterKeyword').on('keyup', function() {
                loadRequests();
            });
            $('.btn-filter-type').change(function() {
                loadRequests();
            });

            // CREATE / UPDATE WITH FILE UPLOAD
            $('#requestForm').submit(function(e) {
                e.preventDefault();
                let id = $('#taskId').val();
                let url = id ? `${BASE_URL}/${id}/update` : STORE_URL;

                // Gunakan FormData untuk file upload
                let formData = new FormData(this);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false, // Wajib untuk FormData
                    processData: false, // Wajib untuk FormData
                    success: function(res) {
                        if (res.status === 'success') {
                            myModal.hide();
                            loadRequests();
                        } else {
                            alert(res.message);
                        }
                    }
                });
            });

            // MODAL HANDLERS
            $('#btnSavePending').click(function() {
                let id = $('#pendingTaskId').val();
                let reason = $('#pendingReasonInput').val();
                if (!reason) {
                    alert('Harap isi alasan!');
                    return;
                }
                updateStatus(id, 'pending', reason, 0);
                pendingModal.hide();
            });
            $('#btnCancelPending').click(function() {
                pendingModal.hide();
                loadRequests();
            });

            $('#btnSaveReject').click(function() {
                let id = $('#rejectTaskId').val();
                let reason = $('#rejectReasonInput').val();
                if (!reason) {
                    alert('Harap isi alasan!');
                    return;
                }
                updateStatus(id, 'rejected', reason, 0);
                rejectModal.hide();
            });
            $('#btnCancelReject').click(function() {
                rejectModal.hide();
                loadRequests();
            });

            $('#btnCancelPriority').click(function() {
                priorityModal.hide();
                loadRequests();
            });

            $('.btn-toggle-col').click(function() {
                let col = $(this).data('target');
                $(this).toggleClass('active');
                if ($(this).hasClass('active')) $('#col-wrapper-' + col).fadeIn();
                else $('#col-wrapper-' + col).fadeOut();
            });
        });

        function submitPriority(level) {
            let id = $('#priorityTaskId').val();
            if (isPriorityDrag) {
                updateStatus(id, 'approved', '', level);
            } else {
                updatePriorityOnly(id, level);
            }
            priorityModal.hide();
        }

        function updatePriorityOnly(id, level) {
            $.post(UPDATE_PRIORITY_URL, {
                id: id,
                priority: level
            }, function(res) {
                if (res.status === 'success') loadRequests();
                else alert('Gagal update prioritas');
            });
        }

        function openPriorityManual(id) {
            isPriorityDrag = false;
            $('#priorityTaskId').val(id);
            priorityModal.show();
        }

        function loadRequests() {
            let filterType = $('input[name="filterType"]:checked').val();

            $.get(LIST_URL, {
                keyword: $('#filterKeyword').val(),
                type: filterType
            }, function(res) {
                $('.kanban-list').html('');
                $('.count-badge').text('0');

                let currentUserId = res.user_session ? res.user_session.id : 0;
                userRole = res.user_session ? res.user_session.role : 0;
                let counts = {
                    belum_dikerjakan: 0,
                    approved: 0,
                    pending: 0,
                    pengerjaan: 0,
                    selesai: 0,
                    rejected: 0
                };

                if (res.data && res.data.length > 0) {
                    res.data.forEach(item => {
                        let statusKey = item.status.toLowerCase();
                        if (counts[statusKey] !== undefined) counts[statusKey]++;

                        let userName = item.username_pemohon || 'User';
                        let userInitial = userName.substring(0, 2).toUpperCase();
                        let createdTime = item.created_at.substring(0, 16);

                        // STARS
                        let starHtml = '';
                        let priorityVal = parseInt(item.priority);
                        if (priorityVal > 0 && statusKey !== 'belum_dikerjakan' && statusKey !== 'rejected') {
                            let stars = '';
                            for (let i = 0; i < priorityVal; i++) {
                                stars += '<i class="fas fa-star text-warning"></i>';
                            }
                            starHtml = `<div class="star-rating mb-1">${stars}</div>`;
                        }

                        // FILES
                        let fileHtml = '';
                        if (item.files && item.files.length > 0) {
                            fileHtml = '<div class="mt-2 border-top pt-1">';
                            item.files.forEach(f => {
                                fileHtml += `<a href="${f.path}" target="_blank" class="file-attachment"><i class="fas fa-paperclip"></i> ${f.name}</a>`;
                            });
                            fileHtml += '</div>';
                        }

                        // STATUS INFO
                        let statusInfoHtml = '';
                        let approverName = item.username_approver || 'IT Admin';
                        let acceptorName = item.username_penerima || 'IT Admin';
                        let rejectorName = item.username_penolak || 'IT Admin';

                        if (statusKey === 'pending' && item.pending_reason) {
                            statusInfoHtml += `<div class="pending-reason-box"><i class="fas fa-info-circle me-1"></i> ${item.pending_reason}</div>`;
                        } else if (statusKey === 'rejected' && item.rejection_reason) {
                            statusInfoHtml += `
                            <div class="rejection-reason-box">
                                <strong class="d-block mb-1">Ditolak oleh ${rejectorName}:</strong>
                                <i class="fas fa-times-circle me-1"></i> ${item.rejection_reason}
                            </div>`;
                        } else if (statusKey === 'approved' && item.approved_at) {
                            let appTime = item.approved_at.substring(0, 16);
                            statusInfoHtml += `
                            <div class="status-info text-info"><i class="fas fa-thumbs-up me-1"></i> Approved by ${approverName}<br><span class="ms-3 text-muted">${appTime}</span></div>`;
                        } else if (statusKey === 'pengerjaan' && item.accepted_at) {
                            let acceptTime = item.accepted_at.substring(0, 16);
                            statusInfoHtml += `
                            <div class="status-info text-primary"><i class="fas fa-play me-1"></i> Accepted by ${acceptorName}<br><span class="ms-3 text-muted">${acceptTime}</span></div>`;
                        } else if (statusKey === 'selesai' && item.finish_time) {
                            let finishTime = item.finish_time.substring(0, 16);
                            statusInfoHtml += `
                            <div class="status-info text-success"><i class="fas fa-check-double me-1"></i> Finished by ${acceptorName}<br><span class="ms-3 text-muted">${finishTime}</span></div>`;
                        }

                        let typeLabel = item.request_type == 'penambahan_fitur' ? 'Fitur' : 'Data';
                        let typeColor = item.request_type == 'penambahan_fitur' ? 'text-primary' : 'text-success';
                        let draggableClass = (userRole == 1) ? 'is-draggable' : '';

                        let buttons = '';
                        let userButtons = '';
                        if (item.user_id == currentUserId) {
                            userButtons = `
                            <button class="btn btn-sm py-0 px-2 text-muted" onclick="editRequest(${item.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm py-0 px-2 text-danger" onclick="deleteRequest(${item.id})"><i class="fas fa-trash"></i></button>
                        `;
                        }

                        let priorityBtn = '';
                        if (userRole == 1 && statusKey !== 'belum_dikerjakan' && statusKey !== 'rejected') {
                            priorityBtn = `
                             <button class="btn btn-sm py-0 px-2 text-warning" onclick="openPriorityManual(${item.id})" title="Ubah Prioritas"><i class="fas fa-star"></i></button>
                        `;
                        }

                        if (userButtons || priorityBtn) {
                            buttons = `<div class="d-flex justify-content-end mt-2 pt-1 border-top border-light">${priorityBtn} ${userButtons}</div>`;
                        }

                        let cardHtml = `
                        <div class="kanban-card ${draggableClass}" data-id="${item.id}" style="border-left: 3px solid ${item.request_type == 'penambahan_fitur' ? '#0d6efd' : '#198754'}">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge bg-light ${typeColor} border">${typeLabel}</span>
                                <small class="text-muted" style="font-size:0.7rem"><i class="far fa-clock me-1"></i>${createdTime}</small>
                            </div>
                            ${starHtml}
                            <div class="fw-bold mb-1 text-dark">${item.title}</div>
                            <small class="text-muted d-block mb-2" style="white-space: pre-wrap;">${item.description}</small>
                            <div class="d-flex align-items-center mb-1">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:20px; height:20px; font-size:9px;">${userInitial}</div>
                                <small class="ms-2 text-secondary fw-bold" style="font-size:0.75rem">${userName}</small>
                            </div>
                            ${fileHtml}
                            ${statusInfoHtml}
                            ${buttons}
                        </div>
                    `;
                        $(`#list-${statusKey}`).append(cardHtml);
                    });
                }
                updateBadges();
                initSortable();
            });
        }

        function initSortable() {
            ['list-belum_dikerjakan', 'list-approved', 'list-pending', 'list-pengerjaan', 'list-selesai', 'list-rejected'].forEach(id => {
                let el = document.getElementById(id);
                let canMove = (userRole == 1);
                new Sortable(el, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    disabled: !canMove,
                    onEnd: function(evt) {
                        if (evt.to !== evt.from) {
                            let itemEl = evt.item;
                            let newStatus = evt.to.getAttribute('data-status');
                            let taskId = itemEl.getAttribute('data-id');

                            if (newStatus === 'pending') {
                                $('#pendingTaskId').val(taskId);
                                $('#pendingReasonInput').val('');
                                pendingModal.show();
                            } else if (newStatus === 'rejected') {
                                $('#rejectTaskId').val(taskId);
                                $('#rejectReasonInput').val('');
                                rejectModal.show();
                            } else if (newStatus === 'approved') {
                                isPriorityDrag = true;
                                $('#priorityTaskId').val(taskId);
                                priorityModal.show();
                            } else {
                                updateStatus(taskId, newStatus);
                                updateBadges();
                            }
                        }
                    }
                });
            });
        }

        function updateBadges() {
            ['belum_dikerjakan', 'approved', 'pending', 'pengerjaan', 'selesai', 'rejected'].forEach(status => {
                let count = $(`#list-${status} .kanban-card`).length;
                $(`#count-${status}`).text(count);
                // UPDATE COUNTER TOMBOL TOGGLE
                $(`#btn-count-${status}`).text(`(${count})`);
            });
        }

        function openModalCreate() {
            $('#requestForm')[0].reset();
            $('#taskId').val('');
            $('#fileListEdit').html('');
            $('#modalTitle').text('Buat Request Baru');
            myModal.show();
        }

        function editRequest(id) {
            $.get(`${BASE_URL}/${id}/detail`, function(res) {
                $('#taskId').val(res.id);
                $('#title').val(res.title);
                $('#description').val(res.description);
                $('#request_type').val(res.request_type);

                // Show existing files
                let fileList = '';
                if (res.files && res.files.length > 0) {
                    res.files.forEach(f => {
                        fileList += `<div class="badge bg-light text-dark border me-1 mb-1"><i class="fas fa-file"></i> ${f.file_name}</div>`;
                    });
                }
                $('#fileListEdit').html(fileList);

                $('#modalTitle').text('Edit Request');
                myModal.show();
            });
        }

        function deleteRequest(id) {
            if (confirm('Yakin hapus?')) {
                $.post(`${BASE_URL}/${id}/delete`, function(res) {
                    if (res.status === 'success') loadRequests();
                    else alert(res.message);
                });
            }
        }

        function updateStatus(id, status, reason = '', priority = 0) {
            $.post(UPDATE_STATUS_URL, {
                id: id,
                status: status,
                reason: reason,
                priority: priority
            }, function(res) {
                if (res.status !== 'success') {
                    alert('Gagal: ' + res.message);
                    loadRequests();
                } else {
                    loadRequests();
                }
            });
        }
    </script>
</body>

</html>
