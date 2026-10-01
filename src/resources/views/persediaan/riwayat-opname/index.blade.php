@extends('persediaan.partials.header')

@section('title', 'Riwayat Stok Opname')

@section('content')
    <div class="container-fluid px-0">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0 text-dark"><i class="bi bi-clock-history me-2 text-success"></i>Arsip &amp; Riwayat Stok Opname Fleksibel</h5>
            <a href="{{ route('persediaan.stok-opname.index') }}" class="btn btn-sm btn-success px-3 fw-bold rounded-3"><i class="bi bi-plus-lg me-1"></i> Buat Opname Baru</a>
        </div>

        <div class="table-responsive bg-white rounded-4 shadow-sm p-3 border-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Tanggal</th>
                        <th>Kode Opname</th>
                        <th>Topik / Nama Kegiatan</th>
                        <th class="text-center">Total Item</th>
                        <th class="text-center">Total Selisih</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opnames as $opname)
                        @php $row = $opname['row']; @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $row->tanggal_opname->format('d/m/Y') }}</td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border border-success-subtle fw-bold">{{ $row->kode_opname }}</span></td>
                            <td class="fw-bold text-dark">{{ $row->nama_kegiatan }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1 rounded">{{ $opname['total_item'] }}</span></td>
                            <td class="text-center fw-bold {{ $opname['total_selisih_unit'] > 0 ? 'text-danger' : 'text-success' }}">{{ $opname['total_selisih_unit'] }} unit</td>
                            <td class="text-center">
                                @if ($row->status === 'draft')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Draft</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Final</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if ($row->status === 'final')
                                    <a href="{{ route('persediaan.cetak.baso', $row->id) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-2" title="Cetak BASO"><i class="bi bi-printer"></i></a>
                                @endif

                                <button class="btn btn-sm btn-outline-info rounded-2" data-bs-toggle="modal" data-bs-target="#viewModal{{ $row->id }}" title="Lihat Detail"><i class="bi bi-eye"></i></button>

                                @if ($row->status === 'draft')
                                    <form method="POST" action="{{ route('persediaan.riwayat-opname.finalkan') }}" class="d-inline" onsubmit="return confirm('Finalisasi dokumen opname ini? Stok sistem akan otomatis disesuaikan!')">
                                        @csrf
                                        <input type="hidden" name="id_opname" value="{{ $row->id }}">
                                        <button type="submit" class="btn btn-sm btn-success rounded-2" title="Finalkan Opname"><i class="bi bi-check-lg"></i> Finalkan</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('persediaan.riwayat-opname.hapus') }}" class="d-inline" onsubmit="return confirm('Hapus dokumen opname ini?')">
                                    @csrf
                                    <input type="hidden" name="id_opname" value="{{ $row->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-2" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="viewModal{{ $row->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 rounded-4">
                                    <div class="modal-header border-0 bg-light px-4">
                                        <div>
                                            <h5 class="modal-title fw-bold text-dark m-0">Detail Opname: {{ $row->kode_opname }}</h5>
                                            <small class="text-muted">{{ $row->nama_kegiatan }} | Tgl: {{ $row->tanggal_opname->translatedFormat('d F Y') }}</small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <table class="table mb-0 small align-middle">
                                            <thead class="table-secondary small text-uppercase text-center">
                                                <tr>
                                                    <th class="text-start ps-4">Uraian Barang</th>
                                                    <th>Satuan</th>
                                                    <th>Harga</th>
                                                    <th>Sistem</th>
                                                    <th>Fisik</th>
                                                    <th>Selisih</th>
                                                    <th class="text-start pe-4">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($opname['details'] as $dt)
                                                    <tr>
                                                        <td class="ps-4 fw-bold text-dark">{{ $dt->barang->nama_barang ?? '-' }}</td>
                                                        <td class="text-center">{{ $dt->barang->satuan->nama_satuan ?? '-' }}</td>
                                                        <td class="text-end">Rp {{ number_format($dt->harga_satuan, 0, ',', '.') }}</td>
                                                        <td class="text-center">{{ $dt->stok_sistem }}</td>
                                                        <td class="text-center fw-bold text-success">{{ $dt->stok_fisik }}</td>
                                                        <td class="text-center fw-bold {{ $dt->selisih != 0 ? 'text-danger' : 'text-muted' }}">{{ $dt->selisih > 0 ? '+' : '' }}{{ $dt->selisih }}</td>
                                                        <td class="pe-4 text-muted">{{ $dt->alasan_selisih ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>Belum ada arsip riwayat stok opname.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
