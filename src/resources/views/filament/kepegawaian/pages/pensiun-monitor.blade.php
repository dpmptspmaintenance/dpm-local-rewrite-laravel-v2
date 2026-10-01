<x-filament-panels::page>
    @php($stats = $this->getStats())

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Terdata', 'value' => $stats['total'], 'color' => 'primary', 'icon' => 'heroicon-m-users', 'hint' => 'Punya TMT BUP/BUT'],
            ['label' => 'Pensiun < 1 Tahun', 'value' => $stats['warning'], 'color' => 'warning', 'icon' => 'heroicon-m-exclamation-triangle', 'hint' => 'Perlu penyiapan berkas'],
            ['label' => 'Pensiun 1–2 Tahun', 'value' => $stats['siaga'], 'color' => 'info', 'icon' => 'heroicon-m-clock', 'hint' => 'Masuk masa siaga'],
            ['label' => 'Sudah Lampau', 'value' => $stats['lampau'], 'color' => 'danger', 'icon' => 'heroicon-m-archive-box', 'hint' => 'TMT sudah terlewati'],
        ] as $stat)
            <x-filament::section class="fi-stat">
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
                            'text-warning-500' => $stat['color'] === 'warning',
                            'text-info-500' => $stat['color'] === 'info',
                            'text-danger-500' => $stat['color'] === 'danger',
                        ])
                    />
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
