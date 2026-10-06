@extends('bangkit.partials.header')

@section('title', 'List Permohonan Perbaikan')

@php
    $isAdmin = (int) (auth()->user()->role ?? 0) === 1 || (int) (auth()->user()->is_admin_bangkit ?? 0) === 1;
@endphp

@section('content')
    <h2 class="fw-bold mb-4">List Permohonan Perbaikan Barang</h2>

    <div class="mb-3">
        @include('bangkit.permohonan.partials.info-barang', ['barang' => $barang])
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Table Permohonan</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Permohonan</th>
                            <th>Verifikasi</th>
                            <th>Perbaikan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="small">
                                    <div><b>Perbaikan Ke:</b> {{ $d->perbaikan_ke }}</div>
                                    <div><b>Tanggal Dibuat:</b> {{ optional($d->tanggal_permohonan)->format('d-m-Y') }}</div>
                                    <div class="border rounded p-2 mt-1"><b>Kerusakan:</b><br>{{ $d->uraian_kerusakan }}</div>
                                </td>
                                <td class="small">
                                    @foreach (['Bendahara' => 'verifikasi_b_barang', 'Kasubag Umpeg' => 'verifikasi_umpeg', 'Sekretaris Dinas' => 'verifikasi_sekdin'] as $label => $col)
                                        <div class="mt-1">
                                            <b>{{ $label }}:</b>
                                            <span class="badge bg-{{ \App\Models\Bangkit\PermohonanPerbaikan::warnaVerifikasi($d->$col) }}">
                                                {{ \App\Models\Bangkit\PermohonanPerbaikan::labelVerifikasi($d->$col) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="small">
                                    <div><b>Mulai:</b> {{ optional($d->tanggal_pengerjaan)->format('d-m-Y') }}</div>
                                    <div><b>Dikerjakan:</b> {{ $d->pengerjaan_oleh }}</div>
                                    <div><b>Selesai:</b> {{ optional($d->tanggal_selesai)->format('d-m-Y') }}</div>
                                    <div><b>Biaya:</b> Rp {{ number_format((float) $d->biaya, 0, ',', '.') }}</div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column gap-1">
                                        <a href="{{ route('bangkit.permohonan.form-ubah', $d->Id) }}" class="btn btn-warning btn-sm">Ubah</a>
                                        <form action="{{ route('bangkit.permohonan.hapus', $d->Id) }}" method="POST" onsubmit="return confirm('Yakin hapus permohonan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm w-100">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada permohonan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
