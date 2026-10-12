<x-filament-panels::page>
    <form wire:submit="import" class="fi-form grid gap-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="import">
                Mulai Impor
            </x-filament::button>
            <span wire:loading wire:target="import" class="text-sm text-gray-500 dark:text-gray-400">
                Memproses berkas PDF…
            </span>
        </div>
    </form>

    @if ($this->sukses)
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Impor berhasil. Lihat hasilnya di halaman
                <a href="{{ \App\Filament\Kepegawaian\Pages\DukPage::getUrl() }}" class="text-primary-600 hover:underline dark:text-primary-400">DUK</a>.
            </p>
        </x-filament::section>
    @endif

    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Cara kerja impor</x-slot>
        <ul class="list-disc space-y-1 ps-5 text-sm text-gray-600 dark:text-gray-400">
            <li>Tiap impor disimpan sebagai <strong>batch upload baru</strong> — riwayat impor sebelumnya tetap ada.</li>
            <li>Masa kerja diambil apa adanya dari dokumen DUK, bukan dihitung ulang.</li>
            <li>Pilih periode upload mana yang ditampilkan di halaman DUK.</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
