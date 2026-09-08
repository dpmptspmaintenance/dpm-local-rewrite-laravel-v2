<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frekuensi Pengajuan Per Individu — Data Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Moderustic:wght@300..800&display=swap" rel="stylesheet">
    <style>html, body { font-family: "Moderustic", sans-serif; }</style>
</head>

<body>
    @include('datakita.partials.navbar')

    <div class="m-3">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Data</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('datakita.overview.mppd-frekuensi-per-individu') }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Pilih Tahun</label>
                            <select class="form-select" name="tahun">
                                @foreach ($listTahun as $thn)
                                    <option value="{{ $thn }}" {{ $thn == $tahun ? 'selected' : '' }}>{{ $thn }}</option>
                                @endforeach
                                @if ($listTahun->isEmpty())
                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
                            <a href="{{ route('datakita.overview.mppd-frekuensi-per-individu-export', ['tahun' => $tahun]) }}" class="btn btn-success" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="fw-bold text-uppercase mb-0">Frekuensi Pengajuan Permohonan oleh Individu</h5>
                <small class="text-muted">Tahun: {{ $tahun }} | Total Pemohon Unik: {{ number_format($totalOrangGlobal, 0, ',', '.') }} Orang</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 text-center" style="border-color: #000;">
                        <thead class="bg-secondary text-white fw-bold">
                            <tr>
                                <th rowspan="2" class="align-middle bg-light text-dark">No Jumlah<br>(n kali)</th>
                                <th colspan="2" class="bg-light text-dark">Pengajuan Permohonan</th>
                                <th colspan="4" class="bg-light text-dark">Status Permohonan</th>
                                <th colspan="2" class="bg-light text-dark">Total & Persentase</th>
                            </tr>
                            <tr>
                                <th class="bg-light text-dark">Jumlah<br>Orang</th>
                                <th class="bg-light text-dark">% Orang</th>
                                <th class="bg-light text-dark">Dibatalkan</th>
                                <th class="bg-light text-dark">Ditolak</th>
                                <th class="bg-light text-dark">Verifikasi<br>DPMPTSP</th>
                                <th class="bg-light text-dark">SK<br>Diterbitkan</th>
                                <th class="bg-light text-dark">Total App</th>
                                <th class="bg-light text-dark">% App</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($rows->isNotEmpty())
                                @foreach ($rows as $row)
                                    @php
                                        $nKali = $row->jumlah_kali_mengajukan;
                                        $jmlOrang = $row->jumlah_orang;
                                        $percOrang = ($totalOrangGlobal > 0) ? ($jmlOrang / $totalOrangGlobal) * 100 : 0;
                                        $rowTotalApp = $row->tot_batal + $row->tot_tolak + $row->tot_verif + $row->tot_terbit;
                                        $percApp = ($totalAplikasiGlobal > 0) ? ($rowTotalApp / $totalAplikasiGlobal) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $nKali }}</td>
                                        <td>{{ number_format($jmlOrang, 0, ',', '.') }}</td>
                                        <td>{{ round($percOrang, 2) }}%</td>
                                        <td>{{ $row->tot_batal }}</td>
                                        <td>{{ $row->tot_tolak }}</td>
                                        <td>{{ $row->tot_verif }}</td>
                                        <td>{{ $row->tot_terbit }}</td>
                                        <td class="fw-bold">{{ number_format($rowTotalApp, 0, ',', '.') }}</td>
                                        <td>{{ round($percApp, 2) }}%</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="9" class="py-4 text-muted">Data tidak ditemukan.</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="fw-bold bg-light" style="border-top: 2px solid #000;">
                            <tr>
                                <td class="text-start ps-3">Total</td>
                                <td>{{ number_format($totalOrangGlobal, 0, ',', '.') }}</td>
                                <td>100%</td>
                                <td>{{ number_format($footerBatal, 0, ',', '.') }}</td>
                                <td>{{ number_format($footerTolak, 0, ',', '.') }}</td>
                                <td>{{ number_format($footerVerif, 0, ',', '.') }}</td>
                                <td>{{ number_format($footerTerbit, 0, ',', '.') }}</td>
                                <td class="text-primary fs-5">{{ number_format($totalAplikasiGlobal, 0, ',', '.') }}</td>
                                <td class="bg-secondary"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end pe-3 fst-italic">Persentase Status Permohonan :</td>
                                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerBatal / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerTolak / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerVerif / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                                <td>{{ ($totalAplikasiGlobal > 0) ? round(($footerTerbit / $totalAplikasiGlobal) * 100) : 0 }}%</td>
                                <td colspan="2" class="bg-secondary"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
