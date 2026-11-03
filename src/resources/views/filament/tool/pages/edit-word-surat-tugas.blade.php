<x-filament-panels::page>
    {{-- Aset SuperDoc dilayani sendiri (tanpa CDN). Dimuat sekali di halaman
         ini saja; worker dokumen baru diambil saat editor dibuka.
         PENTING: jangan tambah query string (?v=...) atau ganti nama file
         index-*.js - chunk SuperDoc meng-import "./index-Cei13evq.js" TANPA
         query string, sehingga URL berbeda = modul ter-load 2x dan editor
         tidak bisa dibuka. -->
    {{-- SuperDoc (AGPL-3.0) — sumber: public/vendor/superdoc/LICENSE-AGPL-3.0.txt --}}
    <link rel="stylesheet" href="{{ asset('vendor/superdoc/assets/index-B2sM2ewd.css') }}">
    <script type="module" src="{{ asset('vendor/superdoc/assets/index-Cei13evq.js') }}"></script>
    @php($config = $this->editorConfig)
    @php($surat = $this->getRecord())

    @if ($surat)
        <div class="stw-toolbar">
            <div class="stw-toolbar-left">
                <x-filament::button tag="a" color="gray" icon="heroicon-o-arrow-left"
                    :href="$this->getResourceUrl('view')">
                    Kembali
                </x-filament::button>
                <span class="stw-toolbar-title">{{ $surat->judul }}</span>
            </div>
            <div class="stw-toolbar-right">
                {{-- Tombol editor hanya relevan di tab Edit. --}}
                <span class="stw-status" data-stw-status data-stw-edit-only>Belum ada perubahan</span>
                <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path"
                    data-stw-action="regenerate" data-stw-edit-only
                    wire:confirm="Generate ulang dari data surat? Semua koreksi tata letak di editor akan hilang.">
                    Generate Ulang
                </x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-printer"
                    data-stw-action="print" data-stw-edit-only>
                    Cetak
                </x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-down-tray"
                    data-stw-action="download" data-stw-edit-only>
                    Unduh Word
                </x-filament::button>
                <x-filament::button type="button" icon="heroicon-o-check" data-stw-action="save"
                    data-stw-edit-only wire:loading.attr="disabled">
                    Simpan Dokumen
                </x-filament::button>
            </div>
        </div>

        <div class="stw-tabs" role="tablist">
            <button type="button" class="stw-tab is-active" data-stw-tab="preview">Pratinjau PDF</button>
            <button type="button" class="stw-tab" data-stw-tab="edit">Edit Kanvas</button>
        </div>

        {{-- Pratinjau memakai PDF yang sama persis dengan hasil Cetak/Unduh
             (renderer LibreOffice), bukan mesin layout SuperDoc. --}}
        <div class="stw-panel" data-stw-panel="preview">
            <iframe id="stw-preview" class="stw-preview" title="Pratinjau PDF"
                src="{{ $config['pdfUrl'] }}"></iframe>
        </div>

        <div class="stw-panel" data-stw-panel="edit" hidden>
            <div class="stw-sd-toolbar" id="stw-sd-toolbar" wire:ignore></div>
            <div class="stw-editor-shell">
                <div id="stw-editor" class="stw-editor" wire:ignore></div>
                <div class="stw-loading" data-stw-loading>
                    <x-filament::loading-indicator class="h-6 w-6" />
                    <span>Memuat dokumen…</span>
                </div>
            </div>

            <p class="stw-hint">
                Kanvas SuperDoc adalah alat edit — paginasinya bisa sedikit berbeda dari Word.
                Hasil resmi selalu dari Cetak/Unduh (LibreOffice).
            </p>
        </div>

    {{-- Skrip editor dimuat sebagai module biasa (bukan @script Livewire)
         supaya pasti jalan setelah bundle SuperDoc dimuat. --}}
    <script type="application/json" id="stw-config">@json($config)</script>
    <script type="module" src="{{ asset('vendor/superdoc/stw-boot.js') . '?v=7' }}"></script>
    @endif

    @assets
    <style>
        .stw-sd-toolbar {
            position: sticky;
            top: 0;
            z-index: 6;
            margin-bottom: .5rem;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #fff;
            overflow-x: auto;
        }
        .dark .stw-sd-toolbar { background: #111827; border-color: #374151; }

        .stw-toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .6rem .75rem;
            margin-bottom: .75rem;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: rgba(255, 255, 255, .97);
        }
        .dark .stw-toolbar { background: rgba(17, 24, 39, .97); border-color: #374151; }
        .stw-toolbar-left { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .stw-toolbar-title {
            font-weight: 600;
            color: #111827;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .stw-toolbar-title { color: #f3f4f6; }
        .stw-toolbar-right { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }

        .stw-tabs {
            display: flex;
            gap: .25rem;
            margin-bottom: .75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .stw-tabs { border-color: #374151; }
        .stw-tab {
            padding: .45rem .9rem;
            font-size: .85rem;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .stw-tab.is-active { color: #111827; border-bottom-color: #f59e0b; }
        .dark .stw-tab { color: #9ca3af; }
        .dark .stw-tab.is-active { color: #f3f4f6; }

        .stw-preview {
            width: 100%;
            height: 78vh;
            min-height: 32rem;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #f3f4f6;
        }
        .dark .stw-preview { border-color: #374151; background: #1f2937; }
        .stw-status { font-size: .8rem; color: #6b7280; white-space: nowrap; }
        .dark .stw-status { color: #9ca3af; }
        .stw-status[data-tone="ok"] { color: #15803d; }
        .stw-status[data-tone="dirty"] { color: #b45309; }
        .stw-status[data-tone="error"] { color: #b91c1c; }

        .stw-editor-shell {
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #f3f4f6;
            height: 78vh;
            min-height: 32rem;
            overflow: hidden;
        }
        .dark .stw-editor-shell { background: #1f2937; border-color: #374151; }
        .stw-editor {
            height: 100%;
            overflow: auto;
            padding: 1rem .5rem 2rem;
        }
        /* Halaman dokumen di tengah + kursor teks terlihat di atas kanvas. */
        .stw-editor .superdoc {
            justify-content: center;
            align-items: flex-start;
        }
        .stw-editor .superdoc__layers,
        .stw-editor .superdoc-page {
            margin-inline: auto;
        }
        .stw-editor .v2-super-editor__stage,
        .stw-editor .superdoc-text-run,
        .stw-editor .superdoc-line {
            cursor: text !important;
        }
        /* Kursor edit dibuat tegas: 2px, biru, berkedip lambat supaya
           terlihat jelas di setiap klik. */
        .stw-editor .sd-v2-local-selection-caret {
            width: 2px !important;
            background-color: #1d4ed8 !important;
            box-shadow: none !important;
            opacity: 1 !important;
            z-index: 10 !important;
            animation: stw-caret-blink 1.3s steps(1) infinite !important;
        }
        @keyframes stw-caret-blink {
            0%, 69% { opacity: 1; }
            70%, 100% { opacity: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .stw-editor .sd-v2-local-selection-caret { animation: none !important; }
        }
        .stw-loading {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: .875rem;
            color: #4b5563;
            background: rgba(243, 244, 246, .9);
        }
        .dark .stw-loading { background: rgba(31, 41, 55, .9); color: #d1d5db; }
        .stw-error-box {
            display: grid;
            gap: .25rem;
            max-width: 34rem;
            padding: 1rem 1.25rem;
            border: 1px solid #fca5a5;
            border-radius: .5rem;
            background: #fff;
            color: #7f1d1d;
            text-align: center;
            font-size: .875rem;
            line-height: 1.45;
        }
        .stw-hint { margin-top: .5rem; font-size: .8rem; color: #6b7280; }
        .dark .stw-hint { color: #9ca3af; }
        .stw-toast {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 90;
            padding: .6rem 1rem;
            border-radius: .5rem;
            background: #166534;
            color: #fff;
            font-size: .875rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .25);
        }
    </style>
    @endassets
</x-filament-panels::page>
