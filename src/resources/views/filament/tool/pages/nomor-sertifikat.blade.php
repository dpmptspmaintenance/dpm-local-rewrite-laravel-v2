<x-filament-panels::page>
    {{ $this->table }}

    @php($activity = $this->currentRowsActivity)

    @if ($activity !== null)
        <div class="cn-overlay" wire:click.self="closeRows">
            <div class="cn-panel">
                <div class="cn-panel-header">
                    <div>
                        <h2 class="cn-panel-title">{{ $activity->kode }}{{ $activity->nomor_awal }}/{{ $activity->tahun }} — {{ $activity->nomorRangeLabel() }}</h2>
                        <p class="cn-panel-desc">{{ $activity->kegiatan }}</p>
                        <p class="cn-panel-meta">
                            {{ $activity->tempat ?: 'Tempat belum diisi' }}
                            @if ($activity->tanggal)
                                · {{ $activity->tanggal->locale('id')->translatedFormat('j F Y') }}
                            @endif
                        </p>
                    </div>
                    <button type="button" class="cn-icon-btn" wire:click="closeRows" title="Tutup">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="cn-panel-toolbar">
                    <x-filament::button
                        type="button"
                        icon="heroicon-o-arrow-up-tray"
                        wire:click="openExcelDialog"
                    >
                        Impor Excel
                    </x-filament::button>
                    <span class="cn-hint">Berkas Excel tidak disimpan — hanya datanya.</span>
                    <x-filament::button
                        type="button"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                        style="margin-left: auto;"
                        wire:click="emptyRows"
                        wire:confirm="Kosongkan semua baris pada kegiatan ini? Baris berikutnya akan mulai lagi dari nomor awal."
                        :disabled="count($this->activityRows) === 0"
                    >
                        Kosongkan
                    </x-filament::button>
                </div>

                <div class="cn-add-block">
                    <label class="cn-add-label">Tambah Nama (satu nama per baris)</label>
                    <x-filament::input.wrapper>
                        <textarea
                            rows="5"
                            wire:model="bulkNama"
                            class="cn-textarea"
                            placeholder="ANTON SISWARTONO, S.Sos.MM&#10;HANGGORO LARAS SAKTI, ST.&#10;AHMAD ROSIDIN, S.IP"
                        ></textarea>
                    </x-filament::input.wrapper>
                    <div class="cn-add-actions">
                        <span class="cn-hint">
                            1 baris = 1 penerima. Tempel (Ctrl+V) daftar nama — nomor otomatis lanjut.
                        </span>
                        <x-filament::button type="button" icon="heroicon-o-plus" size="sm" wire:click="addRowsFromText">
                            Tambah Nama
                        </x-filament::button>
                    </div>
                </div>

                <div class="cn-table-wrap">
                    <table class="cn-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor Surat</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->activityRows as $row)
                                <tr wire:key="cn-row-{{ $row->id }}">
                                    <td class="cn-num">{{ $row->nomor }}</td>
                                    <td class="cn-mono">{{ $row->nomor_surat }}</td>
                                    <td>{{ $row->nama }}</td>
                                    <td>{{ $row->keterangan ?: '—' }}</td>
                                    <td class="cn-actions">
                                        <x-filament::icon-button
                                            icon="heroicon-o-trash"
                                            color="danger"
                                            size="sm"
                                            tooltip="Hapus baris"
                                            wire:click="deleteRow({{ $row->id }})"
                                            wire:confirm="Hapus baris ini?"
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="cn-empty">
                                        Belum ada baris. Tambah manual atau impor dari Excel.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($showExcelDialog)
        <div class="cn-overlay cn-overlay-top" wire:click.self="closeExcelDialog">
            <div class="cn-panel">
                <div class="cn-panel-header">
                    <div>
                        <h2 class="cn-panel-title">Impor Excel</h2>
                        <p class="cn-panel-desc">
                            Baris pertama berkas dianggap header. Pilih kolom mana yang dipakai untuk Nama dan Keterangan —
                            kolom lain diabaikan. Berkas tidak disimpan.
                        </p>
                    </div>
                    <button type="button" class="cn-icon-btn" wire:click="closeExcelDialog" title="Tutup">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="cn-panel-body">
                    <div class="cn-toolbar">
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv,text/csv"
                            wire:model="excelUpload"
                            class="fi-input cn-file"
                        >
                        <span wire:loading wire:target="excelUpload" class="cn-hint">Membaca berkas…</span>
                        @if ($excelName !== '')
                            <span class="cn-hint">{{ $excelName }}</span>
                        @endif
                        @if ($excelRowCount > 0)
                            <span class="cn-badge">{{ $excelRowCount }} baris · {{ count($excelHeaders) }} kolom</span>
                        @endif
                    </div>

                    @if ($excelRowCount === 0)
                        <p class="cn-empty-box">
                            Belum ada berkas. Pilih Excel/CSV untuk melihat kolom dan pratinjau datanya.
                        </p>
                    @else
                        <div class="cn-map-grid">
                            <div class="cn-add-field">
                                <label>Kolom Nama (wajib)</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model.live="mapNama">
                                        <option value="">— pilih kolom —</option>
                                        @foreach ($this->excelColumnOptions as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                            <div class="cn-add-field">
                                <label>Kolom Keterangan (opsional)</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model.live="mapKeterangan">
                                        <option value="">— tidak dipakai —</option>
                                        @foreach ($this->excelColumnOptions as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        </div>

                        <div class="cn-table-wrap">
                            <table class="cn-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        @foreach ($excelHeaders as $headerIndex => $headerLabel)
                                            <th>
                                                <span class="cn-th-label">{{ $headerLabel }}</span>
                                                <span class="cn-th-key">{{ $excelKeys[$headerIndex] ?? '' }}</span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($excelPreview as $rowIndex => $row)
                                        <tr>
                                            <td class="cn-num">{{ $rowIndex + 1 }}</td>
                                            @foreach ($excelKeys as $key)
                                                <td>{{ $row[$key] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($excelRowCount > count($excelPreview))
                            <p class="cn-hint">Menampilkan {{ count($excelPreview) }} baris pertama dari {{ $excelRowCount }} baris.</p>
                        @endif
                    @endif
                </div>

                <div class="cn-panel-footer">
                    <x-filament::button type="button" color="gray" wire:click="closeExcelDialog">
                        Batal
                    </x-filament::button>
                    <x-filament::button
                        type="button"
                        icon="heroicon-o-check"
                        wire:click="applyExcelImport"
                        wire:loading.attr="disabled"
                        wire:target="applyExcelImport"
                        :disabled="$excelRowCount === 0 || $mapNama === null || $mapNama === ''"
                    >
                        Terapkan
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />

    @assets
    <style>
        .cn-overlay {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
            padding: 1rem;
            background: rgba(17, 24, 39, .65);
        }
        .cn-overlay-top { z-index: 70; }
        .cn-panel {
            width: 100%;
            max-width: 68rem;
            margin: 1.5rem auto;
            border-radius: .75rem;
            background: #fff;
            color: #111827;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .45);
            overflow: hidden;
        }
        .dark .cn-panel { background: #111827; color: #f3f4f6; }
        .cn-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .cn-panel-header { border-color: #374151; }
        .cn-panel-title { margin: 0; font-size: 1.125rem; font-weight: 600; }
        .cn-panel-desc { margin: .25rem 0 0; font-size: .875rem; color: #6b7280; white-space: pre-line; }
        .dark .cn-panel-desc { color: #9ca3af; }
        .cn-panel-meta { margin: .35rem 0 0; font-size: .8rem; color: #4b5563; }
        .dark .cn-panel-meta { color: #d1d5db; }
        .cn-panel-body { padding: 1rem 1.25rem; display: grid; gap: 1rem; }
        .cn-panel-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .cn-panel-toolbar { border-color: #374151; }
        .cn-panel-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid #e5e7eb;
        }
        .dark .cn-panel-footer { border-color: #374151; }
        .cn-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: .4rem;
            border: 1px solid rgba(107, 114, 128, .4);
            background: #fff;
            color: #374151;
        }
        .dark .cn-icon-btn { background: #1f2937; color: #e5e7eb; border-color: #4b5563; }
        .cn-icon-btn:hover { border-color: #2563eb; color: #1d4ed8; }
        .cn-hint { font-size: .8rem; color: #6b7280; }
        .dark .cn-hint { color: #9ca3af; }
        .cn-badge {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 9999px;
            background: #dcfce7;
            color: #166534;
            font-size: .75rem;
            font-weight: 600;
        }
        .cn-file { width: auto; max-width: 100%; cursor: pointer; font-size: .875rem; }
        .cn-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .cn-add-block {
            display: grid;
            gap: .4rem;
            padding: .75rem 1.25rem;
        }
        .cn-add-label { font-size: .8rem; color: #4b5563; }
        .dark .cn-add-label { color: #d1d5db; }
        .cn-add-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }
        .cn-textarea {
            display: block;
            width: 100%;
            border: none;
            background: transparent;
            padding: .5rem .75rem;
            font-size: .875rem;
            line-height: 1.5;
            color: #111827;
            outline: none;
            resize: vertical;
            font-family: inherit;
        }
        .dark .cn-textarea { color: #f3f4f6; }
        .cn-textarea::placeholder { color: #9ca3af; }
        .cn-add-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: .6rem;
            padding: .75rem 1.25rem;
        }
        .cn-map-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: minmax(0, 1fr);
        }
        @media (min-width: 640px) {
            .cn-map-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .cn-add-field { display: grid; gap: .25rem; font-size: .8rem; min-width: 12rem; flex: 1 1 12rem; }
        .cn-add-field > label { color: #4b5563; }
        .dark .cn-add-field > label { color: #d1d5db; }
        .cn-table-wrap { max-height: 55vh; overflow: auto; border: 1px solid #e5e7eb; border-radius: .5rem; }
        .dark .cn-table-wrap { border-color: #374151; }
        .cn-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .cn-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: .5rem .75rem;
            text-align: left;
            white-space: nowrap;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .cn-table thead th { background: #1f2937; border-color: #374151; }
        .cn-table tbody td { padding: .35rem .75rem; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .dark .cn-table tbody td { border-color: #1f2937; }
        .cn-table tbody tr:nth-child(odd) { background: #fff; }
        .cn-table tbody tr:nth-child(even) { background: #f9fafb; }
        .dark .cn-table tbody tr:nth-child(odd) { background: #111827; }
        .dark .cn-table tbody tr:nth-child(even) { background: #1f2937; }
        .cn-num { color: #9ca3af; width: 4rem; }
        .cn-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; white-space: nowrap; }
        .cn-actions { width: 3.5rem; text-align: right; }
        .cn-th-label { display: block; font-weight: 600; }
        .cn-th-key { display: block; font-weight: 400; font-size: .7rem; color: #9ca3af; }
        .cn-empty { padding: 2rem 1rem; text-align: center; color: #6b7280; }
        .dark .cn-empty { color: #9ca3af; }
        .cn-empty-box {
            margin: 0;
            padding: 2.5rem 1rem;
            text-align: center;
            font-size: .875rem;
            color: #6b7280;
            border: 1px dashed #d1d5db;
            border-radius: .5rem;
        }
        .dark .cn-empty-box { color: #9ca3af; border-color: #4b5563; }
    </style>
    @endassets
</x-filament-panels::page>
