@extends('bangkit.partials.header')

@section('title', 'List Selesai')

@section('content')
    <h2 class="fw-bold mb-4">List Permohonan Selesai Diverifikasi</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Sudah Terverifikasi Semua Level, Menunggu Penyelesaian</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                        <tr><th>No</th><th>Permohonan</th><th>Verifikasi</th><th class="text-center">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $i => $d)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="small">
                                    <div><b>Barang:</b> {{ $d->barang?->nama_barang }}</div>
                                    <div><b>Merk/Tipe:</b> {{ $d->barang?->merk_type }}</div>
                                    <div><b>Perbaikan Ke:</b> {{ $d->perbaikan_ke }}</div>
                                    <div class="border rounded p-2 mt-1"><b>Kerusakan:</b><br>{{ $d->uraian_kerusakan }}</div>
                                </td>
                                <td class="small">
                                    @foreach (['Bendahara' => 'verifikasi_b_barang', 'Kasubag Umpeg' => 'verifikasi_umpeg', 'Sekretaris Dinas' => 'verifikasi_sekdin'] as $label => $col)
                                        <div><b>{{ $label }}:</b> <span class="badge bg-success">{{ \App\Models\Bangkit\PermohonanPerbaikan::labelVerifikasi($d->$col) }}</span></div>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('bangkit.permohonan.form-selesai', $d->Id) }}" class="btn btn-success btn-sm">Selesaikan</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada permohonan menunggu penyelesaian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
