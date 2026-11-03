<x-filament-panels::page>
    {{-- Aset SuperDoc dilayani sendiri (tanpa CDN). Dimuat sekali di halaman
         ini saja; worker dokumen baru diambil saat editor dibuka.
         PENTING: jangan tambah query string (?v=...) atau ganti nama file
         index-*.js - chunk SuperDoc meng-import "./index-Cei13evq.js" TANPA
         query string, sehingga URL berbeda = modul ter-load 2x dan editor
         tidak bisa dibuka. --}}
    {{-- SuperDoc (AGPL-3.0) — sumber: public/vendor/superdoc/LICENSE-AGPL-3.0.txt --}}
    <link rel="stylesheet" href="{{ asset('vendor/superdoc/assets/index-B2sM2ewd.css') }}">
    <script type="module" src="{{ asset('vendor/superdoc/assets/index-Cei13evq.js') }}"></script>
    @php($config = $this->editorConfig)
    @php($notulen = $this->getRecord())

    @if ($notulen)
        <div class="nw-toolbar">
            <div class="nw-toolbar-left">
                <x-filament::button tag="a" color="gray" icon="heroicon-o-arrow-left"
                    :href="$this->getResourceUrl('view')">
                    Kembali
                </x-filament::button>
                <span class="nw-toolbar-title">{{ $notulen->judul }}</span>
            </div>
            <div class="nw-toolbar-right">
                {{-- Tombol editor hanya relevan di tab Edit. --}}
                <span class="nw-status" data-nw-status data-nw-edit-only>Belum ada perubahan</span>
                <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path"
                    data-nw-action="regenerate" data-nw-edit-only
                    wire:confirm="Generate ulang dari data notulen? Semua koreksi tata letak di editor akan hilang.">
                    Generate Ulang
                </x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-printer"
                    data-nw-action="print" data-nw-edit-only>
                    Cetak
                </x-filament::button>
                <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-down-tray"
                    data-nw-action="download" data-nw-edit-only>
                    Unduh Word
                </x-filament::button>
                <x-filament::button type="button" icon="heroicon-o-check" data-nw-action="save"
                    data-nw-edit-only wire:loading.attr="disabled">
                    Simpan Dokumen
                </x-filament::button>
            </div>
        </div>

        <div class="nw-tabs" role="tablist">
            <button type="button" class="nw-tab is-active" data-nw-tab="preview">Pratinjau PDF</button>
            <button type="button" class="nw-tab" data-nw-tab="edit">Edit Kanvas</button>
        </div>

        {{-- Pratinjau memakai PDF yang sama persis dengan hasil Cetak/Unduh
             (renderer LibreOffice), bukan mesin layout SuperDoc. --}}
        <div class="nw-panel" data-nw-panel="preview">
            <iframe id="nw-preview" class="nw-preview" title="Pratinjau PDF"
                src="{{ $config['pdfUrl'] }}"></iframe>
        </div>

        <div class="nw-panel" data-nw-panel="edit" hidden>
            <div class="nw-sd-toolbar" id="nw-sd-toolbar" wire:ignore></div>
            <div class="nw-editor-shell">
                <div id="nw-editor" class="nw-editor" wire:ignore></div>
                <div class="nw-loading" data-nw-loading>
                    <x-filament::loading-indicator class="h-6 w-6" />
                    <span>Memuat dokumen…</span>
                </div>
            </div>

            <p class="nw-hint">
                Kanvas SuperDoc adalah alat edit — paginasinya bisa sedikit berbeda dari Word.
                Hasil resmi selalu dari Cetak/Unduh (LibreOffice).
            </p>
        </div>

    {{-- Skrip editor dimuat sebagai module biasa (bukan @script Livewire)
         supaya pasti jalan setelah bundle SuperDoc dimuat. --}}
    <script type="application/json" id="nw-config">@json($config)</script>
    <script type="module" src="{{ asset('vendor/superdoc/nw-boot.js') . '?v=6' }}"></script>
    @endif

    @assets
    <style>
        .nw-sd-toolbar {
            position: sticky;
            top: 0;
            z-index: 6;
            margin-bottom: .5rem;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #fff;
            overflow-x: auto;
        }
        .dark .nw-sd-toolbar { background: #111827; border-color: #374151; }

        .nw-toolbar {
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
        .dark .nw-toolbar { background: rgba(17, 24, 39, .97); border-color: #374151; }
        .nw-toolbar-left { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .nw-toolbar-title {
            font-weight: 600;
            color: #111827;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .nw-toolbar-title { color: #f3f4f6; }
        .nw-toolbar-right { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }

        .nw-tabs {
            display: flex;
            gap: .25rem;
            margin-bottom: .75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .nw-tabs { border-color: #374151; }
        .nw-tab {
            padding: .45rem .9rem;
            font-size: .85rem;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .nw-tab.is-active { color: #111827; border-bottom-color: #f59e0b; }
        .dark .nw-tab { color: #9ca3af; }
        .dark .nw-tab.is-active { color: #f3f4f6; }

        .nw-preview {
            width: 100%;
            height: 78vh;
            min-height: 32rem;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #f3f4f6;
        }
        .dark .nw-preview { border-color: #374151; background: #1f2937; }
        .nw-status { font-size: .8rem; color: #6b7280; white-space: nowrap; }
        .dark .nw-status { color: #9ca3af; }
        .nw-status[data-tone="ok"] { color: #15803d; }
        .nw-status[data-tone="dirty"] { color: #b45309; }
        .nw-status[data-tone="error"] { color: #b91c1c; }

        .nw-editor-shell {
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            background: #f3f4f6;
            height: 78vh;
            min-height: 32rem;
            overflow: hidden;
        }
        .dark .nw-editor-shell { background: #1f2937; border-color: #374151; }
        .nw-editor {
            height: 100%;
            overflow: auto;
            padding: 1rem .5rem 2rem;
        }
        /* Halaman dokumen di tengah + kursor teks terlihat di atas kanvas. */
        .nw-editor .superdoc {
            justify-content: center;
            align-items: flex-start;
        }
        .nw-editor .superdoc__layers,
        .nw-editor .superdoc-page {
            margin-inline: auto;
        }
        .nw-editor .v2-super-editor__stage,
        .nw-editor .superdoc-text-run,
        .nw-editor .superdoc-line {
            cursor: text !important;
        }
        /* Kursor edit dibuat tegas: 2px, biru, berkedip lambat supaya
           terlihat jelas di setiap klik. */
        .nw-editor .sd-v2-local-selection-caret {
            width: 2px !important;
            background-color: #1d4ed8 !important;
            box-shadow: none !important;
            opacity: 1 !important;
            z-index: 10 !important;
            animation: nw-caret-blink 1.3s steps(1) infinite !important;
        }
        @keyframes nw-caret-blink {
            0%, 69% { opacity: 1; }
            70%, 100% { opacity: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .nw-editor .sd-v2-local-selection-caret { animation: none !important; }
        }
        .nw-loading {
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
        .dark .nw-loading { background: rgba(31, 41, 55, .9); color: #d1d5db; }
        .nw-error-box {
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
        .nw-hint { margin-top: .5rem; font-size: .8rem; color: #6b7280; }
        .dark .nw-hint { color: #9ca3af; }
        .nw-toast {
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
