<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Notulen Rapat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; }
        .container { margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; text-align: center; font-size: 1.5rem; font-weight: bold; }
        .card-body { padding: 20px; background: white; }
        .detail-item { padding: 10px 0; border-bottom: 1px solid #ddd; }
        .detail-item:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #333; }
        .footer { text-align: center; padding: 20px; font-size: 16px; color: #555; }
        .gallery img { width: 100%; max-height: 100%; object-fit: cover; border-radius: 10px; cursor: pointer; }
    </style>
</head>

<body>
    @include('rapatkita.partials.navbar')

    <div class="container">
        <div class="card">
            <div class="card-header">
                Detail Notulen {{ $notulen->id_kegiatan !== null ? 'Rapat' : 'Personal' }}
            </div>
            <div class="card-body">
                <div class="detail-item"><span class="label">Tanggal:</span> {{ $notulen->tanggal?->translatedFormat('d F Y') }}</div>
                <div class="detail-item"><span class="label">Jam Mulai:</span> {{ $notulen->jam_mulai ? \Carbon\Carbon::parse($notulen->jam_mulai)->format('H:i') : '-' }}</div>
                <div class="detail-item"><span class="label">Nama Kegiatan:</span> {{ $notulen->nama_kegiatan }}</div>
                <div class="detail-item"><span class="label">Ketua:</span> {{ $notulen->ketua }}</div>
                <div class="detail-item"><span class="label">Sekretaris:</span> {{ $notulen->sekretaris }}</div>
                <div class="detail-item"><span class="label">Anggota:</span> {!! $notulen->anggota !!}</div>
                <div class="detail-item"><span class="label">Susunan:</span> {!! $notulen->susunan !!}</div>
                <div class="detail-item"><span class="label">Pembahasan:</span> {!! $notulen->pembahasan !!}</div>
                <div class="detail-item"><span class="label">Hasil:</span> {!! $notulen->hasil !!}</div>
                <div class="detail-item"><span class="label">Nama Pimpinan:</span> {{ $notulen->nama_pimpinan }}</div>
                <div class="detail-item"><span class="label">Jabatan Pimpinan:</span> {{ $notulen->jabatan_pimpinan }}</div>
                <div class="detail-item"><span class="label">Nama Notulis:</span> {{ $notulen->nama_notulis }}</div>
                <div class="detail-item"><span class="label">Jabatan Notulis:</span> {{ $notulen->jabatan_notulis }}</div>

                <div class="mt-4">
                    <h4 class="text-center">Dokumentasi Rapat</h4>
                    <div class="row gallery">
                        @foreach (['foto_1', 'foto_2', 'foto_3'] as $index => $img)
                            @if ($notulen->$img)
                                <div class="col-md-4">
                                    <a href="{{ Storage::url($notulen->$img) }}" target="_blank" data-bs-toggle="modal" data-bs-target="#imageModalFoto{{ $index }}">
                                        <img src="{{ Storage::url($notulen->$img) }}" class="img-fluid">
                                    </a>
                                </div>
                                <div class="modal fade" id="imageModalFoto{{ $index }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Dokumentasi Rapat</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="{{ Storage::url($notulen->$img) }}" class="img-fluid">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="mt-4">
                    <h4 class="text-center">Foto Surat</h4>
                    <div class="row justify-content-center gallery">
                        @if ($notulen->foto_surat)
                            <div class="col-md-4 d-flex justify-content-center">
                                <a href="{{ Storage::url($notulen->foto_surat) }}" target="_blank" data-bs-toggle="modal" data-bs-target="#imageModalSurat">
                                    <img src="{{ Storage::url($notulen->foto_surat) }}" class="img-fluid rounded shadow">
                                </a>
                            </div>
                            <div class="modal fade" id="imageModalSurat" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Foto Surat</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img src="{{ Storage::url($notulen->foto_surat) }}" class="img-fluid rounded shadow">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        &copy; DPMPTSP 2025 - RapatKita 2.1.1
    </div>
</body>

</html>
