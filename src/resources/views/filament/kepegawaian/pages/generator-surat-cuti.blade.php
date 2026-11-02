<x-filament-panels::page>
    <div class="sc-block">
        <label class="sc-label">Tempel rekap pengajuan</label>
        <x-filament::input.wrapper>
                <textarea
                    rows="10"
                    wire:model.live.debounce.500ms="teks"
                    class="sc-textarea"
                placeholder="1. SANTI TRI WAHYUNI&#10;   - Cuti Sakit 2 hari (29 - 30 September 2026) pengajuan 05-10-2026 15:49:19&#10;2. RISTANTI EKA WIDAYATI, A.Md. Cuti Karena Alasan Penting 1 hari (05 Oktober 2026) pengajuan 04-10-2026 23:04:21"
            ></textarea>
        </x-filament::input.wrapper>
        <div class="sc-row">
                <div class="sc-field">
                    <label>Kode</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model.live.debounce.500ms="kode" />
                    </x-filament::input.wrapper>
                </div>
                <div class="sc-field">
                    <label>Nomor awal</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="1" wire:model.live.debounce.500ms="nomorAwal" />
                    </x-filament::input.wrapper>
                </div>
                <div class="sc-actions">
                    <x-filament::button type="button" icon="heroicon-o-sparkles" wire:click="generate">
                        Buat Nomor
                    </x-filament::button>
                    @if (count($hasil) > 0)
                        <x-filament::button type="button" color="gray" icon="heroicon-o-clipboard-document" wire:click="salinHasil">
                            Salin Semua ({{ count($hasil) }})
                        </x-filament::button>
                    @endif
                    @if ($tersimpanBatch !== null)
                        <span class="sc-hint">Otomatis tersimpan ({{ $tersimpanBatch }})</span>
                    @endif
                </div>
        </div>
        <p class="sc-hint">Satu nomor per rincian cuti, berurutan dari nomor awal. Bulan+tahun romawi diambil dari tanggal pengajuan tiap rincian. Sisa tampil sudah dikurangi pengajuan ini (aturan potong PNS/PPPK sama seperti rekap; minus tahun lalu memotong tahun depan). Tidak mengubah data cuti — pastikan impor cuti hari ini sudah dilakukan.</p>
    </div>

    @if (count($takDikenali) > 0)
        <div class="sc-warn">
            <strong>{{ count($takDikenali) }} baris tak dikenali (tanpa nomor):</strong>
            <ul>
                @foreach ($takDikenali as $baris)
                    <li>{{ $baris }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (count($duplikat) > 0)
        <div class="sc-dupe">
            <strong>{{ count($duplikat) }} duplikat (nama + tgl pengajuan sama — tanpa nomor):</strong>
            <ul>
                @foreach ($duplikat as $d)
                    <li>{{ $d['nama'] }} — {{ $d['jenis'] }} — pengajuan {{ $d['pengajuan'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (count($hasil) > 0)
        <div class="sc-table-wrap">
            <table class="sc-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Pengajuan</th>
                        <th>Nomor Surat</th>
                        @foreach ($tahun as $y)
                            <th class="sc-num">Sisa {{ $y }}</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                    <tbody>
                        @foreach ($hasil as $index => $h)
                            <tr @if (! empty($h['duplikat'])) class="sc-row-dupe" @endif>
                                <td class="sc-dim">{{ $h['no'] }}</td>
                                <td>{{ $h['nama'] }}</td>
                                <td class="sc-wrap">{{ $h['pengajuan'] }}</td>
                                <td>
                                    @if (! empty($h['duplikat']))
                                        <span class="sc-badge-dupe">Duplikat</span>
                                    @else
                                        <button type="button" class="sc-nomor" wire:click="salinSatu({{ $index }})" title="Klik untuk salin nomor + sisa">
                                            {{ $h['nomor'] }}
                                        </button>
                                    @endif
                                </td>
                                @foreach ($tahun as $y)
                                    <td class="sc-num">{{ $h['sisa'][$y] ?? '—' }}</td>
                                @endforeach
                                <td class="sc-actions-cell">
                                    <button type="button" class="sc-del" wire:click="hapusBaris({{ $index }})" wire:confirm="Hapus baris ini dari daftar?" title="Hapus baris">
                                        <x-filament::icon icon="heroicon-o-trash" class="sc-del-icon" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </table>
        </div>
        <p class="sc-hint">Klik nomor surat untuk menyalin nomor + sisa tahun berjalan, atau Salin Semua untuk TSV siap tempel ke Excel.</p>
    @elseif ($teks !== '')
        <p class="sc-hint">Klik Buat Nomor untuk memproses tempelan di atas.</p>
    @else
        <div class="sc-empty">Belum ada hasil. Tempel rekap pengajuan lalu klik Buat Nomor.</div>
    @endif

    <div class="sc-riwayat">
        <h3 class="sc-riwayat-title">Riwayat Batch <span class="sc-hint">— murni arsip, tidak dipakai perhitungan cuti</span></h3>
        @if (! $riwayatTersedia)
            <p class="sc-hint">Tabel riwayat belum ada di database. Jalankan SQL migrasi <code>2026_10_30_000002</code> terlebih dahulu.</p>
        @elseif (count($riwayat) === 0)
            <div class="sc-empty">Belum ada batch tersimpan. Klik Simpan Riwayat setelah membuat nomor.</div>
        @else
            <div class="sc-table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Oleh</th>
                            <th class="sc-num">Baris</th>
                            <th>Nomor</th>
                            <th>Sumber</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayat as $r)
                            <tr @if ($riwayatDetailBatch === $r['batch']) class="sc-row-open" @endif>
                                <td class="sc-dim">{{ $r['waktu'] }}</td>
                                <td>{{ $r['oleh'] }}</td>
                                <td class="sc-num">{{ $r['jumlah'] }}</td>
                                <td class="sc-mono">{{ $r['nomor_dari'] }} s.d. {{ $r['nomor_sampai'] }}@if ($r['duplikat'] > 0) <span class="sc-badge-dupe">+{{ $r['duplikat'] }} duplikat</span>@endif</td>
                                <td><span class="sc-badge-src">{{ $r['sumber'] }}</span></td>
                                <td class="sc-actions-cell">
                                    <button type="button" class="sc-view" wire:click="lihatRiwayat('{{ $r['batch'] }}')" title="Lihat rincian">
                                        <x-filament::icon icon="heroicon-o-eye" class="sc-del-icon" />
                                    </button>
                                    <button type="button" class="sc-del" wire:click="hapusRiwayat('{{ $r['batch'] }}')" wire:confirm="Hapus riwayat batch ini?" title="Hapus riwayat">
                                        <x-filament::icon icon="heroicon-o-trash" class="sc-del-icon" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($riwayatDetailBatch !== null)
                <div class="sc-detail">
                    <div class="sc-detail-head">
                        <strong>Rincian batch {{ $riwayatDetailBatch }}</strong>
                        <button type="button" class="sc-view" wire:click="tutupRiwayat" title="Tutup rincian">
                            <x-filament::icon icon="heroicon-o-x-mark" class="sc-del-icon" />
                        </button>
                    </div>
                    @if (count($riwayatDetail) === 0)
                        <p class="sc-hint">Batch tidak ditemukan (mungkin baru saja dihapus).</p>
                    @else
                        <div class="sc-table-wrap">
                            <table class="sc-table">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jenis</th>
                                        <th class="sc-num">Hari</th>
                                        <th>Pengajuan</th>
                                        <th>Nomor Surat</th>
                                        <th class="sc-num">Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($riwayatDetail as $d)
                                        <tr @if ($d['duplikat']) class="sc-row-dupe" @endif>
                                            <td>{{ $d['nama'] }}</td>
                                            <td>{{ $d['jenis'] }}</td>
                                            <td class="sc-num">{{ $d['hari'] }}</td>
                                            <td class="sc-wrap">{{ $d['pengajuan'] }}</td>
                                            <td class="sc-mono">@if ($d['duplikat'])<span class="sc-badge-dupe">Duplikat</span>@else{{ $d['nomor'] }}@endif</td>
                                            <td class="sc-num sc-mono-sm">@if (is_array($d['sisa'])){{ collect($d['sisa'])->map(fn ($v, $k) => $k.': '.$v)->implode(' · ') }}@else —@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <x-filament-actions::modals />

    @assets
    <style>
        .sc-block { display: grid; gap: .5rem; }
        .sc-label { font-size: .8rem; color: #4b5563; }
        .dark .sc-label { color: #d1d5db; }
        .sc-textarea {
            display: block; width: 100%; border: none; background: transparent;
            padding: .5rem .75rem; font-size: .875rem; line-height: 1.5;
            color: #111827; outline: none; resize: vertical; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .dark .sc-textarea { color: #f3f4f6; }
        .sc-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; }
        .sc-field { display: grid; gap: .25rem; font-size: .8rem; min-width: 8rem; flex: 0 1 10rem; }
        .sc-field > label { color: #4b5563; }
        .dark .sc-field > label { color: #d1d5db; }
        .sc-actions { display: flex; gap: .5rem; margin-left: auto; }
        .sc-hint { font-size: .8rem; color: #6b7280; margin: .25rem 0 0; }
        .dark .sc-hint { color: #9ca3af; }
        .sc-warn {
            margin-top: 1rem; padding: .75rem 1rem; border-radius: .5rem;
            background: #fef3c7; color: #92400e; font-size: .875rem;
        }
        .dark .sc-warn { background: #451a03; color: #fcd34d; }
        .sc-warn ul { margin: .35rem 0 0; padding-left: 1.25rem; list-style: disc; }
        .sc-dupe {
            margin-top: 1rem; padding: .75rem 1rem; border-radius: .5rem;
            background: #fee2e2; color: #991b1b; font-size: .875rem;
            border: 1px solid #fca5a5;
        }
        .dark .sc-dupe { background: #450a0a; color: #fca5a5; border-color: #7f1d1d; }
        .sc-dupe ul { margin: .35rem 0 0; padding-left: 1.25rem; list-style: disc; }
        .sc-table-wrap { margin-top: 1rem; overflow-x: auto; border: 1px solid #e5e7eb; border-radius: .5rem; }
        .dark .sc-table-wrap { border-color: #374151; }
        .sc-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .sc-table thead th {
            padding: .5rem .75rem; text-align: left; white-space: nowrap;
            background: #f3f4f6; border-bottom: 1px solid #e5e7eb;
        }
        .dark .sc-table thead th { background: #1f2937; border-color: #374151; }
        .sc-table tbody td { padding: .4rem .75rem; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .dark .sc-table tbody td { border-color: #1f2937; }
        .sc-table tbody tr:nth-child(even) { background: #f9fafb; }
        .dark .sc-table tbody tr:nth-child(even) { background: #1f2937; }
        .sc-dim { color: #9ca3af; width: 3rem; }
        .sc-wrap { min-width: 16rem; }
        .sc-num { text-align: right; white-space: nowrap; }
        .sc-actions-cell { width: 2.5rem; text-align: right; }
        .sc-del {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.75rem; height: 1.75rem; border-radius: .375rem;
            border: none; background: transparent; color: #9ca3af; cursor: pointer;
        }
        .sc-del:hover { color: #dc2626; background: #fef2f2; }
        .dark .sc-del:hover { background: #451a1a; }
        .sc-del-icon { width: 1rem; height: 1rem; }
        .sc-view {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.75rem; height: 1.75rem; border-radius: .375rem;
            border: none; background: transparent; color: #9ca3af; cursor: pointer;
        }
        .sc-view:hover { color: #1d4ed8; background: #eff6ff; }
        .dark .sc-view:hover { background: #1e3a8a; }
        .sc-actions-cell { white-space: nowrap; }
        .sc-row-open td { background: #eff6ff; }
        .dark .sc-row-open td { background: #1e3a8a; }
        .sc-detail { margin-top: .75rem; border: 1px solid #e5e7eb; border-radius: .5rem; overflow: hidden; }
        .dark .sc-detail { border-color: #374151; }
        .sc-detail-head {
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
            padding: .5rem .75rem; background: #f3f4f6; font-size: .875rem;
        }
        .dark .sc-detail-head { background: #1f2937; }
        .sc-badge-src {
            display: inline-block; padding: .1rem .5rem; border-radius: 9999px;
            background: #e0e7ff; color: #3730a3; font-size: .7rem; font-weight: 600;
            white-space: nowrap;
        }
        .dark .sc-badge-src { background: #312e81; color: #c7d2fe; }
        .sc-mono-sm { font-size: .75rem; }
        .sc-row-dupe td { background: #fef2f2; }
        .dark .sc-row-dupe td { background: #450a0a; }
        .sc-badge-dupe {
            display: inline-block; padding: .1rem .5rem; border-radius: 9999px;
            background: #fecaca; color: #991b1b; font-size: .7rem; font-weight: 700;
            white-space: nowrap;
        }
        .dark .sc-badge-dupe { background: #7f1d1d; color: #fecaca; }
        .sc-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; white-space: nowrap; font-size: .8rem; }
        .sc-riwayat { margin-top: 1.5rem; display: grid; gap: .5rem; }
        .sc-riwayat-title { margin: 0; font-size: 1rem; font-weight: 600; }
        .sc-nomor {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 600; color: #1d4ed8; cursor: pointer;
            background: none; border: none; padding: 0; white-space: nowrap;
        }
        .dark .sc-nomor { color: #93c5fd; }
        .sc-nomor:hover { text-decoration: underline; }
        .sc-empty {
            margin-top: 1rem; padding: 2.5rem 1rem; text-align: center; font-size: .875rem;
            color: #6b7280; border: 1px dashed #d1d5db; border-radius: .5rem;
        }
        .dark .sc-empty { color: #9ca3af; border-color: #4b5563; }
    </style>
    @endassets
</x-filament-panels::page>
