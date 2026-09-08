<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Notulen — RapatKita</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/ge67fbf36imy0bwu1ac41vl4c12ql09g2quh3g3ldmlu7xxg/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
    <script>
        tinymce.init({
            selector: 'textarea',
            plugins: 'advlist autolink lists link image charmap print preview anchor',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | ',
            menubar: false,
            height: 300,
            branding: false
        });
    </script>
    <style>
        :root { --bs-success-rgb: 71, 222, 152 !important; }
        html, body { font-family: "Moderustic", sans-serif; height: 100%; width: 100%; }
    </style>
</head>

<body>
    @include('rapatkita.partials.navbar')

    <div class="container" id="page-container">
        <div class="row">
            <div class="col-12 my-4">
                <form action="{{ route('rapatkita.notulen.store', $schedule) }}" method="post" id="schedule-form" enctype="multipart/form-data">
                    @csrf
                    <div class="row bg-white shadow p-3 bg-body rounded">
                        <div class="col-12">
                            <h4>Foto</h4>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-group">
                                <label for="foto" class="control-label">Foto Kegiatan <span style="font-size: 10px; color: red;" class="fw-semibold">*Maksimal 3 Foto</span></label>
                                <input type="file" class="form-control" name="foto[]" id="foto" multiple accept="image/*">
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="form-group">
                                <label for="foto_surat" class="control-label">Foto Surat <span style="font-size: 10px; color: red;" class="fw-semibold">*Masukan 1 Foto Saja</span></label>
                                <input type="file" class="form-control" name="foto_surat" id="foto_surat" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="row bg-white shadow p-3 bg-body rounded mt-3">
                        <div class="col-12">
                            <h4>Waktu Kegiatan</h4>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="form-group">
                                <label for="hari" class="control-label">Tanggal</label>
                                <input type="date" class="form-control" name="hari" id="hari" value="{{ old('hari', $schedule->start_datetime?->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="jam_mulai" class="control-label">Jam Mulai</label>
                                <input type="time" class="form-control" name="jam_mulai" id="jam_mulai" value="{{ old('jam_mulai', $schedule->start_datetime?->format('H:i')) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row bg-white shadow p-3 bg-body rounded mt-3">
                        <div class="col-12">
                            <h4>Perihal</h4>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="title" class="control-label">Nama Kegiatan</label>
                                <input type="text" class="form-control" name="title" id="title" required value="{{ old('title', $schedule->title) }}">
                            </div>
                        </div>
                    </div>
                    <div class="row bg-white shadow p-3 bg-body rounded mt-3">
                        <div class="col-12">
                            <h4>Anggota Kegiatan</h4>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="form-group">
                                <label for="ketua" class="control-label">Ketua</label>
                                <input type="text" class="form-control" name="ketua" id="ketua" value="{{ old('ketua') }}" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="sekretaris" class="control-label">Sekretaris</label>
                                <input type="text" class="form-control" name="sekretaris" id="sekretaris" value="{{ old('sekretaris') }}" required>
                            </div>
                        </div>
                        <div class="col-12 mt-1">
                            <label for="anggota" class="control-label">Anggota Kegiatan</label>
                            <textarea id="anggota" name="anggota" class="form-control">{{ old('anggota') }}</textarea>
                        </div>
                    </div>
                    <div class="row bg-white shadow p-3 bg-body rounded mt-3">
                        <div class="col-12">
                            <h4>Kegiatan</h4>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="form-group">
                                <label for="susunan" class="control-label">Susunan Kegiatan</label>
                                <textarea id="susunan" name="susunan" class="form-control">{{ old('susunan') }}</textarea>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="pembahasan" class="control-label">Pembahasan Kegiatan</label>
                                <textarea id="pembahasan" name="pembahasan" class="form-control">{{ old('pembahasan') }}</textarea>
                            </div>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-group">
                                <label for="hasil" class="control-label">Hasil Kegiatan</label>
                                <textarea id="hasil" name="hasil" class="form-control">{{ old('hasil') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row bg-white shadow p-3 bg-body rounded mt-3">
                        <div class="col-12">
                            <h4>Anggota Kegiatan</h4>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="form-group">
                                <label for="nama_pimpinan" class="control-label">Nama Pimpinan</label>
                                <input type="text" class="form-control" name="nama_pimpinan" id="nama_pimpinan" value="{{ old('nama_pimpinan') }}" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="jabatan_pimpinan" class="control-label">Jabatan Pimpinan</label>
                                <input type="text" class="form-control" name="jabatan_pimpinan" id="jabatan_pimpinan" value="{{ old('jabatan_pimpinan') }}" required>
                            </div>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="form-group">
                                <label for="nama_notulis" class="control-label">Nama Notulis</label>
                                <input type="text" class="form-control" name="nama_notulis" id="nama_notulis" value="{{ old('nama_notulis') }}" required>
                            </div>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="form-group">
                                <label for="jabatan_notulis" class="control-label">Jabatan Notulis</label>
                                <input type="text" class="form-control" name="jabatan_notulis" id="jabatan_notulis" value="{{ old('jabatan_notulis') }}" required>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-success">tambah</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-12">
                <div class="text-center text-black fw-semibold" style="font-size: 18px;">
                    © DPMPTSP RapatKita 2.1.1 2025
                </div>
            </div>
        </div>
    </div>
    @if ($errors->any())
        <script>swal("Error!", @json($errors->first()), "error");</script>
    @endif
</body>

</html>
