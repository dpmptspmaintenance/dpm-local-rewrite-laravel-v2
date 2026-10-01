<x-filament-panels::page>
    @php
        $tabBase = 'rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-medium transition';
        $tabActive = 'border-primary-500 bg-white text-primary-600 dark:bg-white/5 dark:text-primary-400';
        $tabInactive = 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300';
    @endphp

    <div class="flex gap-1 border-b border-gray-200 dark:border-white/10">
        <a
            href="{{ \App\Filament\Kepegawaian\Pages\ImportData::getUrl(['tab' => 'pegawai']) }}"
            wire:navigate
            class="{{ $tabBase }} {{ $this->tab === 'pegawai' ? $tabActive : $tabInactive }}"
        >
            Pegawai (JSON)
        </a>
        <a
            href="{{ \App\Filament\Kepegawaian\Pages\ImportData::getUrl(['tab' => 'cuti']) }}"
            wire:navigate
            class="{{ $tabBase }} {{ $this->tab === 'cuti' ? $tabActive : $tabInactive }}"
        >
            Cuti (Excel)
        </a>
    </div>

    @if ($this->tab === 'pegawai')
        <form wire:submit="importPegawai" class="fi-form grid gap-6">
            {{ $this->pegawaiForm }}

            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="importPegawai">
                    Mulai Impor
                </x-filament::button>
                <span wire:loading wire:target="importPegawai" class="text-sm text-gray-500 dark:text-gray-400">
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

        @if ($this->pegawaiErrors)
            <x-filament::section>
                <x-slot name="heading">Catatan Impor ({{ count($this->pegawaiErrors) }})</x-slot>
                <x-slot name="description">Record berikut gagal diimpor.</x-slot>

                <ul class="max-h-96 space-y-1 overflow-y-auto text-sm text-gray-600 dark:text-gray-400">
                    @foreach ($this->pegawaiErrors as $error)
                        <li class="border-b border-gray-100 py-1 last:border-0 dark:border-white/5">{{ $error }}</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
    @else
        <form wire:submit="importCuti" class="fi-form grid gap-6">
            {{ $this->cutiForm }}

            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled" wire:target="importCuti">
                    Mulai Impor
                </x-filament::button>
                <span wire:loading wire:target="importCuti" class="text-sm text-gray-500 dark:text-gray-400">
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

        @if ($this->cutiErrors)
            <x-filament::section>
                <x-slot name="heading">Catatan Impor ({{ count($this->cutiErrors) }})</x-slot>
                <x-slot name="description">
                    Baris berikut tetap tersimpan, tapi ada hal yang perlu dicek manual.
                </x-slot>

                <ul class="max-h-96 space-y-1 overflow-y-auto text-sm text-gray-600 dark:text-gray-400">
                    @foreach ($this->cutiErrors as $error)
                        <li class="border-b border-gray-100 py-1 last:border-0 dark:border-white/5">{{ $error }}</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
