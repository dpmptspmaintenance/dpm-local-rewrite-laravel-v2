@extends('bangkit.partials.header')

@section('title', 'Data Anggaran')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Data Anggaran</h2>
        <a href="{{ route('bangkit.sdia.tambah-anggaran') }}" class="btn btn-success btn-sm">Tambah Anggaran</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light"><tr><th>No</th><th>Tahun</th><th>Kegiatan</th><th>Rek. Klas</th><th>Anggaran</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($anggaran as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->kegiatan?->tahun }}</td>
                                <td>{{ $row->kegiatan?->nama_kegiatan }}</td>
                                <td>{{ $row->klasifikasi?->rek_klas }}</td>
                                <td>Rp {{ number_format($row->anggaran, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('bangkit.sdia.ubah-anggaran', $row->Id) }}" class="btn btn-sm btn-primary">Ubah</a>
                                    @if ($row->used_in_bulanan)
                                        <button class="btn btn-sm btn-danger" disabled title="Sedang digunakan">Hapus</button>
                                    @else
                                        <form action="{{ route('bangkit.sdia.delete-anggaran', $row->Id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Tidak ada data anggaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
