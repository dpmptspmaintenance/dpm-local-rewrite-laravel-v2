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
            <li>Baris dengan <strong>No Surat</strong> yang sudah ada di database akan <strong>diperbarui</strong>.</li>
            <li>Baris yang No Suratnya belum ada akan <strong>ditambahkan</strong> sebagai data baru.</li>
            <li>Data lama yang tidak muncul di berkas <strong>tetap tersimpan</strong> — tidak ada yang dihapus.</li>
            <li>No Surat / NIP / Nama yang kosong tetap disimpan apa adanya.</li>
            <li>NIP yang terbaca sebagai angka Excel dicocokkan ulang dengan data pegawai bila memungkinkan.</li>
        </ul>
    </x-filament::section>

    @if ($this->errors)
        <x-filament::section>
            <x-slot name="heading">Catatan Impor ({{ count($this->errors) }})</x-slot>
            <x-slot name="description">
                Baris berikut tetap tersimpan, tapi ada hal yang perlu dicek manual.
            </x-slot>

            <ul class="max-h-96 space-y-1 overflow-y-auto text-sm text-gray-600 dark:text-gray-400">
                @foreach ($this->errors as $error)
                    <li class="border-b border-gray-100 py-1 last:border-0 dark:border-white/5">{{ $error }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
