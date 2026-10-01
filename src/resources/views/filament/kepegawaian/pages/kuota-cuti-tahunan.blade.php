<x-filament-panels::page>
    <div class="mb-4 max-w-xs">
        {{ $this->form }}
    </div>

    <x-filament::section
        heading="Kuota per Pegawai"
        description="Kolom Kuota dan Keterangan bisa diklik dan diedit langsung. Kosong (Default) berarti pegawai memakai jatah normal — isi hanya untuk pengecualian, mis. PPPK/CPNS di tahun pertama."
    >
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
