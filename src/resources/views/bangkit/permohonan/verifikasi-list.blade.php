@extends('bangkit.partials.header')

@section('title', 'List Verifikasi Permohonan')

@php
    $isAdmin = (int) (auth()->user()->role ?? 0) === 1 || (int) (auth()->user()->is_admin_bangkit ?? 0) === 1;
    $role = (int) (auth()->user()->role ?? 0);
    $levelLabel = match ($role) { 5 => 'Bendahara', 4 => 'Kasubag Umpeg', 3 => 'Sekretaris Dinas', default => 'Admin' };
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">List Verifikasi Permohonan</h2>
        @unless ($isAdmin)
            <span class="badge bg-secondary">Level: {{ $levelLabel }}</span>
        @endunless
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Permohonan</th>
                            <th>Verifikasi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="small">
                                    <div><b>Barang:</b> {{ $d->barang?->nama_barang }}</div>
                                    <div><b>Merk/Tipe:</b> {{ $d->barang?->merk_type }}</div>
                                    <div><b>Perbaikan Ke:</b> {{ $d->perbaikan_ke }}</div>
                                    <div><b>Tanggal:</b> {{ optional($d->tanggal_permohonan)->format('d-m-Y') }}</div>
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
                                <td class="text-center">
                                    <a href="{{ route('bangkit.permohonan.form-verifikasi', $d->Id) }}" class="btn btn-success btn-sm">Verifikasi</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada permohonan yang menunggu verifikasi Anda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
