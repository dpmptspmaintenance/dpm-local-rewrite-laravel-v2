<table class="table table-hover align-middle mb-0">
    <thead class="table-light text-muted small text-uppercase">
        <tr>
            <th class="ps-3 py-3">Tanggal</th>
            <th>Kode Transaksi</th>
            <th>Vendor / Rekanan</th>
            <th>Diinput Oleh</th>
            <th class="text-center">Item</th>
            <th class="text-end">Total Nilai</th>
            <th class="text-center">Status</th>
            <th class="text-end pe-3">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($masukDocs as $doc)
            @php $row = $doc['row']; @endphp
            <tr>
                <td class="ps-3 text-muted small">{{ $row->tanggal_transaksi->format('d-m-Y') }}</td>
                <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold">{{ $row->kode_transaksi }}</span></td>
                <td class="fw-bold text-dark">{{ $row->pihak_terkait ?: '-' }}</td>
                <td><small class="text-secondary"><i class="bi bi-person me-1"></i>{{ $mapUsers[$row->created_by] ?? '-' }}</small></td>
                <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1 rounded">{{ $doc['total_item'] }}</span></td>
                <td class="text-end text-success fw-bold">Rp {{ number_format($doc['total_nilai'], 0, ',', '.') }}</td>
                <td class="text-center">@include('persediaan.riwayat-dokumen.partials.status-badge', ['status' => $row->status])</td>
                <td class="text-end pe-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-2" data-bs-toggle="collapse" data-bs-target="#detailMasuk{{ $row->id }}" title="Lihat Rincian"><i class="bi bi-chevron-down"></i></button>

                    @if ($row->status === 'draft')
                        <form method="POST" action="{{ route('persediaan.riwayat-dokumen.ajukan') }}" class="d-inline" onsubmit="return confirm('Ajukan dokumen BAST ini ke Admin untuk diverifikasi?')">
                            @csrf
                            <input type="hidden" name="id_header" value="{{ $row->id }}">
                            <button type="submit" class="btn btn-sm btn-success shadow-sm rounded-2" title="Ajukan ke Admin"><i class="bi bi-send-fill"></i></button>
                        </form>
                    @endif

                    @if ($row->status === 'draft' || $isAdmin)
                        <form method="POST" action="{{ route('persediaan.riwayat-dokumen.hapus') }}" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini secara permanen?')">
                            @csrf
                            <input type="hidden" name="id_header" value="{{ $row->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm rounded-2" title="Hapus Dokumen"><i class="bi bi-trash-fill"></i></button>
                        </form>
                    @endif

                    <a href="{{ route('persediaan.cetak.bast', $row->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-2" title="Cetak Naskah BAST"><i class="bi bi-printer"></i></a>

                    @if ($isAdmin || in_array($row->status, ['draft', 'menunggu', 'ditolak'], true))
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}" title="Edit / Perbaiki Dokumen"><i class="bi bi-pencil"></i></button>
                    @endif
                </td>
            </tr>
            <tr class="collapse" id="detailMasuk{{ $row->id }}">
                <td colspan="8" class="bg-light bg-opacity-50 p-0 border-0">
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <table class="table table-bordered table-sm align-middle small shadow-sm bg-white mb-0">
                                    <thead class="table-dark text-center">
                                        <tr><th>Uraian Barang & Rekening</th><th style="width:80px;">Qty</th><th style="width:110px;">Harga Satuan</th><th style="width:110px;">Subtotal</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($doc['details'] as $dtl)
                                            <tr>
                                                <td>
                                                    <span class="d-block fw-bold text-dark">{{ $dtl->barang->nama_barang ?? '-' }}</span>
                                                    <small class="text-muted font-monospace">{{ $dtl->barang->rekening->nama_rekening ?? '-' }}</small>
                                                </td>
                                                <td class="text-center fw-bold text-primary">{{ $dtl->qty }} <small class="text-muted fw-normal">{{ $dtl->barang->satuan->nama_satuan ?? '' }}</small></td>
                                                <td class="text-end">Rp {{ number_format($dtl->harga_satuan, 0, ',', '.') }}</td>
                                                <td class="text-end fw-bold text-success">Rp {{ number_format($dtl->qty * $dtl->harga_satuan, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-lg-5">
                                <label class="small text-dark fw-bold mb-2 d-block"><i class="bi bi-paperclip me-1"></i>Lampiran Dokumen Fisik</label>
                                @if ($doc['files']->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($doc['files'] as $f)
                                            @if ($f['ext'] === 'pdf')
                                                <a href="{{ $f['url'] }}" target="_blank" class="lampiran-pdf-chip" title="{{ $f['name'] }}"><i class="bi bi-file-earmark-pdf-fill"></i></a>
                                            @else
                                                <a href="{{ $f['url'] }}" target="_blank" title="{{ $f['name'] }}"><img src="{{ $f['url'] }}" class="lampiran-thumb" alt="{{ $f['name'] }}"></a>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <p class="small text-muted mb-0">Tidak ada berkas dilampirkan.</p>
                                @endif

                                @if ($isAdmin && $row->status === 'menunggu')
                                    <form method="POST" action="{{ route('persediaan.riwayat-dokumen.approve') }}" class="d-flex gap-2 mt-3">
                                        @csrf
                                        <input type="hidden" name="id_header" value="{{ $row->id }}">
                                        <button type="submit" name="status_app" value="ditolak" class="btn btn-danger px-3 rounded-3 fw-bold small shadow-sm" onclick="return confirm('Tolak dokumen ini?')"><i class="bi bi-x-lg me-1"></i> Tolak Input</button>
                                        <button type="submit" name="status_app" value="disetujui" class="btn btn-success px-4 rounded-3 fw-bold small shadow-sm" onclick="return confirm('Setujui dokumen ini dan tambahkan ke stok?')"><i class="bi bi-check-lg me-1"></i> Setujui & Tambah Stok</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            @push('modals')
                @include('persediaan.riwayat-dokumen.partials.edit-modal', ['doc' => $doc, 'jenis' => 'masuk'])
            @endpush
        @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted bg-light bg-opacity-10"><i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>Belum ada riwayat berkas masuk persediaan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
