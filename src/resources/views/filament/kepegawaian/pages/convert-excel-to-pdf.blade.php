<x-filament-panels::page>
    <form wire:submit="convert" class="fi-form grid gap-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled">
                Convert ke PDF
            </x-filament::button>
            <span wire:loading wire:target="convert" class="text-sm text-gray-500 dark:text-gray-400">
                Memproses berkas…
            </span>
        </div>
    </form>

    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Cara kerja</x-slot>
        <ul class="list-disc space-y-1 ps-5 text-sm text-gray-600 dark:text-gray-400">
            <li>Upload satu atau beberapa berkas Excel (.xlsx/.xls) — sheet-nya terbaca otomatis.</li>
            <li>Default hanya sheet pertama yang dipilih per berkas. Aktifkan <strong>Gabung jadi satu PDF</strong> untuk memilih lebih dari satu sheet — sheet-sheet terpilih digabung jadi satu PDF.</li>
            <li>Upload lebih dari satu berkas: tiap berkas tetap diatur sendiri (sheet + gabung), hasilnya satu PDF per berkas, dibungkus dalam satu berkas .zip.</li>
            <li>Kalau cuma satu berkas dan hasilnya satu PDF, langsung didownload sebagai PDF (tidak dizip).</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
