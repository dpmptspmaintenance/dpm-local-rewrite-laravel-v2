<x-filament-panels::page>
    <form wire:submit="import" class="fi-form grid gap-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="import">
                Mulai Impor
            </x-filament::button>
            <span wire:loading wire:target="import" class="text-sm text-gray-500 dark:text-gray-400">
                Memproses berkas…
            </span>
        </div>
    </form>

    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Cara kerja impor</x-slot>
        <ul class="list-disc space-y-1 ps-5 text-sm text-gray-600 dark:text-gray-400">
            <li>Profil pegawai dicocokkan berdasarkan <strong>NIP</strong> — sudah ada diperbarui, belum ada ditambahkan.</li>
            <li>Riwayat anak &amp; kompetensi <strong>diganti seluruhnya</strong> tiap impor, bukan ditambah — tiap record dianggap snapshot terkini.</li>
            <li>Tanggal <code>dd-mm-yyyy</code>; nilai kosong atau <code>-</code> disimpan sebagai kosong.</li>
            <li>Satu record gagal tidak membatalkan yang lain — tiap record punya transaksi sendiri.</li>
        </ul>
    </x-filament::section>

    @if ($this->errors)
        <x-filament::section>
            <x-slot name="heading">Catatan Impor ({{ count($this->errors) }})</x-slot>
            <x-slot name="description">Record berikut gagal diimpor.</x-slot>

            <ul class="max-h-96 space-y-1 overflow-y-auto text-sm text-gray-600 dark:text-gray-400">
                @foreach ($this->errors as $error)
                    <li class="border-b border-gray-100 py-1 last:border-0 dark:border-white/5">{{ $error }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
