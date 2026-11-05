<x-filament-panels::page>
    @if (filled($this->teksGenerate))
        <x-filament::section
            heading="Hasil Generate"
            description="Centang baris lalu klik Generate. Teks siap disalin ke WhatsApp/laporan."
        >
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                <textarea
                    readonly
                    rows="{{ min(20, substr_count($this->teksGenerate, "\n") + 1) }}"
                    onclick="this.select()"
                    style="width:100%; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:13px; line-height:1.5; padding:.5rem .75rem; border-radius:.5rem; border:1px solid rgba(128,128,128,.35); background:transparent; resize:vertical; white-space:pre;"
                >{{ $this->teksGenerate }}</textarea>

                <div style="display:flex; gap:.5rem;">
                    <x-filament::button wire:click="salinGenerate" size="sm" icon="heroicon-m-clipboard">
                        Salin
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
