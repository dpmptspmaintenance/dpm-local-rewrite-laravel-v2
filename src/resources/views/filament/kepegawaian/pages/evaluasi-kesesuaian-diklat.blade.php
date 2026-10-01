<x-filament-panels::page>
    @php($stats = $this->getStats())

    <div class="mb-4 max-w-xs">
        {{ $this->form }}
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Pegawai', 'value' => $stats['total'], 'suffix' => '', 'color' => 'primary', 'icon' => 'heroicon-m-users', 'hint' => 'Pegawai aktif'],
            ['label' => 'Terpenuhi', 'value' => $stats['terpenuhi'], 'suffix' => '', 'color' => 'success', 'icon' => 'heroicon-m-check-badge', 'hint' => 'Realisasi JP ≥ jatah'],
            ['label' => 'Kurang', 'value' => $stats['kurang'], 'suffix' => '', 'color' => 'danger', 'icon' => 'heroicon-m-exclamation-triangle', 'hint' => 'Realisasi JP < jatah'],
            ['label' => 'Rata-rata Capaian', 'value' => $stats['rata_persen'], 'suffix' => '%', 'color' => 'info', 'icon' => 'heroicon-m-chart-bar', 'hint' => 'Realisasi ÷ jatah, semua pegawai'],
        ] as $stat)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stat['value']) }}{{ $stat['suffix'] }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
                    </div>
                    <x-filament::icon
                        :icon="$stat['icon']"
                        @class([
                            'h-7 w-7 shrink-0',
                            'text-primary-500' => $stat['color'] === 'primary',
                            'text-success-500' => $stat['color'] === 'success',
                            'text-danger-500' => $stat['color'] === 'danger',
                            'text-info-500' => $stat['color'] === 'info',
                        ])
                    />
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section
        class="mt-6"
        description="Jumlah: realisasi JP dibanding jatah (PNS minimal 20 JP/tahun — PP 11/2017 jo. PP 17/2020; PPPK & PPPK paruh waktu hak s.d. 24 JP/tahun — Perka LAN No. 15/2020). Keragaman Jenis: variasi FORMAT diklat (Seminar/Workshop/dst), bukan ukuran kecocokan topik. Sesuai Jabatan: judul-judul kompetensi tahun ini yang ditandai manual Sesuai — tandai di daftar Kompetensi atau tab Riwayat Kompetensi pegawai (default Sesuai sampai ditinjau)."
    >
        <x-slot name="heading">Evaluasi per Pegawai</x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
