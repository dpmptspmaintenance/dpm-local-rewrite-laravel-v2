@extends('bangkit.partials.header')

@section('title', 'Form Verifikasi Permohonan')

@php
    $isAdmin = (int) (auth()->user()->role ?? 0) === 1 || (int) (auth()->user()->is_admin_bangkit ?? 0) === 1;
    $role = (int) (auth()->user()->role ?? 0);
    $barang = $permohonan->barang;
@endphp

@section('content')
    <h2 class="fw-bold mb-4">Form Verifikasi Permohonan</h2>

    <div class="mb-3">
        @include('bangkit.permohonan.partials.info-barang', ['barang' => $barang])
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Keterangan Permohonan</h5>
            <div class="row small mb-4">
                <div class="col-md-4"><b>Kerusakan:</b><br>{{ $permohonan->uraian_kerusakan }}</div>
                <div class="col-md-4"><b>Tanggal:</b><br>{{ optional($permohonan->tanggal_permohonan)->format('d-m-Y') }}</div>
                <div class="col-md-4"><b>Perbaikan Ke:</b><br>{{ $permohonan->perbaikan_ke ?? '-' }}</div>
            </div>

            <h5 class="fw-bold mb-3">Status Verifikasi Saat Ini</h5>
            <div class="row small mb-4">
                @foreach ([['Bendahara', 'verifikasi_b_barang', 'penjelasan_b_barang', 'tanggal_verifikasi_b_barang'], ['Kasubag Umpeg', 'verifikasi_umpeg', 'penjelasan_umpeg', 'tanggal_verifikasi_umpeg'], ['Sekretaris Dinas', 'verifikasi_sekdin', 'penjelasan_sekdin', 'tanggal_verifikasi_sekdin']] as [$label, $col, $ket, $tgl])
                    <div class="col-md-4">
                        <b>{{ $label }}:</b>
                        <span class="badge bg-{{ \App\Models\Bangkit\PermohonanPerbaikan::warnaVerifikasi($permohonan->$col) }}">{{ \App\Models\Bangkit\PermohonanPerbaikan::labelVerifikasi($permohonan->$col) }}</span>
                        <div class="text-muted">{{ $permohonan->$tgl ? \Illuminate\Support\Carbon::parse($permohonan->$tgl)->format('d-m-Y') : '' }}</div>
                        <div>{{ $permohonan->$ket }}</div>
                    </div>
                @endforeach
            </div>

            <hr>
            <h5 class="fw-bold mb-3">Keputusan Verifikasi</h5>
            <form action="{{ route('bangkit.permohonan.verifikasi', $permohonan->Id) }}" method="POST">
                @csrf
                @if ($isAdmin)
                    <div class="mb-3">
                        <label class="fw-semibold mb-2">Level Verifikasi</label>
                        <select name="level" class="form-select" required>
                            <option value="b">Bendahara</option>
                            <option value="umpeg">Kasubag Umpeg</option>
                            <option value="sekdin">Sekretaris Dinas</option>
                        </select>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="fw-semibold mb-2">Keputusan</label>
                    <select name="verifikasi" class="form-select" required>
                        <option value="1">Disetujui</option>
                        <option value="2">Ditolak</option>
                        <option value="0">Belum Diverifikasi</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fw-semibold mb-2">Keterangan</label>
                    <textarea name="penjelasan" class="form-control" rows="3"></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" onclick="history.back()" class="btn btn-danger">Kembali</button>
                    <button type="submit" class="btn btn-success">Simpan Verifikasi</button>
                </div>
            </form>
        </div>
    </div>
@endsection
