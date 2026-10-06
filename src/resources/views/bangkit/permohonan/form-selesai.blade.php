@extends('bangkit.partials.header')

@section('title', 'Form Selesai')

@php $barang = $permohonan->barang; @endphp

@section('content')
    <h2 class="fw-bold mb-4">Form Penyelesaian Perbaikan</h2>

    <div class="mb-3">
        @include('bangkit.permohonan.partials.info-barang', ['barang' => $barang])
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Keterangan Permohonan</h5>
            <div class="row small mb-4">
                <div class="col-md-4"><b>Kerusakan:</b><br>{{ $permohonan->uraian_kerusakan }}</div>
                <div class="col-md-4"><b>Tanggal:</b><br>{{ optional($permohonan->tanggal_permohonan)->format('d-m-Y') }}</div>
            </div>

            <h5 class="fw-bold mb-3">Form Kelengkapan Perbaikan</h5>
            <form action="{{ route('bangkit.permohonan.selesai', $permohonan->Id) }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Perbaikan Ke</label>
                    <input type="text" name="perbaikan_ke" class="form-control" value="{{ old('perbaikan_ke', $permohonan->perbaikan_ke) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Pagu Anggaran</label>
                    <input type="number" step="0.01" name="pagu_anggaran" class="form-control" value="{{ old('pagu_anggaran', $permohonan->pagu_anggaran) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Tanggal Pengerjaan</label>
                    <input type="date" name="tanggal_pengerjaan" class="form-control" value="{{ old('tanggal_pengerjaan', optional($permohonan->tanggal_pengerjaan)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Dikerjakan Oleh</label>
                    <input type="text" name="pengerjaan_oleh" class="form-control" value="{{ old('pengerjaan_oleh', $permohonan->pengerjaan_oleh) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai', optional($permohonan->tanggal_selesai)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Serah Terima Oleh</label>
                    <input type="text" name="serah_terima_oleh" class="form-control" value="{{ old('serah_terima_oleh', $permohonan->serah_terima_oleh) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Biaya</label>
                    <input type="number" step="0.01" name="biaya" class="form-control" value="{{ old('biaya', $permohonan->biaya) }}">
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold mb-2">Tanggal SPJ</label>
                    <input type="date" name="spj_tanggal" class="form-control" value="{{ old('spj_tanggal', optional($permohonan->spj_tanggal)->format('Y-m-d')) }}">
                </div>
                <div class="col-12">
                    <label class="fw-semibold mb-2">Keterangan Perbaikan</label>
                    <textarea name="uraian_perbaikan" class="form-control" rows="3">{{ old('uraian_perbaikan', $permohonan->uraian_perbaikan) }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-between">
                    <button type="button" onclick="history.back()" class="btn btn-danger">Kembali</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
