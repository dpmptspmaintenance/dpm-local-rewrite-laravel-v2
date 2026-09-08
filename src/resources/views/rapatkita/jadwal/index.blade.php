<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduling — RapatKita</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        :root { --bs-success-rgb: 71, 222, 152 !important; }
        html, body { font-family: "Moderustic", sans-serif; height: 100%; width: 100%; }
        .btn-info.text-light:hover, .btn-info.text-light:focus { background: #000; }
        table, tbody, td, tfoot, th, thead, tr { border-color: #ededed !important; border-style: solid; border-width: 1px !important; }
        @media only screen and (max-width: 600px) { #calendar { min-height: 100vh; } }
        #loading-overlay { transition: opacity 0.3s ease; }
    </style>
</head>

<body>
    <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255,255,255,0.8); z-index: 9999; display: flex; justify-content: center; align-items: center;">
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3 class="mt-3 text-dark">Memuat Jadwal...</h3>
        </div>
    </div>

    @include('rapatkita.partials.navbar')

    <div class="container" id="page-container">
        <div class="row">
            <div class="col-lg-12 py-3">
                <div class="text-start my-3">
                    <button id="add-schedule" class="btn btn-success" style="display: none;">Tambah Jadwal</button>
                </div>
                <div id="calendar" class="bg-light p-3" style="max-height: 100vh;"></div>
            </div>
            <div class="col-lg-12 py-3" id="schedule-form-container">
                <div class="rounded-bottom shadow bg-light">
                    <div class="rounded-top bg-primary text-light p-2">
                        <h5 class="card-title">Form Jadwal</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="container-fluid">
                            <form action="{{ route('rapatkita.jadwal.store') }}" class="row" method="post" id="schedule-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ old('id') }}">
                                <div class="form-group mb-2 col-12">
                                    <label for="title" class="control-label">Nama Kegiatan</label>
                                    <input type="text" class="form-control" name="title" id="title" placeholder="Jangan ada petik untuk mengisi input ini" value="{{ old('title') }}" required>
                                </div>
                                <div class="form-group mb-2 col-12">
                                    <label for="pelaksana" class="control-label">Pelaksana</label>
                                    <select name="pelaksana" id="pelaksana" class="form-select" required>
                                        <option value="">Pilih Pelaksana</option>
                                        @foreach (['Bidang 1', 'Bidang 2', 'Bidang 3', 'Sekretariat', 'Simonev', 'Bidang PM'] as $opt)
                                            <option value="{{ $opt }}" @selected(old('pelaksana') === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-2 col-12">
                                    <label for="dihadiri" class="control-label">Dihadiri Oleh</label>
                                    <select name="dihadiri" id="dihadiri" class="form-select" required>
                                        <option value="">Pilih Siapa Yang Menghadiri Kegiatan</option>
                                        <option value="Internal" @selected(old('dihadiri') === 'Internal')>Internal</option>
                                        <option value="kadin" @selected(old('dihadiri') === 'kadin')>Kepala Dinas</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2 col-12">
                                    <label for="dispo" class="control-label">Disposisi</label>
                                    <input type="text" class="form-control" name="dispo" id="dispo" value="{{ old('dispo') }}">
                                </div>
                                <div class="form-group mb-2 col-12" id="pilih-lokasi-container">
                                    <label for="pilih-lokasi" class="control-label">Pilih lokasi</label>
                                    <select name="pilih-lokasi" id="pilih-lokasi" class="form-select">
                                        <option value="">Pilih Lokasi</option>
                                        <option value="Masukan Lokasi Manual" @selected(old('pilih-lokasi') === 'Masukan Lokasi Manual')>Masukan Lokasi Manual</option>
                                        @foreach (['Command Room', 'Ruang Rapat Sebelah PM', 'Ruang Rapat Cantik', 'Cafe Investasi', 'Aula Belakang'] as $opt)
                                            <option value="{{ $opt }}" @selected(old('pilih-lokasi') === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="lokasi-container" class="form-group mb-2 col-12" style="display: none;">
                                    <label for="lokasi" class="control-label">Lokasi</label>
                                    <input type="text" class="form-control" name="lokasi" id="lokasi" value="{{ old('lokasi') }}">
                                </div>
                                <div class="form-group mb-2 col-12">
                                    <label for="description" class="control-label">Deskripsi Kegiatan</label>
                                    <textarea rows="3" class="form-control" name="description" id="description" placeholder="Jangan ada petik untuk mengisi input ini">{{ old('description') }}</textarea>
                                </div>
                                <div class="form-group mb-2 col-12">
                                    <label for="konfirmasi" class="control-label">Apakah Kegiatan ini Lanjutan Dari Kegiatan Sebelumnya?</label>
                                    <select name="konfirmasi" id="konfirmasi" class="form-select" required>
                                        <option value="tidak" @selected(old('konfirmasi', 'tidak') === 'tidak')>Tidak</option>
                                        <option value="iya" @selected(old('konfirmasi') === 'iya')>Iya</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2 col-12" id="rapat-sebelumnya-container" style="display: none;">
                                    <label for="rapat_sebelumnya" class="control-label">Pilih Rapat Sebelumnya</label>
                                    <select name="rapat_sebelumnya" id="rapat_sebelumnya" class="form-select">
                                        <option value="">Pilih Rapat Sebelumnya</option>
                                        @foreach ($rapatSebelumnya as $rapat)
                                            <option value="{{ $rapat->id }}" @selected((string) old('rapat_sebelumnya') === (string) $rapat->id)>{{ $rapat->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-2 col-6">
                                    <label for="hari" class="control-label">Tanggal</label>
                                    <input type="date" class="form-control" name="hari" id="hari" value="{{ old('hari') }}" required>
                                </div>
                                <div class="form-group mb-2 col-6">
                                    <label for="jam_mulai" class="control-label">Jam Mulai</label>
                                    <input type="time" class="form-control" name="jam_mulai" id="jam_mulai" value="{{ old('jam_mulai') }}" required>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-footer p-2">
                        <div class="text-center">
                            <button class="btn btn-primary" type="submit" form="schedule-form"><i class="fa fa-save"></i> Simpan</button>
                            <button class="btn btn-default border" type="reset" form="schedule-form"><i class="fa fa-reset"></i> Batal</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="text-center text-black fw-semibold" style="font-size: 18px;">
                    © DPMPTSP RapatKita 2.1.1 2024
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" data-bs-backdrop="static" id="event-details-modal">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between">
                    <div><h5 class="modal-title">Detail Jadwal</h5></div>
                    <div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <dl>
                            <dt class="text-muted">Judul</dt> <dd id="title" class="fw-bold fs-4"></dd>
                            <dt class="text-muted">Lokasi</dt> <dd id="lokasi" class=""></dd>
                            <dt class="text-muted">Disposisi</dt> <dd id="dispo" class=""></dd>
                            <dt class="text-muted">Pelaksana</dt> <dd id="pelaksana" class=""></dd>
                            <dt class="text-muted">Deskripsi</dt> <dd id="description" class=""></dd>
                            <dt class="text-muted">Jam Mulai</dt>
                            <div class="d-flex gap-2">
                                <dd id="start" class=""></dd> <p>-</p> <dd class="">Selesai</dd>
                            </div>
                            <dt class="text-muted">Dibuat</dt> <dd id="cdate" class=""></dd>
                        </dl>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-end">
                        <button type="button" class="btn btn-primary" id="edit" data-id="" style="display:none;">Ubah</button>
                        <button type="button" class="btn btn-danger" id="delete" data-id="" style="display:none;">Hapus</button>
                        <button type="button" class="btn btn-success" id="notulen" data-id="">Buat Notulen</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="delete-form" method="post" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</body>
<script>
    var scheds = @json($schedRes);
    var calendar;
    if (typeof FullCalendar === 'undefined') {
        document.getElementById('loading-overlay').innerHTML = '<h3 class="text-danger">Gagal memuat kalender. Periksa koneksi lalu muat ulang halaman.</h3>';
        throw new Error('FullCalendar gagal dimuat');
    }
    var Calendar = FullCalendar.Calendar;
    var events = [];
    var userBidang = @json($userBidang);

    $(function() {
        $('#loading-overlay').show();
        if (!!scheds) {
            Object.keys(scheds).map(k => {
                var row = scheds[k]
                events.push({
                    id: row.id,
                    title: `${row.title} - ${row.pelaksana}`,
                    start: row.start_datetime,
                    end: row.end_datetime
                });
            })
        }
        var calendarEl = document.getElementById('calendar');
        calendar = new Calendar(calendarEl, {
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,dayGridWeek,listDay' },
            eventDidMount: function(info) { $('#loading-overlay').fadeOut(); },
            locale: 'id',
            selectable: true,
            editable: false,
            events: events,
            dayMaxEventRows: true,
            dayCellDidMount: function(info) {
                const day = info.date.getDay();
                if (day === 6 || day === 0) info.el.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
            },
            dateClick: function(info) {
                document.getElementById('page-container').scrollIntoView({ behavior: 'smooth' });
                calendar.changeView('listDay', info.date);
                $('#add-schedule').show().off('click').on('click', function() {
                    $('#hari').val(info.dateStr);
                    $('html, body').animate({ scrollTop: $("#schedule-form").offset().top }, 500);
                });
            },
            eventClick: function(info) {
                var _details = $('#event-details-modal');
                var id = info.event.id;
                if (!!scheds[id]) {
                    _details.find('#title').text(scheds[id].title);
                    _details.find('#lokasi').text(scheds[id].lokasi);
                    _details.find('#dispo').text(scheds[id].dispo);
                    _details.find('#dihadiri').text(scheds[id].dihadiri);
                    _details.find('#pelaksana').text(scheds[id].pelaksana);
                    _details.find('#description').text(scheds[id].description);
                    _details.find('#start').text(scheds[id].sdate);
                    _details.find('#cdate').text(scheds[id].cdate);

                    var $notulenBtn = _details.find('#notulen');
                    if (scheds[id].has_notulen) $notulenBtn.hide();
                    else $notulenBtn.text('Buat Notulen').attr('data-id', id).show();

                    _details.find('#edit').text('Ubah').attr('data-id', id);
                    _details.find('#delete').text('Hapus').attr('data-id', id);

                    var eventBidang = scheds[id].bidang_pembuat_jadwal;
                    if (userBidang === eventBidang || userBidang === 'admin') $('#edit, #delete').show();
                    else $('#edit, #delete').hide();

                    new bootstrap.Modal(_details[0]).show();
                }
            }
        });

        calendar.render();
        setTimeout(function() { $('#loading-overlay').fadeOut(); }, 2000);

        $('#add-schedule').on('click', function() {
            $('html, body').animate({ scrollTop: $("#schedule-form-container").offset().top }, 500);
        });

        $('#schedule-form').on('reset', function() {
            $(this).find('input:hidden').val('');
            $(this).find('input:visible').first().focus();
        });

        $('#edit').click(function() {
            var id = $(this).attr('data-id');
            if (!!scheds[id]) {
                var _form = $('#schedule-form');
                var data = scheds[id];
                _form.trigger('reset');
                _form.find('[name="id"]').val(id);
                _form.find('[name="title"]').val(data.title);
                _form.find('[name="dispo"]').val(data.dispo);
                _form.find('[name="dihadiri"]').val(data.dihadiri);
                _form.find('[name="pelaksana"]').val(data.pelaksana);
                _form.find('[name="description"]').val(data.description);

                var startDate = new Date(data.start_datetime);
                _form.find('[name="hari"]').val(startDate.toLocaleDateString('en-CA'));
                _form.find('[name="jam_mulai"]').val(startDate.getHours().toString().padStart(2, '0') + ':' + startDate.getMinutes().toString().padStart(2, '0'));

                var predefinedLocations = ["Command Room", "Ruang Rapat Sebelah PM", "Ruang Rapat Cantik", "Cafe Investasi", "Aula Belakang"];
                if (predefinedLocations.includes(data.lokasi)) {
                    _form.find('[name="pilih-lokasi"]').val(data.lokasi);
                    $('#lokasi-container').hide();
                } else {
                    _form.find('[name="pilih-lokasi"]').val("Masukan Lokasi Manual");
                    _form.find('[name="lokasi"]').val(data.lokasi);
                    $('#lokasi-container').show();
                }

                if (data.id_rapat_sebelumnya && data.id_rapat_sebelumnya !== "") {
                    _form.find('[name="konfirmasi"]').val("iya");
                    $('#rapat-sebelumnya-container').show();
                    _form.find('[name="rapat_sebelumnya"]').val(data.id_rapat_sebelumnya);
                } else {
                    _form.find('[name="konfirmasi"]').val("tidak");
                    $('#rapat-sebelumnya-container').hide();
                }

                bootstrap.Modal.getInstance($('#event-details-modal')[0])?.hide();
                _form.find('[name="title"]').focus();
                $('html, body').animate({ scrollTop: $("#schedule-form-container").offset().top }, 500);
            }
        });

        $('#notulen').click(function() {
            var id = $(this).attr('data-id');
            if (!!scheds[id]) {
                window.location.href = '{{ url("rapatkita/jadwal") }}/' + id + '/notulen';
            }
        });

        $('#delete').click(function() {
            var id = $(this).attr('data-id');
            if (!!scheds[id] && confirm("Are you sure to delete this scheduled event?")) {
                var form = document.getElementById('delete-form');
                form.action = '{{ url("rapatkita/jadwal") }}/' + id;
                form.submit();
            }
        });
    });

    $('html, body').animate({ scrollTop: $("#calendar").offset().top }, 500);

    document.addEventListener("DOMContentLoaded", function() {
        const konf = document.getElementById("konfirmasi");
        const prevCont = document.getElementById("rapat-sebelumnya-container");
        konf.addEventListener("change", function() { prevCont.style.display = (konf.value === "iya") ? "block" : "none"; });

        const locSel = document.getElementById("pilih-lokasi");
        const locCont = document.getElementById("lokasi-container");
        locSel.addEventListener("change", function() { locCont.style.display = (locSel.value === "Masukan Lokasi Manual") ? "block" : "none"; });

        @if (old('pilih-lokasi') === 'Masukan Lokasi Manual')
            locCont.style.display = "block";
        @endif
        @if (old('konfirmasi') === 'iya')
            prevCont.style.display = "block";
        @endif
    });

    @if (session('success'))
        swal("Berhasil!", @json(session('success')), "success");
    @endif
    @if (session('error'))
        swal("Error!", @json(session('error')), "error");
    @endif
    @if ($errors->any())
        swal("Error!", @json($errors->first()), "error");
    @endif
</script>
</html>
