<x-filament-panels::page>
    <form wire:submit="save" class="fi-form grid gap-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check-circle" wire:loading.attr="disabled">
                Simpan Template
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-table-cells"
                wire:click="openExcelDialog"
            >
                Data Excel
                @if ($datasetLoaded && $rows !== [])
                    <span class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ count($rows) }}
                    </span>
                @endif
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-hashtag"
                wire:click="openNumbersDialog"
            >
                Ambil Data dari Nomor Surat
            </x-filament::button>

            <span wire:loading wire:target="save" class="text-sm text-gray-500 dark:text-gray-400">
                Memproses…
            </span>
        </div>
    </form>

    @if ($datasetLoaded)
        <x-filament::section heading="Field Data" description="Tarik kolom ke kanvas untuk menempatkan field, atau klik untuk menaruhnya di posisi awal.">
            <div class="flex flex-wrap gap-2">
                @foreach ($keys as $headerIndex => $headerKey)
                    @php
                        $headerLabel = $headers[$headerIndex] ?? $headerKey;
                        $already = collect($fields)->contains(fn (array $f): bool => $f['key'] === $headerKey);
                    @endphp
                    <button
                        type="button"
                        draggable="true"
                        data-cert-key="{{ $headerKey }}"
                        class="cert-chip {{ $already ? 'is-used' : '' }}"
                        wire:click="addField({{ Illuminate\Support\Js::from($headerKey) }})"
                        title="Tarik ke kanvas atau klik untuk menambah field"
                    >
                        <x-filament::icon icon="heroicon-o-bars-3" class="h-4 w-4" />
                        <span>{{ $headerLabel }}</span>
                    </button>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-gray-400">Tip: tarik langsung ke posisi yang diinginkan — field diletakkan tepat di titik lepasnya.</p>
        </x-filament::section>
    @endif

    {{-- Large display: kanvas (3) dan properti field (1) dalam satu baris. --}}
    <div class="cert-editor-grid">
    <x-filament::section heading="Kanvas Template">
        @if (blank($backgroundUrl))
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Unggah gambar template di atas — kanvas langsung tampil dan siap ditempatkan field.
            </p>
        @else
            <div
                class="cert-canvas"
                x-data="certCanvas({
                    fields: @js($fields),
                    pageWidthMm: @js($pageWidthMm),
                    pageHeightMm: @js($pageHeightMm),
                    backgroundUrl: @js($backgroundUrl),
                    previewRow: @js($previewRow),
                    showPreview: @js($showPreview),
                    selectedIndex: @js($selectedFieldIndex),
                    fontGenerics: @js($fontGenerics),
                })"
                x-init="boot()"
                x-effect="sync($wire.fields, $wire.selectedFieldIndex, $wire.previewRow, $wire.backgroundUrl, $wire.showPreview)"
            >
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <label class="text-sm text-gray-600 dark:text-gray-300">Zoom</label>
                    <input type="range" min="20" max="100" step="5" x-model.number="zoom" class="w-48">
                    <span class="text-sm text-gray-500" x-text="zoom + '%'"></span>
                    <span class="text-sm text-gray-500">
                        Halaman: <strong x-text="round(pageWidthMm) + ' × ' + round(pageHeightMm) + ' mm'"></strong>
                    </span>
                    <span class="text-xs text-gray-400">Tarik kolom ke kanvas · tarik field untuk memindah · handle untuk ukuran · Delete untuk hapus</span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300" x-show="drag !== null" x-text="dragInfo"></span>
                </div>

                @if ($datasetLoaded && $rows !== [])
                    <div class="cert-preview-bar">
                        <button
                            type="button"
                            class="cert-icon-btn"
                            title="{{ $showPreview ? 'Sembunyikan isi data (lihat posisi field saja)' : 'Tampilkan isi data pada field' }}"
                            wire:click="togglePreview"
                        >
                            <x-filament::icon :icon="$showPreview ? 'heroicon-o-eye' : 'heroicon-o-eye-slash'" class="h-4 w-4" />
                        </button>

                        @if ($showPreview)
                            <button
                                type="button"
                                class="cert-icon-btn"
                                title="Data sebelumnya"
                                wire:click="previousPreviewRow"
                                @disabled($datasetPreviewIndex === null || $datasetPreviewIndex <= 0)
                            >
                                <x-filament::icon icon="heroicon-o-chevron-left" class="h-4 w-4" />
                            </button>

                            <span class="cert-preview-goto">
                                <span class="cert-preview-goto-label">Data</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="{{ count($rows) }}"
                                    inputmode="numeric"
                                    class="fi-input cert-preview-goto-input"
                                    title="Ketik nomor data lalu Enter untuk lompat langsung"
                                    wire:model="previewGoto"
                                    wire:keydown.enter.prevent="gotoPreviewRow"
                                    wire:blur="gotoPreviewRow"
                                >
                                <span class="cert-preview-goto-total">/ {{ count($rows) }}</span>
                            </span>

                            <button
                                type="button"
                                class="cert-icon-btn"
                                title="Data berikutnya"
                                wire:click="nextPreviewRow"
                                @disabled($datasetPreviewIndex === null || $datasetPreviewIndex >= count($rows) - 1)
                            >
                                <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                            </button>

                            <span class="cert-preview-sep"></span>

                            <button
                                type="button"
                                class="cert-icon-btn"
                                title="Unduh data ini (PDF)"
                                wire:click="downloadSingle({{ (int) $datasetPreviewIndex }})"
                                wire:loading.attr="disabled"
                                wire:target="downloadSingle"
                            >
                                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                            </button>
                        @endif

                        <button
                            type="button"
                            class="cert-icon-btn"
                            title="Unduh batch ({{ count($rows) }} data, per 25 PDF)"
                            wire:click="mountAction('downloadBatchRange')"
                            wire:loading.attr="disabled"
                        >
                            <x-filament::icon icon="heroicon-o-archive-box-arrow-down" class="h-4 w-4" />
                        </button>
                    </div>
                @endif

                <div class="cert-viewport" x-ref="viewport" wire:ignore>
                    <div
                        class="cert-page"
                        x-ref="page"
                        :class="dragOver ? 'is-drop-target' : ''"
                        :style="'width:' + (pageWidthMm * pxPerMm * zoom / 100) + 'px;height:' + (pageHeightMm * pxPerMm * zoom / 100) + 'px;'"
                        @pointerdown.self="startPan($event)"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="dropField($event)"
                    >
                        <img
                            :src="backgroundUrl"
                            class="cert-bg"
                            alt="Template"
                            x-ref="bg"
                        >

                        {{-- Garis bantu tengah halaman: tampil saat drag/resize
                             supaya posisi tengah mudah dicek (menyala hijau
                             bila field tepat di tengah). --}}
                        <div class="cert-guide cert-guide-v" :class="guides.v ? 'is-active' : ''" x-show="drag !== null || dragOver"></div>
                        <div class="cert-guide cert-guide-h" :class="guides.h ? 'is-active' : ''" x-show="drag !== null || dragOver"></div>

                        <template x-for="(field, index) in fields" :key="field.uid">
                            <div
                                class="cert-field"
                                :class="index === selectedIndex ? 'is-selected' : ''"
                                :style="fieldStyle(field)"
                                @pointerdown.prevent="startDrag($event, index, 'move')"
                                @keydown.delete.window="if (index === selectedIndex) $wire.removeField(index)"
                            >
                                <div class="cert-field-text" :style="textStyle(field)" x-text="displayValue(field)"></div>

                                <template x-if="index === selectedIndex">
                                    <div>
                                        <span class="cert-handle h-nw" @pointerdown.stop.prevent="startDrag($event, index, 'nw')"></span>
                                        <span class="cert-handle h-n"  @pointerdown.stop.prevent="startDrag($event, index, 'n')"></span>
                                        <span class="cert-handle h-ne" @pointerdown.stop.prevent="startDrag($event, index, 'ne')"></span>
                                        <span class="cert-handle h-e"  @pointerdown.stop.prevent="startDrag($event, index, 'e')"></span>
                                        <span class="cert-handle h-se" @pointerdown.stop.prevent="startDrag($event, index, 'se')"></span>
                                        <span class="cert-handle h-s"  @pointerdown.stop.prevent="startDrag($event, index, 's')"></span>
                                        <span class="cert-handle h-sw" @pointerdown.stop.prevent="startDrag($event, index, 'sw')"></span>
                                        <span class="cert-handle h-w"  @pointerdown.stop.prevent="startDrag($event, index, 'w')"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Kolom kanan selalu ada di layar besar supaya kanvas konsisten
         3:1 dan tidak "hilang-muncul". Saat belum ada field dipilih,
         tampilkan card kosong berisi petunjuk. --}}
    <div class="cert-editor-side">
    @if ($selectedFieldIndex === null || ! isset($fields[$selectedFieldIndex]))
        <x-filament::section heading="Properti Field">
            <p class="cert-props-empty">
                Pilih field di kanvas untuk mengatur label, font, warna, dan perataan.
            </p>
        </x-filament::section>
    @else
        @php $f = $fields[$selectedFieldIndex]; @endphp
        <x-filament::section heading="Properti Field">
            <div class="cert-field-props grid grid-cols-1 gap-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Label</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model.blur="fields.{{ $selectedFieldIndex }}.label" />
                    </x-filament::input.wrapper>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Kolom Data</span>
                    <x-filament::input.wrapper disabled>
                        <x-filament::input type="text" value="{{ $f['key'] }}" disabled />
                    </x-filament::input.wrapper>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Font</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:change="updateFieldProperties({{ $selectedFieldIndex }}, { font_family: $event.target.value })">
                            @foreach ($fontOptions as $font)
                                <option value="{{ $font['family'] }}" @selected($f['font_family'] === $font['family'])>{{ $font['label'] }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Ukuran (pt)</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="4" max="200" step="0.5" value="{{ $f['font_size_pt'] }}"
                            wire:change="updateFieldProperties({{ $selectedFieldIndex }}, { font_size_pt: $event.target.value })" />
                    </x-filament::input.wrapper>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Warna</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="color" class="h-9" value="{{ $f['color'] }}"
                            wire:change="updateFieldProperties({{ $selectedFieldIndex }}, { color: $event.target.value })" />
                    </x-filament::input.wrapper>
                </label>
                <div class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Gaya</span>
                    <div class="flex gap-2">
                        <x-filament::button type="button" size="xs" :color="$f['bold'] ? 'primary' : 'gray'"
                            wire:click="updateFieldProperties({{ $selectedFieldIndex }}, { bold: {{ $f['bold'] ? 'false' : 'true' }} })">
                            <strong>B</strong>
                        </x-filament::button>
                        <x-filament::button type="button" size="xs" :color="$f['italic'] ? 'primary' : 'gray'"
                            wire:click="updateFieldProperties({{ $selectedFieldIndex }}, { italic: {{ $f['italic'] ? 'false' : 'true' }} })">
                            <em>I</em>
                        </x-filament::button>
                    </div>
                </div>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Rata Teks</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:change="updateFieldProperties({{ $selectedFieldIndex }}, { align: $event.target.value })">
                            @foreach (['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'] as $value => $label)
                                <option value="{{ $value }}" @selected($f['align'] === $value)>{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Rata Vertikal</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:change="updateFieldProperties({{ $selectedFieldIndex }}, { valign: $event.target.value })">
                            @foreach (['top' => 'Atas', 'middle' => 'Tengah', 'bottom' => 'Bawah'] as $value => $label)
                                <option value="{{ $value }}" @selected($f['valign'] === $value)>{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                <div class="grid gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Posisi &amp; Ukuran (mm)</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Bisa diisi manual untuk presisi. Halaman {{ $pageWidthMm }} × {{ $pageHeightMm }} mm.
                    </span>
                    <div class="cert-geometry-grid">
                        @foreach (['x_mm' => 'X', 'y_mm' => 'Y', 'w_mm' => 'Lebar', 'h_mm' => 'Tinggi'] as $part => $partLabel)
                            <label class="cert-geometry-item">
                                <span>{{ $partLabel }}</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        value="{{ $f[$part] }}"
                                        wire:change="updateFieldGeometryPart({{ $selectedFieldIndex }}, '{{ $part }}', $event.target.value)"
                                    />
                                </x-filament::input.wrapper>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <x-filament::button type="button" color="danger" size="sm" icon="heroicon-o-trash"
                    wire:click="removeField({{ $selectedFieldIndex }})"
                    wire:confirm="Hapus field ini dari template?">
                    Hapus Field
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
    </div>
    </div>

    @assets
    <style>
        {!! $fontFaceCss !!}
        .cert-canvas { width: 100%; }
        .cert-viewport {
            position: relative;
            overflow: auto;
            cursor: grab;
            background: #1f2937;
            padding: 1rem;
            border-radius: .5rem;
            max-height: 75vh;
        }
        .cert-page {
            position: relative;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,.4);
            overflow: hidden;
            user-select: none;
            touch-action: none;
        }
        .cert-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            pointer-events: none;
        }
        .cert-field {
            position: absolute;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            cursor: move;
            outline: 1px dashed rgba(59,130,246,.45);
            box-sizing: border-box;
        }
        .cert-field.is-selected {
            outline: 2px solid #2563eb;
            background: rgba(37,99,235,.06);
        }
        .cert-field-text {
            width: 100%;
            line-height: 1.15;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .cert-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #fff;
            border: 2px solid #2563eb;
            border-radius: 2px;
            z-index: 5;
        }
        .h-nw { left: -5px; top: -5px; cursor: nwse-resize; }
        .h-n  { left: calc(50% - 5px); top: -5px; cursor: ns-resize; }
        .h-ne { right: -5px; top: -5px; cursor: nesw-resize; }
        .h-e  { right: -5px; top: calc(50% - 5px); cursor: ew-resize; }
        .h-se { right: -5px; bottom: -5px; cursor: nwse-resize; }
        .h-s  { left: calc(50% - 5px); bottom: -5px; cursor: ns-resize; }
        .h-sw { left: -5px; bottom: -5px; cursor: nesw-resize; }
        .h-w  { left: -5px; top: calc(50% - 5px); cursor: ew-resize; }
        .cert-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .7rem;
            font-size: .8rem;
            font-weight: 500;
            border-radius: .5rem;
            border: 1px solid rgba(107,114,128,.45);
            background: rgba(243,244,246,.9);
            color: #1f2937;
            cursor: grab;
            user-select: none;
        }
        .cert-chip:hover { border-color: #2563eb; color: #1d4ed8; }
        .cert-chip.is-used { border-color: #16a34a; color: #15803d; background: rgba(220,252,231,.9); }
        .cert-chip:active { cursor: grabbing; }
        .cert-page.is-drop-target { outline: 3px dashed #2563eb; outline-offset: 2px; }
        .cert-viewport.is-panning { cursor: grabbing; }
        .cert-viewport.is-panning .cert-field { cursor: grabbing; }
        .cert-guide {
            position: absolute;
            pointer-events: none;
            z-index: 4;
            opacity: .75;
            transition: opacity .08s linear;
        }
        .cert-guide-v {
            top: 0;
            bottom: 0;
            left: 50%;
            border-left: 1px dashed rgba(239,68,68,.75);
        }
        .cert-guide-h {
            left: 0;
            right: 0;
            top: 50%;
            border-top: 1px dashed rgba(239,68,68,.75);
        }
        .cert-guide.is-active {
            opacity: 1;
            border-color: #22c55e;
            border-width: 2px;
        }
        .cert-preview-bar {
            position: relative;
            margin-bottom: .6rem;
            z-index: 2;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
            padding: .35rem .5rem;
            border-radius: .5rem;
            background: rgba(255,255,255,.94);
            border: 1px solid rgba(107,114,128,.35);
            box-shadow: 0 4px 14px rgba(0,0,0,.25);
            color: #1f2937;
        }
        .cert-preview-sep {
            width: 1px;
            height: 1.4rem;
            background: rgba(107,114,128,.35);
            margin: 0 .15rem;
        }
        .cert-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: .4rem;
            border: 1px solid rgba(107,114,128,.4);
            background: #fff;
            color: #374151;
        }
        .cert-icon-btn:hover:not(:disabled) { border-color: #2563eb; color: #1d4ed8; }
        .cert-icon-btn:disabled { opacity: .4; cursor: not-allowed; }
        .cert-preview-select { max-width: 22rem; }
        .cert-preview-goto {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .8rem;
            color: #374151;
            white-space: nowrap;
        }
        .dark .cert-preview-goto { color: #e5e7eb; }
        .cert-preview-goto-label { font-weight: 600; }
        .cert-preview-goto-total { color: #6b7280; }
        .dark .cert-preview-goto-total { color: #9ca3af; }
        .cert-preview-goto-input {
            width: 3.6rem;
            padding: .15rem .35rem;
            text-align: center;
            font-size: .8rem;
            line-height: 1.2;
            border: 1px solid rgba(107, 114, 128, .45);
            border-radius: .35rem;
            background: #fff;
            color: #111827;
        }
        .dark .cert-preview-goto-input { background: #1f2937; color: #f3f4f6; border-color: #4b5563; }
        .cert-preview-goto-input:focus { outline: 2px solid #2563eb; outline-offset: 1px; }
        /* Sembunyikan spinner angka bawaan supaya kotak tetap ringkas. */
        .cert-preview-goto-input::-webkit-outer-spin-button,
        .cert-preview-goto-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .cert-preview-goto-input { -moz-appearance: textfield; appearance: textfield; }

        /* Kanvas + kolom properti selalu 3 : 1 di layar lebar. Kolom kanan
           dibiarkan kosong saat belum ada field terpilih. */
        .cert-editor-grid {
            display: grid;
            gap: 1.5rem;
            align-items: start;
            grid-template-columns: minmax(0, 1fr);
        }
        @media (min-width: 1280px) {
            .cert-editor-grid {
                grid-template-columns: minmax(0, 3fr) minmax(0, 1fr);
            }
        }
        .cert-props-empty {
            margin: 0;
            padding: 1.5rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            color: #6b7280;
            text-align: center;
        }
        .dark .cert-props-empty { color: #9ca3af; }
        .cert-geometry-grid {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .cert-geometry-item {
            display: grid;
            gap: .15rem;
            font-size: .75rem;
            color: #4b5563;
        }
        .dark .cert-geometry-item { color: #d1d5db; }
        .cert-field-props { grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 640px) {
            .cert-field-props { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1280px) {
            /* Di kolom kanan selebar 1/4, satu kolom lebih terbaca. */
            .cert-geometry-grid {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .cert-geometry-item {
            display: grid;
            gap: .15rem;
            font-size: .75rem;
            color: #4b5563;
        }
        .dark .cert-geometry-item { color: #d1d5db; }
        .cert-field-props { grid-template-columns: minmax(0, 1fr); }
        }

        /* Dialog Data Excel — CSS sendiri (bukan utility Tailwind) karena
           CSS Filament sudah terkompilasi dan tidak memuat semua utility. */
        .cert-modal-overlay {
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
        .cert-modal-overlay-top { z-index: 70; }
        .cert-modal.cert-modal-narrow { max-width: 34rem; }
                .cert-num-search {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }
        .cert-num-search > .fi-input-wrp { flex: 1 1 18rem; }
        .cert-num-search-count { font-size: .8rem; color: #6b7280; white-space: nowrap; }
        .dark .cert-num-search-count { color: #9ca3af; }
        .cert-num-table-wrap {
            max-height: 55vh;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
        }
        .dark .cert-num-table-wrap { border-color: #374151; }
        .cert-num-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .cert-num-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: .5rem .75rem;
            text-align: left;
            white-space: nowrap;
            font-weight: 600;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .cert-num-table thead th { background: #1f2937; border-color: #374151; }
        .cert-num-table tbody td {
            padding: .5rem .75rem;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .cert-num-table tbody td { border-color: #1f2937; }
        .cert-num-row { cursor: pointer; }
        .cert-num-row:hover { background: rgba(37, 99, 235, .06); }
        .cert-num-row.is-selected { background: rgba(37, 99, 235, .12); }
        .cert-num-row.is-selected .cert-num-dot {
            border-color: #2563eb;
            background: #2563eb;
            box-shadow: inset 0 0 0 2px #fff;
        }
        .cert-num-check { width: 2.25rem; }
        .cert-num-dot {
            display: inline-block;
            width: .9rem;
            height: .9rem;
            border-radius: 9999px;
            border: 2px solid #9ca3af;
            background: transparent;
            vertical-align: middle;
        }
        .cert-num-kegiatan { min-width: 18rem; max-width: 30rem; }
        .cert-num-meta { white-space: nowrap; color: #4b5563; }
        .dark .cert-num-meta { color: #d1d5db; }
        .cert-num-dim { color: #9ca3af; }
        .cert-num-range { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; white-space: nowrap; }
        .cert-num-badge {
            display: inline-block;
            padding: .1rem .5rem;
            border-radius: 9999px;
            background: #e5e7eb;
            color: #374151;
            font-size: .75rem;
            font-weight: 600;
        }
        .dark .cert-num-badge { background: #374151; color: #e5e7eb; }
        .cert-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: .4rem;
            border: 1px solid rgba(107,114,128,.4);
            background: #fff;
            color: #374151;
        }
        .cert-icon-btn:hover:not(:disabled) { border-color: #2563eb; color: #1d4ed8; }
        .cert-icon-btn:disabled { opacity: .4; cursor: not-allowed; }
        .cert-preview-select { max-width: 22rem; }

        /* Kanvas + kolom properti selalu 3 : 1 di layar lebar. Kolom kanan
           dibiarkan kosong saat belum ada field terpilih. */
        .cert-editor-grid {
            display: grid;
            gap: 1.5rem;
            align-items: start;
            grid-template-columns: minmax(0, 1fr);
        }
        @media (min-width: 1280px) {
            .cert-editor-grid {
                grid-template-columns: minmax(0, 3fr) minmax(0, 1fr);
            }
        }
        .cert-props-empty {
            margin: 0;
            padding: 1.5rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            color: #6b7280;
            text-align: center;
        }
        .dark .cert-props-empty { color: #9ca3af; }
        .cert-geometry-grid {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .cert-geometry-item {
            display: grid;
            gap: .15rem;
            font-size: .75rem;
            color: #4b5563;
        }
        .dark .cert-geometry-item { color: #d1d5db; }
        .cert-field-props { grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 640px) {
            .cert-field-props { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1280px) {
            /* Di kolom kanan selebar 1/4, satu kolom lebih terbaca. */
            .cert-geometry-grid {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .cert-geometry-item {
            display: grid;
            gap: .15rem;
            font-size: .75rem;
            color: #4b5563;
        }
        .dark .cert-geometry-item { color: #d1d5db; }
        .cert-field-props { grid-template-columns: minmax(0, 1fr); }
        }

        /* Dialog Data Excel — CSS sendiri (bukan utility Tailwind) karena
           CSS Filament sudah terkompilasi dan tidak memuat semua utility. */
        .cert-modal-overlay {
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
        .cert-modal-overlay-top { z-index: 70; }
        .cert-modal.cert-modal-narrow { max-width: 34rem; }
        .cert-modal.cert-modal-wide { max-width: 64rem; }
        .cert-modal {
            width: 100%;
            max-width: 72rem;
            margin: 1.5rem auto;
            border-radius: .75rem;
            background: #fff;
            color: #111827;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .45);
            overflow: hidden;
        }
        .dark .cert-modal { background: #111827; color: #f3f4f6; }
        .cert-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .cert-modal-header { border-color: #374151; }
        .cert-modal-title { margin: 0; font-size: 1.125rem; font-weight: 600; }
        .cert-modal-desc { margin: .25rem 0 0; font-size: .875rem; color: #6b7280; }
        .dark .cert-modal-desc { color: #9ca3af; }
        .cert-modal-body { display: grid; gap: 1rem; padding: 1rem 1.25rem; }
        .cert-modal-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .cert-modal-file { width: auto; max-width: 100%; cursor: pointer; font-size: .875rem; }
        .cert-modal-hint { font-size: .875rem; color: #4b5563; }
        .dark .cert-modal-hint { color: #d1d5db; }
        .cert-modal-badge {
            display: inline-block;
            padding: .15rem .55rem;
            border-radius: 9999px;
            background: #dcfce7;
            color: #166534;
            font-size: .75rem;
            font-weight: 600;
        }
        .cert-modal-empty {
            margin: 0;
            padding: 2.5rem 1rem;
            text-align: center;
            font-size: .875rem;
            color: #6b7280;
            border: 1px dashed #d1d5db;
            border-radius: .5rem;
        }
        .dark .cert-modal-empty { color: #9ca3af; border-color: #4b5563; }
        .cert-modal-table-wrap {
            max-height: 55vh;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
        }
        .dark .cert-modal-table-wrap { border-color: #374151; }
        .cert-modal-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .cert-modal-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: .5rem .75rem;
            text-align: left;
            white-space: nowrap;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .cert-modal-table thead th { background: #1f2937; border-color: #374151; }
        .cert-modal-th-label { display: block; font-weight: 600; color: #111827; }
        .dark .cert-modal-th-label { color: #f3f4f6; }
        .cert-modal-th-key { display: block; font-weight: 400; font-size: .75rem; color: #9ca3af; }
        .cert-modal-table tbody td {
            padding: .35rem .75rem;
            white-space: nowrap;
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .cert-modal-table tbody td { border-color: #1f2937; }
        .cert-modal-table tbody tr:nth-child(odd) { background: #fff; }
        .cert-modal-table tbody tr:nth-child(even) { background: #f9fafb; }
        .dark .cert-modal-table tbody tr:nth-child(odd) { background: #111827; }
        .dark .cert-modal-table tbody tr:nth-child(even) { background: #1f2937; }
        .cert-modal-cell-num { color: #9ca3af; }
        .cert-modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid #e5e7eb;
        }
        .dark .cert-modal-footer { border-color: #374151; }
        .cert-modal-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    </style>
    @endassets

    @script
    <script>
        // Chip kolom Excel: HTML5 drag start menyimpan key di dataTransfer.
        document.addEventListener('dragstart', function (event) {
            const chip = event.target.closest('[data-cert-key]');
            if (!chip) return;
            event.dataTransfer.setData('text/plain', chip.getAttribute('data-cert-key'));
            event.dataTransfer.effectAllowed = 'copy';
        }, true);
        window.certCanvas = function (config) {
            let uidSeq = 0;

            // uid WAJIB ada sebelum x-for merender: field tanpa uid membuat
            // Alpine memperlakukan setiap baris sebagai elemen baru.
            const initialFields = (config.fields || []).map((f) => {
                if (!f.uid) {
                    f.uid = 'f' + (uidSeq++) + '-' + Math.random().toString(36).slice(2, 8);
                }
                return f;
            });

            return {
                fields: initialFields,
                zoom: 70,
                pxPerMm: 96 / 25.4,
                pageWidthMm: config.pageWidthMm,
                pageHeightMm: config.pageHeightMm,
                backgroundUrl: config.backgroundUrl,
                previewRow: config.previewRow || {},
                fontGenerics: config.fontGenerics || {},
                showPreview: config.showPreview !== false,
                selectedIndex: config.selectedIndex,
                drag: null,
                pan: null,
                dragOver: false,
                // status garis bantu tengah (aktif bila field tepat di tengah)
                guides: { v: false, h: false },
                dragInfo: '',
                uidSeq: uidSeq,
                // index -> true selama menunggu konfirmasi geometri dari server
                pendingGeometry: {},

                boot() {
                    // Field sudah dinormalisasi di inisialisasi state (uid
                    // harus sudah ada SEBELUM x-for merender) — jangan
                    // menimpa di sini, karena boot() jalan setelah x-for.
                    this.selectedIndex = config.selectedIndex;
                },

                normalize(list) {
                    return (list || []).map((f) => {
                        if (!f.uid) {
                            f.uid = 'f' + (this.uidSeq++) + '-' + Math.random().toString(36).slice(2, 8);
                        }
                        return f;
                    });
                },

                sync(fields, selectedIndex, previewRow, backgroundUrl, showPreview) {
                    const incoming = this.normalize(JSON.parse(JSON.stringify(fields || [])));

                    // Pertahankan uid lama berdasarkan urutan/identitas field:
                    // server tidak menyimpan uid, jadi tiap render PHP
                    // mengirim objek tanpa uid. Tanpa pemetaan ini x-for
                    // menganggap semua baris baru dan mengosongkan kanvas.
                    const previous = this.fields;

                    incoming.forEach((f, i) => {
                        const old = previous[i];

                        if (old) {
                            f.uid = old.uid;
                        }

                        // Posisi lokal dipertahankan sampai server mengirim
                        // nilai yang sama. Tanpa ini, tiap round-trip Livewire
                        // merender nilai server yang masih lama sehingga field
                        // tampak "lompat balik" (blink) sebelum ke posisi baru.
                        if (old && this.pendingGeometry[i]) {
                            const same =
                                Math.abs(old.x_mm - f.x_mm) < 0.01 &&
                                Math.abs(old.y_mm - f.y_mm) < 0.01 &&
                                Math.abs(old.w_mm - f.w_mm) < 0.01 &&
                                Math.abs(old.h_mm - f.h_mm) < 0.01;

                            if (same) {
                                delete this.pendingGeometry[i];
                            } else {
                                f.x_mm = old.x_mm;
                                f.y_mm = old.y_mm;
                                f.w_mm = old.w_mm;
                                f.h_mm = old.h_mm;
                            }
                        }

                        if (this.drag !== null && old && this.drag.index === i) {
                            // Jangan timpa field yang sedang ditarik.
                            f.x_mm = old.x_mm;
                            f.y_mm = old.y_mm;
                            f.w_mm = old.w_mm;
                            f.h_mm = old.h_mm;
                        }
                    });

                    this.fields = incoming;
                    this.selectedIndex = selectedIndex;
                    this.previewRow = previewRow || {};

                    if (backgroundUrl) {
                        this.backgroundUrl = backgroundUrl;
                    }

                    this.showPreview = showPreview !== false;
                },

                dropField(event) {
                    this.dragOver = false;

                    const key = event.dataTransfer.getData('text/plain') || event.dataTransfer.getData('text');

                    if (!key) {
                        return;
                    }

                    const rect = this.$refs.page.getBoundingClientRect();
                    const scale = this.pxPerMm * this.zoom / 100;
                    const xMm = (event.clientX - rect.left) / scale;
                    const yMm = (event.clientY - rect.top) / scale;

                    this.$wire.addFieldAt(key, Math.round(xMm * 100) / 100, Math.round(yMm * 100) / 100);
                },

                round(n) { return Math.round(n * 10) / 10; },

                fieldStyle(field) {
                    const scale = this.pxPerMm * this.zoom / 100;
                    const justify = { top: 'flex-start', middle: 'center', bottom: 'flex-end' }[field.valign] || 'center';
                    return 'left:' + (field.x_mm * scale) + 'px;top:' + (field.y_mm * scale) + 'px;' +
                        'width:' + (field.w_mm * scale) + 'px;height:' + (field.h_mm * scale) + 'px;' +
                        'justify-content:' + justify + ';';
                },

                textStyle(field) {
                    const scale = this.zoom / 100;
                    // Fallback generik mengikuti kategori font (serif/mono), bukan
                    // selalu sans-serif — kalau tidak, font serif tampil beda di
                    // browser yang belum punya font tersebut.
                    const generic = this.fontGenerics[field.font_family] || 'sans-serif';
                    return "font-family:'" + field.font_family + "', " + generic + ';' +
                        'font-size:' + (field.font_size_pt * 0.3528 * this.pxPerMm * scale).toFixed(2) + 'px;' +
                        'text-align:' + field.align + ';color:' + field.color + ';' +
                        'font-weight:' + (field.bold ? '700' : '400') + ';' +
                        'font-style:' + (field.italic ? 'italic' : 'normal') + ';';
                },

                displayValue(field) {
                    const penanda = '{' + '{' + field.key + '}' + '}';

                    if (!this.showPreview) {
                        return penanda;
                    }

                    const v = this.previewRow ? this.previewRow[field.key] : undefined;

                    // Key tidak ada di baris pratinjau (dataset belum dimuat /
                    // tidak cocok) — tampilkan penanda, jangan kosong.
                    if (v === undefined || v === null) {
                        return penanda;
                    }

                    return String(v);
                },

                select(index) {
                    this.selectedIndex = index;
                    this.$wire.selectField(index === null ? -1 : index);
                },

                // Drag pada area kosong = geser tampilan (pan) viewport,
                // bukan memindah field. Klik tanpa geser tetap menghapus
                // pilihan field seperti sebelumnya.
                startPan(event) {
                    this.select(null);

                    if (event.button !== 0 && event.pointerType === 'mouse') {
                        return;
                    }

                    const viewport = this.$refs.viewport;

                    if (!viewport) {
                        return;
                    }

                    this.pan = {
                        startX: event.clientX,
                        startY: event.clientY,
                        left: viewport.scrollLeft,
                        top: viewport.scrollTop,
                    };

                    viewport.classList.add('is-panning');

                    this.onPanMove = (e) => this.movePan(e);
                    this.onPanUp = (e) => this.endPan(e);

                    window.addEventListener('pointermove', this.onPanMove);
                    window.addEventListener('pointerup', this.onPanUp, { once: true });
                },

                movePan(event) {
                    if (!this.pan) return;

                    const viewport = this.$refs.viewport;

                    if (!viewport) return;

                    viewport.scrollLeft = this.pan.left - (event.clientX - this.pan.startX);
                    viewport.scrollTop = this.pan.top - (event.clientY - this.pan.startY);
                },

                endPan(event) {
                    const viewport = this.$refs.viewport;

                    viewport?.classList.remove('is-panning');
                    this.pan = null;

                    if (this.onPanMove) {
                        window.removeEventListener('pointermove', this.onPanMove);
                    }
                },

                startDrag(event, index, mode) {
                    event.stopPropagation();
                    this.selectedIndex = index;
                    this.$wire.selectField(index);

                    const scale = this.pxPerMm * this.zoom / 100;
                    const f = this.fields[index];

                    this.drag = {
                        index,
                        mode,
                        startX: event.clientX,
                        startY: event.clientY,
                        orig: { x: f.x_mm, y: f.y_mm, w: f.w_mm, h: f.h_mm },
                        scale,
                    };

                    this.onMove = (e) => this.move(e);
                    this.onUp = (e) => this.end(e);
                    window.addEventListener('pointermove', this.onMove);
                    window.addEventListener('pointerup', this.onUp, { once: true });
                },

                move(event) {
                    if (!this.drag) return;
                    const d = this.drag;
                    const f = this.fields[d.index];
                    if (!f) return;

                    const dxMm = (event.clientX - d.startX) / d.scale;
                    const dyMm = (event.clientY - d.startY) / d.scale;
                    let { x, y, w, h } = d.orig;

                    if (d.mode === 'move') {
                        x = x + dxMm;
                        y = y + dyMm;
                    } else {
                        if (d.mode.includes('e')) w = w + dxMm;
                        if (d.mode.includes('s')) h = h + dyMm;
                        if (d.mode.includes('w')) { x = x + dxMm; w = w - dxMm; }
                        if (d.mode.includes('n')) { y = y + dyMm; h = h - dyMm; }
                    }

                    // Clamp ke halaman.
                    w = Math.max(5, Math.min(w, this.pageWidthMm));
                    h = Math.max(3, Math.min(h, this.pageHeightMm));
                    x = Math.max(0, Math.min(x, this.pageWidthMm - w));
                    y = Math.max(0, Math.min(y, this.pageHeightMm - h));

                    // Snap 1 mm.
                    x = Math.round(x);
                    y = Math.round(y);
                    w = Math.round(w);
                    h = Math.round(h);

                    // Garis bantu: bandingkan titik tengah field dengan
                    // titik tengah halaman; toleransi 2 mm lalu snap tepat
                    // ke tengah agar hasil unduhan simetris.
                    const toleransi = 2;
                    const tengahX = this.pageWidthMm / 2;
                    const tengahY = this.pageHeightMm / 2;

                    this.guides.v = Math.abs((x + w / 2) - tengahX) <= toleransi;
                    this.guides.h = Math.abs((y + h / 2) - tengahY) <= toleransi;

                    if (this.guides.v) {
                        x = Math.max(0, Math.min(tengahX - w / 2, this.pageWidthMm - w));
                    }

                    if (this.guides.h) {
                        y = Math.max(0, Math.min(tengahY - h / 2, this.pageHeightMm - h));
                    }

                    f.x_mm = x;
                    f.y_mm = y;
                    f.w_mm = w;
                    f.h_mm = h;

                    this.dragInfo = 'x ' + this.round(x) + ' mm · y ' + this.round(y) +
                        ' mm · tengah ' + this.round(x + w / 2) + ', ' + this.round(y + h / 2) + ' mm';
                },

                end(event) {
                    if (!this.drag) return;

                    const d = this.drag;
                    const f = this.fields[d.index];
                    this.drag = null;
                    this.guides.v = false;
                    this.guides.h = false;
                    this.dragInfo = '';

                    window.removeEventListener('pointermove', this.onMove);

                    if (f) {
                        // Tandai: posisi lokal ini yang menang sampai server
                        // mengonfirmasi nilai yang sama (menghindari blink).
                        this.pendingGeometry[d.index] = true;

                        this.$wire.updateFieldGeometry(
                            d.index,
                            f.x_mm,
                            f.y_mm,
                            f.w_mm,
                            f.h_mm,
                        );
                    }
                },
            };
        };
    </script>
    @endscript

    {{-- Dialog Data Excel: terpisah dari form template. Unggah berkas di
         sini, semua baris langsung ditampilkan, lalu "Gunakan Data" untuk
         memakai kolomnya di kanvas. Saat edit template, data tersimpan
         otomatis dimuat ke dialog ini. --}}
    @if ($showExcelDialog)
        <div class="cert-modal-overlay" wire:click.self="closeExcelDialog">
            <div class="cert-modal">
                <div class="cert-modal-header">
                    <div>
                        <h2 class="cert-modal-title">Data Excel</h2>
                        <p class="cert-modal-desc">
                            Baris pertama berkas dianggap header. Semua baris di bawahnya ditampilkan di sini
                            dan bisa dipakai sebagai field pada template.
                        </p>
                    </div>
                    <button type="button" class="cert-icon-btn" wire:click="closeExcelDialog" title="Tutup">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="cert-modal-body">
                    <div class="cert-modal-toolbar">
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv,text/csv"
                            wire:model="excelUpload"
                            class="fi-input cert-modal-file"
                        >
                        <span wire:loading wire:target="excelUpload" class="cert-modal-hint">
                            Membaca berkas…
                        </span>
                        @if ($pendingExcelName)
                            <span class="cert-modal-hint">{{ $pendingExcelName }}</span>
                        @endif
                        @if ($pendingExcelRows !== [])
                            <span class="cert-modal-badge">
                                {{ count($pendingExcelRows) }} baris · {{ count($pendingExcelHeaders) }} kolom
                            </span>
                        @endif
                    </div>

                    @if ($pendingExcelRows === [])
                        <p class="cert-modal-empty">
                            Belum ada data. Pilih berkas Excel/CSV untuk melihat seluruh isinya di sini.
                        </p>
                    @else
                        <div class="cert-modal-table-wrap">
                            <table class="cert-modal-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        @foreach ($pendingExcelHeaders as $headerIndex => $headerLabel)
                                            <th>
                                                <span class="cert-modal-th-label">{{ $headerLabel }}</span>
                                                <span class="cert-modal-th-key">{{ $pendingExcelKeys[$headerIndex] ?? '' }}</span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pendingExcelRows as $rowIndex => $row)
                                        <tr>
                                            <td class="cert-modal-cell-num">{{ $rowIndex + 1 }}</td>
                                            @foreach ($pendingExcelKeys as $key)
                                                <td>{{ $row[$key] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="cert-modal-footer">
                    <div>
                        @if ($datasetLoaded && $rows !== [])
                            <x-filament::button
                                type="button"
                                color="danger"
                                size="sm"
                                icon="heroicon-o-trash"
                                wire:click="clearExcelData"
                                wire:confirm="Hapus data Excel dari template ini?"
                            >
                                Hapus Data Aktif
                            </x-filament::button>
                        @endif
                    </div>
                    <div class="cert-modal-actions">
                        <x-filament::button
                            type="button"
                            color="gray"
                            icon="heroicon-o-hashtag"
                            wire:click="openNumbersDialog"
                        >
                            Ambil dari Nomor Sertifikat
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" wire:click="closeExcelDialog">
                            Batal
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            icon="heroicon-o-check"
                            wire:click="applyExcelDialog"
                            wire:loading.attr="disabled"
                            wire:target="applyExcelDialog"
                            :disabled="$pendingExcelRows === []"
                        >
                            Gunakan Data
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Dialog "Ambil Data dari Nomor Surat": daftar kegiatan tersimpan
         (menu Nomor Sertifikat) ditampilkan sebagai tabel pilihan. --}}
    @if ($showNumbersDialog)
        <div class="cert-modal-overlay cert-modal-overlay-top" wire:click.self="closeNumbersDialog">
            <div class="cert-modal cert-modal-wide">
                <div class="cert-modal-header">
                    <div>
                        <h2 class="cert-modal-title">Ambil Data dari Nomor Surat</h2>
                        <p class="cert-modal-desc">
                            Klik salah satu kegiatan. Nomor surat, nama, dan keterangan tersimpan langsung sebagai
                            data template.
                        </p>
                    </div>
                    <button type="button" class="cert-icon-btn" wire:click="closeNumbersDialog" title="Tutup">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="cert-modal-body">
                    <div class="cert-num-search">
                        <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                            <x-filament::input
                                type="search"
                                wire:model.live.debounce.400ms="numberSearch"
                                placeholder="Cari kegiatan, tempat, kode, atau tahun…"
                            />
                        </x-filament::input.wrapper>
                        @if ($numberSearch !== '')
                            <x-filament::button type="button" color="gray" size="sm" icon="heroicon-o-x-mark"
                                wire:click="$set('numberSearch', '')">
                                Reset
                            </x-filament::button>
                        @endif
                        <span class="cert-num-search-count">{{ count($numberActivityOptions) }} kegiatan</span>
                    </div>

                    @if ($numberActivityOptions === [])
                        <p class="cert-modal-empty">
                            @if ($numberSearch !== '')
                                Tidak ada kegiatan yang cocok dengan “{{ $numberSearch }}”.
                            @else
                                Belum ada kegiatan dengan data nomor. Buka menu Nomor Sertifikat untuk menambah kegiatan
                                dan mengisi penerimanya.
                            @endif
                        </p>
                    @else
                        <div class="cert-num-table-wrap">
                            <table class="cert-num-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Kegiatan</th>
                                        <th>Tempat / Tanggal</th>
                                        <th>Kode</th>
                                        <th>Tahun</th>
                                        <th>Nomor</th>
                                        <th>Baris</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($numberActivityOptions as $option)
                                        <tr
                                            wire:key="cert-num-opt-{{ $option['id'] }}"
                                            wire:click="selectNumberActivity({{ $option['id'] }})"
                                            class="cert-num-row {{ $numberActivityId === $option['id'] ? 'is-selected' : '' }}"
                                        >
                                            <td class="cert-num-check">
                                                <span class="cert-num-dot"></span>
                                            </td>
                                            <td class="cert-num-kegiatan">{{ $option['kegiatan'] }}</td>
                                            <td class="cert-num-meta">
                                                {{ $option['tempat'] !== '' ? $option['tempat'] : '—' }}
                                                @if ($option['tanggal'] !== '')
                                                    <span class="cert-num-dim">· {{ $option['tanggal'] }}</span>
                                                @endif
                                            </td>
                                            <td><span class="cert-num-badge">{{ $option['kode'] }}</span></td>
                                            <td><span class="cert-num-badge">{{ $option['tahun'] }}</span></td>
                                            <td class="cert-num-range">{{ $option['range'] }}</td>
                                            <td><span class="cert-num-badge">{{ $option['jumlah'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="cert-modal-footer">
                    <div></div>
                    <div class="cert-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeNumbersDialog">
                            Batal
                        </x-filament::button>
                        <x-filament::button
                            type="button"
                            icon="heroicon-o-check"
                            wire:click="applyNumberActivity"
                            wire:loading.attr="disabled"
                            wire:target="applyNumberActivity"
                            :disabled="$numberActivityId === null"
                        >
                            Gunakan Data
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Render modal aksi halaman ini (mis. popup "Unduh Batch"). Layout
         x-filament-panels::page hanya merender modals otomatis untuk tabel. --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
