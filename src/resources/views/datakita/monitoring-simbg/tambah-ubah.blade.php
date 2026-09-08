<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah/Ubah Pengambilan SK — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>html, body { font-family: "Moderustic", sans-serif; } .card { margin-top: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); } .form-label { font-weight: 600; }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="mx-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="card-title h4">Tambah/Ubah Pengambilan SK</h1>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3" id="formTambahUbah">
                    @csrf
                    <div class="col-md-6">
                        <label for="no_registrasi" class="form-label">No Registrasi</label>
                        <select class="form-control select2" id="no_registrasi" name="no_registrasi" required>
                            <option value="">Pilih No Registrasi</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="jenis_permohonan" class="form-label">Jenis Permohonan</label>
                        <input type="text" class="form-control" id="jenis_permohonan" name="jenis_permohonan">
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_registrasi" class="form-label">Tanggal Registrasi</label>
                        <input type="date" class="form-control" id="tgl_registrasi" name="tgl_registrasi">
                    </div>
                    <div class="col-md-6">
                        <label for="no_dokumen_pbg" class="form-label">No Dokumen PBG</label>
                        <input type="text" class="form-control" id="no_dokumen_pbg" name="no_dokumen_pbg" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_dokumen_pbg" class="form-label">Tanggal Dokumen PBG</label>
                        <input type="date" class="form-control" id="tgl_dokumen_pbg" name="tgl_dokumen_pbg" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nama_pemilik" class="form-label">Nama Pemilik</label>
                        <input type="text" class="form-control" id="nama_pemilik" name="nama_pemilik">
                    </div>
                    <div class="col-md-6">
                        <label for="dokumen" class="form-label">Dokumen</label>
                        <input type="text" class="form-control" id="dokumen" name="dokumen" required>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <input type="text" class="form-control" id="status" name="status" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_ambil_data" class="form-label">Tanggal Ambil Data</label>
                        <input type="date" class="form-control" id="tgl_ambil_data" name="tgl_ambil_data" required>
                    </div>
                    <div class="col-md-6">
                        <label for="jam_ambil_data" class="form-label">Jam Ambil Data</label>
                        <input type="time" class="form-control" id="jam_ambil_data" step="1" name="jam_ambil_data" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_pengambilan_sk" class="form-label">Tanggal Pengambilan SK</label>
                        <input type="date" class="form-control" id="tgl_pengambilan_sk" name="tgl_pengambilan_sk" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nama_pengambil_sk" class="form-label">Nama Pengambil SK</label>
                        <input type="text" class="form-control" id="nama_pengambil_sk" name="nama_pengambil_sk" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nik" class="form-label">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" required>
                    </div>
                    <div class="col-md-6">
                        <label for="npwp" class="form-label">NPWP</label>
                        <input type="text" class="form-control" id="npwp" name="npwp" required>
                    </div>
                    <div class="col-md-12">
                        <label for="hak_atas_tanah" class="form-label">Hak Atas Tanah</label>
                        <input type="text" class="form-control" id="hak_atas_tanah" name="hak_atas_tanah" required>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100" id="btnSubmit">Tambah Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } });

        $(document).ready(function() {
            $('.select2').select2({
                ajax: {
                    url: @json(route('datakita.monitoring-simbg.get-select-data')),
                    data: function(params) {
                        return { search: params.term };
                    },
                    dataType: 'json',
                    processResults: function(data) {
                        return { results: data };
                    }
                }
            });

            $('#no_registrasi').change(function() {
                var no_registrasi = $(this).val();
                if (no_registrasi) {
                    $.ajax({
                        url: @json(route('datakita.monitoring-simbg.get-data')),
                        type: 'POST',
                        data: { no_registrasi: no_registrasi },
                        dataType: 'json',
                        success: function(data) {
                            if (data.exists) {
                                $('#btnSubmit').text('Ubah Data');
                                $('#jenis_permohonan').val(data.jenis_permohonan);
                                $('#tgl_registrasi').val(data.tgl_registrasi);
                                $('#nama_pemilik').val(data.nama_pemilik);
                                $('#no_dokumen_pbg').val(data.no_dokumen_pbg);
                                $('#tgl_dokumen_pbg').val(data.tgl_dokumen_pbg);
                                $('#dokumen').val(data.dokumen);
                                $('#status').val(data.status);
                                $('#tgl_ambil_data').val(data.tgl_ambil_data);
                                $('#jam_ambil_data').val(data.jam_ambil_data);
                                $('#tgl_pengambilan_sk').val(data.tgl_pengambilan_sk);
                                $('#nama_pengambil_sk').val(data.nama_pengambil_sk);
                                $('#nik').val(data.nik);
                                $('#npwp').val(data.npwp);
                                $('#hak_atas_tanah').val(data.hak_atas_tanah);
                            } else {
                                $('#btnSubmit').text('Tambah Data');
                                $('#jenis_permohonan').val(data.jenis_permohonan);
                                $('#tgl_registrasi').val(data.tgl_registrasi);
                                $('#nama_pemilik').val(data.nama_pemilik);
                                $('#no_dokumen_pbg').val('');
                                $('#tgl_dokumen_pbg').val('');
                                $('#dokumen').val('');
                                $('#status').val('');
                                $('#tgl_ambil_data').val('');
                                $('#jam_ambil_data').val('');
                                $('#tgl_pengambilan_sk').val('');
                                $('#nama_pengambil_sk').val('');
                                $('#nik').val('');
                                $('#npwp').val('');
                                $('#hak_atas_tanah').val('');
                            }
                        },
                        error: function() {
                            Swal.fire({ icon: 'error', title: 'Oops...', text: 'Terjadi kesalahan saat mengambil data' });
                        }
                    });
                }
            });

            $('#formTambahUbah').submit(function(e) {
                e.preventDefault();
                var action = ($('#btnSubmit').text() === 'Tambah Data') ? 'tambah' : 'ubah';
                $.ajax({
                    url: @json(route('datakita.monitoring-simbg.proses-tambah-ubah')),
                    type: 'POST',
                    data: $(this).serialize() + '&action=' + action,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Sukses!', text: response.message }).then((result) => {
                                if (result.isConfirmed) window.location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Oops...', text: response.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Terjadi kesalahan saat memproses data' });
                    }
                });
            });
        });
    </script>
</body>

</html>
