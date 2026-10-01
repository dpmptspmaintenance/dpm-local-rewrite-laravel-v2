<x-filament-panels::page>
    @php($stats = $this->getStats())

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Anak Terdata', 'value' => $stats['total'], 'color' => 'primary', 'icon' => 'heroicon-m-user-group', 'hint' => 'Punya tanggal lahir'],
            ['label' => 'Hak Masih Aktif', 'value' => $stats['aktif'], 'color' => 'success', 'icon' => 'heroicon-m-check-badge', 'hint' => '> 1 tahun ke batas usia'],
            ['label' => 'Berakhir < 1 Tahun', 'value' => $stats['warning'], 'color' => 'warning', 'icon' => 'heroicon-m-exclamation-triangle', 'hint' => 'Siapkan perubahan KP4'],
            ['label' => 'Hak Sudah Habis', 'value' => $stats['habis'], 'color' => 'danger', 'icon' => 'heroicon-m-x-circle', 'hint' => 'Melewati batas usia'],
        ] as $stat)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($stat['value']) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
                    </div>
                    <x-filament::icon
                        :icon="$stat['icon']"
                        @class([
                            'h-8 w-8 shrink-0',
                            'text-primary-500' => $stat['color'] === 'primary',
                            'text-success-500' => $stat['color'] === 'success',
                            'text-warning-500' => $stat['color'] === 'warning',
                            'text-danger-500' => $stat['color'] === 'danger',
                        ])
                    />
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Batas usia hak tunjangan anak: <strong>21 tahun</strong>, diperpanjang sampai
            <strong>25 tahun</strong> bila anak masih menempuh pendidikan tinggi
            (Diploma III, Diploma IV, S-1, S-2, S-3).
        </p>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
